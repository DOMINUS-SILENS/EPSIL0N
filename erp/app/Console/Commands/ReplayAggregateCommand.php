<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DomainEvent;

class ReplayAggregateCommand extends Command
{
    protected $signature = 'replay:aggregate 
                            {tenant_id : The ID of the tenant}
                            {aggregate_type : Types such as "order" or "stock"}
                            {aggregate_id : The specific aggregate UUID}
                            {projector : The fully qualified class name of the projector}';

    protected $description = 'Enterprise Replay Governance: Rebuilds projector state deterministically for a specific aggregate instance';

    public function handle(): void
    {
        $tenantId = $this->argument('tenant_id');
        $aggregateType = $this->argument('aggregate_type');
        $aggregateId = $this->argument('aggregate_id');
        $projectorClass = $this->argument('projector');

        if (!class_exists($projectorClass)) {
            $this->error("Projector {$projectorClass} does not exist.");
            return;
        }

        /** @var \App\Services\Projector $projector */
        $projector = app($projectorClass);

        $query = DomainEvent::where('tenant_id', $tenantId)
            ->where('aggregate_type', $aggregateType)
            ->where('aggregate_id', $aggregateId)
            ->orderBy('sequence', 'asc');
        
        $count = $query->count();
        if ($count === 0) {
            $this->warn("No events found for {$aggregateType}:{$aggregateId} under Tenant {$tenantId}");
            return;
        }

        $this->info("Found {$count} events. Replaying specifically for single aggregate...");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach ($query->cursor() as $event) {
            $projector->handle($event);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Aggregate replay completed successfully.");
    }
}
