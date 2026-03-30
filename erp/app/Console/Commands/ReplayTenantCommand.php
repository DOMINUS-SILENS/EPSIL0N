<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DomainEvent;
use Illuminate\Support\Facades\DB;

class ReplayTenantCommand extends Command
{
    protected $signature = 'replay:tenant 
                            {tenant_id : The ID of the tenant to rebuild}
                            {projector : The fully qualified class name of the projector}
                            {--full : Obliterates existing state and rebuilds from Genesis (Event 0)}';

    protected $description = 'Enterprise Replay Governance: Rebuilds projector state deterministically for an entire tenant';

    public function handle(): void
    {
        $tenantId = $this->argument('tenant_id');
        $projectorClass = $this->argument('projector');
        $isFull = $this->option('full');

        if (!class_exists($projectorClass)) {
            $this->error("Projector {$projectorClass} does not exist.");
            return;
        }

        /** @var \App\Services\Projector $projector */
        $projector = app($projectorClass);

        if ($isFull) {
            $this->warn("DANGER: Executing Full Structural Reset for {$projectorClass} on Tenant {$tenantId}");
            if (!$this->confirm('Are you absolutely sure you want to obliterate and rebuild this read model?')) {
                return;
            }
            $projector->resetState();
            $this->info("Projector state reset. Commencing replay from Genesis...");
        }

        $query = DomainEvent::where('tenant_id', $tenantId)->orderBy('sequence', 'asc');
        
        $count = $query->count();
        $this->info("Found {$count} events. Replaying...");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach ($query->cursor() as $event) {
            $projector->handle($event);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Replay completed successfully for Tenant {$tenantId}.");
    }
}
