<?php

namespace App\Services\Sagas;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SagaManager
{
    /**
     * Dispatches an event to the requested saga enforcing strict Causal Locking and Idempotency.
     */
    public function dispatchEvent(string $sagaClass, string $correlationId, \App\Models\DomainEvent $event, string $mode = 'LIVE'): void
    {
        DB::transaction(function () use ($sagaClass, $correlationId, $event, $mode) {
            // Priority 1: Acquire Causal Block
            $record = DB::table('saga_states')
                ->where('saga_type', $sagaClass)
                ->where('correlation_id', $correlationId)
                ->lockForUpdate() // Absolute race condition prevention
                ->first();

            if ($record) {
                // Priority 2: Enforce Idempotency mapping
                if ($record->last_event_id && $record->last_event_id >= $event->id) {
                    return; // Idempotent block.
                }

                $saga = new $sagaClass(
                    $record->saga_id,
                    json_decode($record->state, true) ?? [],
                    $record->status,
                    $record->correlation_id,
                    $record->last_event_id,
                    $record->timeout_at
                );
            } else {
                $saga = new $sagaClass((string) Str::uuid(), [], 'started', $correlationId, null, null);
            }

            if ($saga->isCompleted()) {
                return; // Ignore late events
            }

            $saga->setExecutionMode($mode);
            $saga->handle($event);
            
            // Priority 3: Advance Causal Clock
            $saga->setLastEventId($event->id);

            // Persist the mutated state safely
            DB::table('saga_states')->updateOrInsert(
                ['saga_id' => $saga->getId()],
                $saga->toSnapshot()
            );
        });
    }
}
