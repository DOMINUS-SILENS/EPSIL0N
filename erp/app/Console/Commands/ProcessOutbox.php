<?php

namespace App\Console\Commands;

use App\Models\DomainOutbox;
use App\Services\ProjectionDispatcher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProcessOutbox extends Command
{
    protected $signature = 'outbox:process {--chunk=100}';
    protected $description = 'Process pending outbox deliveries enforcing strict aggregate Head-of-Line ordering';

    public function handle(ProjectionDispatcher $dispatcher): void
    {
        $chunkSize = (int) $this->option('chunk');
        $processedCount = 0;

        DB::transaction(function () use ($chunkSize, $dispatcher, &$processedCount) {
            // Strict Event Sourcing causality constraint:
            // Fetch only the MIN(sequence) per aggregate to prevent N+1 from executing before N completes or dies.
            $events = DomainOutbox::join('domain_events', 'domain_outbox.event_id', '=', 'domain_events.id')
                ->whereIn('domain_outbox.status', ['pending', 'failed'])
                ->where(function ($q) {
                    $q->whereNull('domain_outbox.next_retry_at')
                      ->orWhere('domain_outbox.next_retry_at', '<=', now());
                })
                ->whereRaw('domain_events.sequence = (
                    SELECT MIN(de2.sequence) 
                    FROM domain_events de2 
                    JOIN domain_outbox do2 ON de2.id = do2.event_id 
                    WHERE de2.aggregate_id = domain_events.aggregate_id 
                    AND do2.status IN ("pending", "failed")
                )')
                ->orderBy('domain_events.sequence')
                ->limit($chunkSize)
                ->lockForUpdate() // Locks the joined rows securely
                ->select(
                    'domain_outbox.*', 
                    'domain_events.aggregate_type', 
                    'domain_events.aggregate_id', 
                    'domain_events.sequence', 
                    'domain_events.event_type', 
                    'domain_events.payload'
                )
                ->get();

            if ($events->isEmpty()) {
                $this->info('No pending events to process.');
                return;
            }

            // Pre-mark processing
            foreach ($events as $event) {
                $outboxRecord = DomainOutbox::find($event->id);
                $outboxRecord->status = 'processing';
                $outboxRecord->save();
            }

            foreach ($events as $event) {
                try {
                    // Reassemble payload safely for Projector
                    $event->payload = is_string($event->payload) ? json_decode($event->payload, true) : $event->payload;
                    $dispatcher->dispatch($event);
                    
                    $outboxRecord = DomainOutbox::find($event->id);
                    $outboxRecord->status = 'processed';
                    $outboxRecord->processed_at = now();
                    $outboxRecord->save();
                    
                    $processedCount++;
                } catch (Throwable $e) {
                    $failureClass = $this->classifyFailure($e);
                    
                    $outboxRecord = DomainOutbox::find($event->id);
                    $outboxRecord->attempts++;
                    $outboxRecord->last_error = $e->getMessage();
                    
                    $isDeterministic = in_array($failureClass, ['SCHEMA_MISMATCH', 'DETERMINISTIC_CODE']);
                    
                    if ($isDeterministic || $outboxRecord->attempts >= $outboxRecord->max_attempts) {
                        $outboxRecord->status = 'dead';
                        $outboxRecord->save();
                        
                        DB::table('dead_letters')->insert([
                            'event_id' => $event->event_id,
                            'failure_class' => $failureClass,
                            'last_error' => $outboxRecord->last_error,
                            'final_attempts' => $outboxRecord->attempts,
                            'failed_at' => now(),
                        ]);
                        
                        $outboxRecord->delete(); // Unblocks Head-of-Line for next sequence
                        $this->error("Event {$event->id} moved to DEAD LETTER ({$failureClass}). N+1 sequence is now unblocked.");
                    } else {
                        $outboxRecord->status = 'failed';
                        $minutes = pow(2, $outboxRecord->attempts);
                        $outboxRecord->next_retry_at = now()->addMinutes($minutes);
                        $outboxRecord->save();
                        
                        $this->warn("Event {$event->id} failed ({$failureClass}) => queued for retry at {$outboxRecord->next_retry_at}");
                    }
                }
            }
        });

        if ($processedCount > 0) {
            $this->info("Successfully processed {$processedCount} events with pure causal ordering.");
        }
    }

    protected function classifyFailure(Throwable $e): string
    {
        $class = get_class($e);
        
        if (str_contains($class, 'PDOException') || str_contains($class, 'Connection') || str_contains($class, 'TimeoutException')) {
            return 'TRANSIENT_INFRA';
        }
        
        if (str_contains($class, 'SchemaMismatchException') || str_contains($class, 'Deserialization') || str_contains($class, 'TypeError')) {
            return 'SCHEMA_MISMATCH';
        }
        
        if ($e instanceof \InvalidArgumentException || $e instanceof \DomainException || $e instanceof \LogicException) {
            return 'DETERMINISTIC_CODE';
        }

        return 'UNKNOWN_ERROR';
    }
}
