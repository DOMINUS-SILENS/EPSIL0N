<?php

namespace App\Services;

use App\Models\DomainEvent;
use App\Models\DomainOutbox;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

/**
 * Sync Batch Service - Optimized batch processing for mobile sync
 * Processes multiple events in a single transaction for better performance
 */
class SyncBatchService
{
    protected int $batchSize = 100;
    protected int $maxPayloadSize = 10 * 1024 * 1024; // 10MB

    public function __construct(
        protected EventStoreService $eventStore,
        protected IdempotencyService $idempotency,
        protected SequenceValidator $sequenceValidator
    ) {}

    /**
     * Process a batch of sync events atomically with partial success support
     *
     * @param array $events Array of events from mobile device
     * @param string $deviceId Device identifier
     * @param string $userId User identifier
     * @param string $batchId Batch identifier for idempotency
     * @param int $entrepriseId Company/tenant ID
     * @return array Results with accepted/rejected events
     */
    public function processBatch(
        array $events,
        string $deviceId,
        string $userId,
        string $batchId,
        int $entrepriseId
    ): array {
        $results = [];
        $acceptedEvents = [];
        $correlationId = Str::uuid()->toString();

        // Phase 1: Pre-validation (no DB locks)
        $preValidation = $this->preValidateEvents($events, $entrepriseId);

        foreach ($preValidation['results'] as $result) {
            if ($result['status'] !== 'PENDING') {
                $results[] = $result;
            } else {
                $acceptedEvents[] = $result['event'];
            }
        }

        if (empty($acceptedEvents)) {
            return [
                'acked' => true,
                'processed' => 0,
                'results' => $results,
            ];
        }

        // Phase 2: Process accepted events in batches
        $chunks = array_chunk($acceptedEvents, $this->batchSize);
        $processedCount = 0;

        foreach ($chunks as $chunk) {
            try {
                $chunkResults = $this->processChunk(
                    $chunk,
                    $deviceId,
                    $userId,
                    $entrepriseId,
                    $correlationId
                );

                foreach ($chunkResults as $eventId => $status) {
                    $results[] = [
                        'eventId' => $eventId,
                        'status' => $status,
                    ];
                    if ($status === 'ACCEPTED') {
                        $processedCount++;
                    }
                }
            } catch (\Throwable $e) {
                // Log and mark all events in chunk as failed
                logger()->error('Batch sync chunk failed', [
                    'error' => $e->getMessage(),
                    'chunk_size' => count($chunk),
                ]);

                foreach ($chunk as $event) {
                    $results[] = [
                        'eventId' => $event['eventId'],
                        'status' => 'BATCH_FAILED',
                        'reason' => 'Internal server error',
                    ];
                }
            }
        }

        // Mark batch as processed
        $this->idempotency->record($batchId);

        return [
            'acked' => true,
            'processed' => $processedCount,
            'correlation_id' => $correlationId,
            'results' => $results,
        ];
    }

    /**
     * Pre-validate events without database locks
     * Fast checks: idempotency, schema, size
     */
    protected function preValidateEvents(array $events, int $entrepriseId): array
    {
        $results = [];
        $batchIds = array_column($events, 'eventId');

        // Batch check idempotency keys
        $existingKeys = $this->idempotency->filterExisting($batchIds);

        foreach ($events as $event) {
            $eventId = $event['eventId'];

            // Check idempotency
            if (in_array($eventId, $existingKeys)) {
                $results[] = [
                    'eventId' => $eventId,
                    'status' => 'ALREADY_ACKNOWLEDGED',
                    'event' => null,
                ];
                continue;
            }

            // Validate payload size
            $payloadSize = strlen(json_encode($event['payload'] ?? []));
            if ($payloadSize > $this->maxPayloadSize) {
                $results[] = [
                    'eventId' => $eventId,
                    'status' => 'PAYLOAD_TOO_LARGE',
                    'reason' => "Payload size {$payloadSize} exceeds limit",
                    'event' => null,
                ];
                continue;
            }

            // Basic schema validation
            if (!$this->isValidEventSchema($event)) {
                $results[] = [
                    'eventId' => $eventId,
                    'status' => 'SCHEMA_INVALID',
                    'event' => null,
                ];
                continue;
            }

            $results[] = [
                'eventId' => $eventId,
                'status' => 'PENDING',
                'event' => $event,
            ];
        }

        return ['results' => $results];
    }

    /**
     * Process a chunk of events in a single transaction
     */
    protected function processChunk(
        array $events,
        string $deviceId,
        string $userId,
        int $entrepriseId,
        string $correlationId
    ): array {
        $results = [];

        DB::transaction(function () use (
            $events,
            $deviceId,
            $userId,
            $entrepriseId,
            $correlationId,
            &$results
        ) {
            // Build batch data structures
            $eventStoreEntries = [];
            $domainEvents = [];
            $outboxEntries = [];
            $sequenceValidations = [];

            $now = now();

            // Phase 1: Validate sequences (with locking)
            foreach ($events as $event) {
                $isValid = $this->sequenceValidator->isValidAtomic(
                    $event['aggregateType'],
                    $event['aggregateId'],
                    (int) $event['sequence'],
                    $entrepriseId,
                    false // Don't update yet, batch it
                );

                if (!$isValid) {
                    $results[$event['eventId']] = 'CAUSALITY_VIOLATION';
                    continue;
                }

                $sequenceValidations[] = [
                    'aggregateType' => $event['aggregateType'],
                    'aggregateId' => $event['aggregateId'],
                    'sequence' => $event['sequence'],
                ];

                // Prepare event store entry
                $eventStoreEntry = $this->eventStore->prepareAppend(
                    $event['aggregateType'],
                    $event['aggregateId'],
                    $event['type'],
                    $event['payload'],
                    ['tenant_id' => $entrepriseId],
                    $correlationId,
                    $event['causationId'] ?? null,
                    $event['version'] ?? 1
                );

                $eventStoreEntries[] = $eventStoreEntry;
            }

            if (empty($eventStoreEntries)) {
                return;
            }

            // Phase 2: Batch insert to event_store
            $insertedEventIds = [];
            foreach ($eventStoreEntries as $entry) {
                $eventStoreId = DB::table('event_store')->insertGetId($entry);
                $insertedEventIds[] = $eventStoreId;
            }

            // Phase 3: Batch insert domain events
            $domainEventData = [];
            $index = 0;
            foreach ($events as $event) {
                if (!isset($results[$event['eventId']])) {
                    $domainEventData[] = [
                        'tenant_id' => $entrepriseId,
                        'event_store_id' => $insertedEventIds[$index] ?? null,
                        'aggregate_type' => $event['aggregateType'],
                        'aggregate_id' => $event['aggregateId'],
                        'sequence' => $event['sequence'],
                        'event_type' => $event['type'],
                        'event_version' => $event['version'] ?? 1,
                        'causation_id' => $event['causationId'] ?? null,
                        'correlation_id' => $correlationId,
                        'payload' => json_encode($event['payload']),
                        'event_time' => $event['occurredAt'] ?? $now,
                        'source_device_id' => $deviceId,
                        'source_user_id' => $userId,
                        'created_at' => $now,
                    ];
                    $index++;
                }
            }

            if (!empty($domainEventData)) {
                DB::table('domain_events')->insert($domainEventData);

                // Get the inserted IDs
                $firstId = DB::table('domain_events')
                    ->where('correlation_id', $correlationId)
                    ->min('id');
                $lastId = DB::table('domain_events')
                    ->where('correlation_id', $correlationId)
                    ->max('id');

                // Phase 4: Batch insert outbox entries
                $outboxData = [];
                for ($i = $firstId; $i <= $lastId; $i++) {
                    $outboxData[] = [
                        'event_id' => $i,
                        'status' => 'pending',
                        'attempts' => 0,
                        'created_at' => $now,
                    ];
                }

                if (!empty($outboxData)) {
                    DB::table('domain_outbox')->insert($outboxData);
                }
            }

            // Phase 5: Update sequences in batch
            $this->sequenceValidator->updateSequencesBatch($sequenceValidations, $entrepriseId);

            // Phase 6: Record idempotency keys
            foreach ($events as $event) {
                if (!isset($results[$event['eventId']])) {
                    $this->idempotency->record($event['eventId']);
                    $results[$event['eventId']] = 'ACCEPTED';
                }
            }

            // Phase 7: Redis publish AFTER COMMIT ONLY
            DB::afterCommit(function () use ($events, $correlationId) {
                $this->publishToRedis($events, $correlationId);
            });
        }, 3); // 3 retry attempts

        return $results;
    }

    /**
     * Publish events to Redis for real-time subscribers
     */
    protected function publishToRedis(array $events, string $correlationId): void
    {
        try {
            $pipeline = Redis::pipeline();

            foreach ($events as $event) {
                $pipeline->publish('events', json_encode([
                    'aggregate_type' => $event['aggregateType'],
                    'aggregate_id' => $event['aggregateId'],
                    'event_type' => $event['type'],
                    'payload' => $event['payload'],
                    'correlation_id' => $correlationId,
                ]));
            }

            $pipeline->execute();
        } catch (\Throwable $e) {
            // Log but don't fail the batch
            logger()->warning('Redis publish failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
        }
    }

    /**
     * Basic event schema validation
     */
    protected function isValidEventSchema(array $event): bool
    {
        $required = ['eventId', 'aggregateId', 'aggregateType', 'type', 'payload'];
        foreach ($required as $field) {
            if (!isset($event[$field])) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get sync statistics for a company
     */
    public function getSyncStats(int $entrepriseId, ?\DateTime $since = null): array
    {
        $since = $since ?? now()->subDay();

        return [
            'total_events' => DomainEvent::where('tenant_id', $entrepriseId)
                ->where('created_at', '>=', $since)
                ->count(),
            'pending_outbox' => DomainOutbox::where('status', 'pending')
                ->where('created_at', '>=', $since)
                ->count(),
            'failed_outbox' => DomainOutbox::where('status', 'failed')
                ->where('created_at', '>=', $since)
                ->count(),
            'by_device' => DomainEvent::where('tenant_id', $entrepriseId)
                ->where('created_at', '>=', $since)
                ->selectRaw('source_device_id, COUNT(*) as count')
                ->groupBy('source_device_id')
                ->pluck('count', 'source_device_id')
                ->toArray(),
        ];
    }
}
