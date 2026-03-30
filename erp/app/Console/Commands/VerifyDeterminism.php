<?php

namespace App\Console\Commands;

use App\Models\DomainOutbox;
use App\Services\Projectors\CustomerBalanceProjector;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerifyDeterminism extends Command
{
    protected $signature = 'determinism:verify {aggregate_id?}';

    protected $description = 'Verify that replay produces the same state';

    public function handle(CustomerBalanceProjector $projector)
    {
        $aggregateId = $this->argument('aggregate_id') ?? 1;

        // Get events in original order
        $events = DomainOutbox::where('aggregate_id', $aggregateId)
            ->orderBy('sequence')
            ->get();

        // Compute state by replay
        DB::beginTransaction();
        try {
            foreach ($events as $event) {
                $projector->process($event);
            }
            $replayBalance = DB::table('customer_balance_projections')
                ->where('customer_id', $aggregateId)
                ->value('balance');
            DB::rollBack();
        } catch (\Exception $e) {
            $this->error('Replay failed: '.$e->getMessage());

            return Command::FAILURE;
        }

        // Compare with current state
        $currentBalance = DB::table('customer_balance_projections')
            ->where('customer_id', $aggregateId)
            ->value('balance');

        if ($replayBalance == $currentBalance) {
            $this->info('Determinism verified: replay matches current state.');

            return Command::SUCCESS;
        } else {
            $this->error('Determinism violation: replay balance '.$replayBalance.' != current '.$currentBalance);

            return Command::FAILURE;
        }
    }
}
