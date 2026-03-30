<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use App\Models\Article;
use App\Services\OrderApplicationService;
use App\Aggregates\StockAggregate;
use Illuminate\Support\Str;

class SeedCertificationDataset extends Command
{
    protected $signature = 'cert:seed-sfa {--clean : Truncate everything before seeding}';
    protected $description = 'Seed dataset certification via domaine métier strict (events/projectors)';

    public function handle(OrderApplicationService $orderService): int
    {
        $this->info("Initializing Gate B0 Dataset Construction...");

        if ($this->option('clean')) {
            $this->warn("Cleaning database...");
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('event_store')->truncate();
            DB::table('domain_events')->truncate();
            DB::table('domain_outbox')->truncate();
            DB::table('orders')->truncate();
            DB::table('order_lines')->truncate();
            DB::table('balance_stock')->truncate();
            DB::table('article_mouvement')->truncate();
            DB::table('projector_processed_events')->truncate();
            DB::table('projector_checkpoints')->truncate();
            DB::table('api_idempotency_keys')->truncate();
            DB::table('article')->truncate();
            DB::table('customers')->truncate();
            DB::table('users')->truncate();
            DB::table('depot')->truncate();
            DB::table('device_sync_state')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        DB::transaction(function () {
            $this->seedReferentiels();
        });

        // Event-sourced operations MUST NOT share the same massive transaction 
        // because idempotency and deadlocks would trigger, and outbox expects standalone transactions.
        $this->seedStockEvents();
        $this->seedOrderEvents($orderService);
        
        $this->replayProjectors();

        $this->info("\nDataset certification généré via moteur métier.");
        $this->table(['Table', 'Volume'], $this->getVolumes());

        return self::SUCCESS;
    }

    private function seedReferentiels(): void
    {
        $this->info("Phase 1: Generating Referential Foundations via Factories");

        // Users
        \App\Models\User::factory()->count(50)->create();
        $this->line("-> 50 users created.");

        // Depots
        \App\Models\Depot::factory()->count(20)->create();
        $this->line("-> 20 depots created.");

        // Customers
        \App\Models\Customer::factory()->count(1000)->create();
        $this->line("-> 1000 customers created.");

        // Articles
        \App\Models\Article::factory()->count(5000)->create();
        $this->line("\n-> 5000 articles created.");

        // Device Sync State
        \App\Models\DeviceSyncState::factory()->count(200)->create();
        $this->line("-> 200 device syncing states registered.");
    }

    private function seedStockEvents(): void
    {
        $this->info("Phase 2: Generating Stock Operations (Event-Sourced)");
        $iterations = 5000; // 5000 stock movements
        $this->output->progressStart($iterations);

        for ($i = 1; $i <= $iterations; $i++) {
            $depotId = rand(1, 20);
            $articleId = rand(1, 5000);
            $quantity = rand(10, 500);

            try {
                $uuid = "STOCK-{$depotId}-{$articleId}";
                $agg = StockAggregate::retrieve($uuid);
                $agg->receive($depotId, $articleId, 1, $quantity, "Initial Seeding $i");
                $agg->persist();
            } catch (\Exception $e) {
                // Ignore business errors like invalid state silently for seed speed
            }

            if ($i % 100 === 0) {
                $this->output->progressAdvance(100);
            }
        }
        $this->output->progressFinish();
        $this->line("\n-> Stock events committed to Source of Truth.");
    }

    private function seedOrderEvents(OrderApplicationService $orderService): void
    {
        $this->info("Phase 3: Generating Orders (Event-Sourced & Domain Service)");
        $iterations = 10000;
        $this->output->progressStart($iterations);

        for ($i = 1; $i <= $iterations; $i++) {
            $payload = [
                'entreprise_id' => 1,
                'customer_id' => rand(1, 1000),
                'subtotal_amount' => rand(50, 500),
                'total_amount' => rand(60, 600),
                'lines' => [
                    [
                        'product_id' => rand(1, 5000), 
                        'quantity' => rand(1, 5), 
                        'unit_price' => rand(10, 50)
                    ],
                    [
                        'product_id' => rand(1, 5000), 
                        'quantity' => rand(1, 2), 
                        'unit_price' => rand(5, 20)
                    ]
                ]
            ];

            try {
                // Generates OrderCreated + writes Outbox + writes Read Model (via Launch Bridge) + guarantees Idempotency
                $mutId = "seed-mut-{$i}";
                $orderService->createOrder($payload, 'usr-' . rand(1, 50), '/api/orders', $mutId, 'DEV-' . rand(1, 200));
            } catch (\Exception $e) {
                // Proceed on conflict or error
            }

            if ($i % 100 === 0) {
                $this->output->progressAdvance(100);
            }
        }
        $this->output->progressFinish();
        $this->line("\n-> Order operations committed cleanly.");
    }

    private function replayProjectors(): void
    {
        $this->info("Phase 4: Synchronizing Projectors (Outbox Flush)");
        
        $pending = DB::table('domain_outbox')->where('status', 'pending')->count();
        $this->line("Pending Outbox events to process: $pending");
        $this->output->progressStart($pending);

        while ($pending > 0) {
            Artisan::call('outbox:process', ['--chunk' => 500]);
            
            $newPending = DB::table('domain_outbox')->where('status', 'pending')->count();
            $advanced = $pending - $newPending;
            if ($advanced > 0) {
                $this->output->progressAdvance($advanced);
            }
            if ($newPending === $pending) {
                // Stuck in dead letters or failed state
                break;
            }
            $pending = $newPending;
        }

        $this->output->progressFinish();
        $this->line("\n-> Projection Complete.");
    }

    private function getVolumes(): array
    {
        return [
            ['articles', DB::table('article')->count()],
            ['customers', DB::table('customers')->count()],
            ['orders', DB::table('orders')->count()],
            ['event_store', DB::table('event_store')->count()],
            ['domain_events', DB::table('domain_events')->count()],
            ['domain_outbox (total)', DB::table('domain_outbox')->count()],
            ['domain_outbox (pending)', DB::table('domain_outbox')->where('status', 'pending')->count()],
            ['domain_outbox (dead)', DB::table('domain_outbox')->where('status', 'dead')->count()],
            ['balance_stock', DB::table('balance_stock')->count()],
            ['projector_processed_events', DB::table('projector_processed_events')->count()],
            ['device_sync_state', DB::table('device_sync_state')->count()],
        ];
    }
}