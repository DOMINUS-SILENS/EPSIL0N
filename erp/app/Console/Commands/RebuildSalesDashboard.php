<?php

namespace App\Console\Commands;

use App\Models\DomainOutbox;
use App\Services\Projectors\SalesDashboardProjector;
use Illuminate\Console\Command;

/**
 * Command to rebuild the Sales Dashboard projections.
 * Useful for backfilling analytics after schema changes or data corrections.
 */
class RebuildSalesDashboard extends Command
{
    protected $signature = 'dashboard:rebuild-sales
                            {--date-from= : Start date (Y-m-d) for selective rebuild}
                            {--date-to= : End date (Y-m-d) for selective rebuild}
                            {--chunk=500 : Number of events to process per batch}';

    protected $description = 'Rebuild sales dashboard projections from domain events';

    public function handle(SalesDashboardProjector $projector): int
    {
        $this->info('🔄 Rebuilding Sales Dashboard Projections...');

        // Clear existing dashboard data if doing full rebuild
        if (!$this->option('date-from') && !$this->option('date-to')) {
            $this->info('Clearing existing dashboard data...');
            \DB::table('dashboard_sales')->delete();
            \DB::table('dashboard_top_articles')->delete();
        }

        $query = DomainOutbox::whereIn('event_type', [
            'MovementValidated',
            'movement.validated',
            'StopVisited',
            'stop.visited',
        ])->where('status', 'processed');

        // Apply date filters if provided
        if ($this->option('date-from')) {
            $query->whereDate('created_at', '>=', $this->option('date-from'));
        }
        if ($this->option('date-to')) {
            $query->whereDate('created_at', '<=', $this->option('date-to'));
        }

        $chunkSize = $this->option('chunk');
        $totalEvents = $query->clone()->count();

        if ($totalEvents === 0) {
            $this->warn('No events found for processing.');
            return Command::SUCCESS;
        }

        $this->info("Processing {$totalEvents} events in chunks of {$chunkSize}...");
        $bar = $this->output->createProgressBar($totalEvents);
        $bar->start();

        $processed = 0;

        $query->orderBy('id')->chunk($chunkSize, function ($events) use ($projector, $bar, &$processed) {
            foreach ($events as $event) {
                try {
                    $projector->process($event);
                    $processed++;
                } catch (\Exception $e) {
                    \Log::error('Dashboard rebuild failed for event', [
                        'event_id' => $event->id,
                        'error' => $e->getMessage(),
                    ]);
                }
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        // Show summary statistics
        $this->info('📊 Dashboard Rebuild Complete!');
        $this->info("Total events processed: {$processed}");

        $salesRows = \DB::table('dashboard_sales')->count();
        $articlesRows = \DB::table('dashboard_top_articles')->count();

        $this->info("Dashboard sales records: {$salesRows}");
        $this->info("Dashboard top articles records: {$articlesRows}");

        // Show sample data
        $this->newLine();
        $this->info('Sample dashboard_sales data:');
        $sample = \DB::table('dashboard_sales')
            ->select('date', 'route_id', 'nb_orders', 'subtotal_amount', 'nb_clients_visited')
            ->limit(5)
            ->get();

        $this->table(
            ['Date', 'Route', 'Orders', 'Total HT', 'Visits'],
            $sample->map(fn($r) => [
                $r->date,
                $r->route_id,
                $r->nb_orders,
                number_format($r->subtotal_amount, 2),
                $r->nb_clients_visited,
            ])->toArray()
        );

        return Command::SUCCESS;
    }
}