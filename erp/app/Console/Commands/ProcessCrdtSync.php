<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Crdt\MergeService;
use Illuminate\Support\Facades\DB;

/**
 * Command to process pending CRDT operations from mobile devices.
 * Merges concurrent updates using CRDT semantics.
 */
class ProcessCrdtSync extends Command
{
    protected $signature = 'crdt:sync {--company= : Specific company to process}';

    protected $description = 'Process pending CRDT operations and merge states';

    public function handle(MergeService $merge): int
    {
        $query = DB::table('crdt_operations')
            ->where('status', 'pending')
            ->orderBy('created_at');

        if ($this->option('company')) {
            $query->where('entreprise_id', $this->option('company'));
        }

        $operations = $query->get();

        if ($operations->isEmpty()) {
            $this->info('No pending CRDT operations.');
            return Command::SUCCESS;
        }

        $this->info("Processing {$operations->count()} CRDT operations...");
        $bar = $this->output->createProgressBar($operations->count());
        $bar->start();

        foreach ($operations as $op) {
            $this->processOperation($merge, $op);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('CRDT sync complete.');

        return Command::SUCCESS;
    }

    protected function processOperation(MergeService $merge, $op): void
    {
        DB::transaction(function () use ($merge, $op) {
            // Get current state
            $currentState = DB::table('crdt_states')
                ->where('entreprise_id', $op->entreprise_id)
                ->where('entity_type', $op->entity_type)
                ->where('entity_id', $op->entity_id)
                ->where('replica_id', $op->replica_id)
                ->first();

            $payload = json_decode($op->payload, true);
            $vectorClock = json_decode($op->vector_clock, true);

            if (!$currentState) {
                // Create new state
                DB::table('crdt_states')->insert([
                    'entreprise_id' => $op->entreprise_id,
                    'entity_type' => $op->entity_type,
                    'entity_id' => $op->entity_id,
                    'replica_id' => $op->replica_id,
                    'vector_clock' => json_encode($vectorClock),
                    'state' => json_encode($payload),
                    'timestamp' => $op->created_at,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                // Merge with existing state based on CRDT type
                $currentVector = json_decode($currentState->vector_clock, true);
                $currentPayload = json_decode($currentState->state, true);

                $mergedState = match ($op->operation_type) {
                    'gc_inc' => [
                        'value' => $merge->mergeGCounter(
                            $currentPayload['value'] ?? [],
                            $payload['value'] ?? []
                        ),
                    ],
                    'pnc_update' => [
                        'value' => $merge->mergePNCounter(
                            $currentPayload['value'] ?? ['p' => [], 'n' => []],
                            $payload['value'] ?? ['p' => [], 'n' => []]
                        ),
                    ],
                    'lww_set' => [
                        'value' => $merge->mergeLWWRegister(
                            $currentPayload['value'] ?? [],
                            $payload['value'] ?? []
                        ),
                    ],
                    default => $payload,
                };

                // Merge vector clocks
                $mergedClock = array_merge($currentVector, $vectorClock);
                foreach ($vectorClock as $node => $time) {
                    $mergedClock[$node] = max($mergedClock[$node] ?? 0, $time);
                }

                DB::table('crdt_states')
                    ->where('id', $currentState->id)
                    ->update([
                        'vector_clock' => json_encode($mergedClock),
                        'state' => json_encode($mergedState),
                        'timestamp' => $op->created_at,
                        'updated_at' => now(),
                    ]);
            }

            // Mark operation as synced
            DB::table('crdt_operations')
                ->where('id', $op->id)
                ->update([
                    'status' => 'synced',
                    'applied_at' => now(),
                ]);
        });
    }
}
