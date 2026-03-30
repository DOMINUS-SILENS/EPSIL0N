<?php

namespace App\Console\Commands;

use App\Models\DomainOutbox;
use App\Services\Projectors\CustomerBalanceProjector;
use Illuminate\Console\Command;

class RebuildProjection extends Command
{
    protected $signature = 'projection:rebuild {customer_id?}';

    protected $description = 'Rebuild projection from outbox events';

    public function handle(CustomerBalanceProjector $projector): void
    {
        $customerId = $this->argument('customer_id');
        if ($customerId) {
            $projector->rebuild($customerId);
            $this->info("Rebuilt projection for customer {$customerId}");
        } else {
            // Rebuild for all customers – get distinct aggregate IDs
            $ids = DomainOutbox::where('aggregate_type', 'journal_entry')
                ->distinct('aggregate_id')
                ->pluck('aggregate_id');
            foreach ($ids as $id) {
                $projector->rebuild($id);
            }
            $this->info('Rebuilt projections for all customers.');
        }
    }
}
