<?php

namespace App\Services;

use App\Models\DomainEvent;
use App\Models\DomainOutbox;
use App\Services\EventStoreService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OutboxService
{
    public function __construct(
        protected EventStoreService $eventStore
    ) {}

    public function publishDomain(
        string $aggregateType, 
        string $aggregateId, 
        string $eventType, 
        array $payload, 
        ?int $entrepriseId = null,
        ?string $correlationId = null,
        ?string $causationId = null,
        int $eventVersion = 1
    ): \App\Models\DomainOutbox {
        // Absolute Crash Consistency Boundary
        // Entire sequence logic, immutable record, and outbox insertion are encapsulated into a singular atomic COMMIT.
        return DB::transaction(function () use ($aggregateType, $aggregateId, $eventType, $payload, $correlationId, $causationId, $eventVersion, $entrepriseId) {
            
            // 2. Canonicalize Payload (ensure it is sharding/hash friendly)
            $canonicalPayload = (array) $payload;

            // 3. Absolute Append-Only State Generation (Event Store as Canonical Source of Truth)
            $eventStoreEntry = $this->eventStore->append(
                $aggregateType,
                $aggregateId,
                $eventType,
                $canonicalPayload,
                ['entreprise_id' => $entrepriseId ?? $payload['entreprise_id'] ?? 1],
                $correlationId,
                $causationId,
                $eventVersion
            );

            // 4. Create the Domain Event (Source of truth for Outbox and Projectors)
            $domainEvent = DomainEvent::create([
                'entreprise_id' => $entrepriseId ?? $payload['entreprise_id'] ?? 1,
                'event_store_id' => $eventStoreEntry->id,
                'aggregate_type' => $aggregateType,
                'aggregate_id' => $aggregateId,
                'sequence' => $eventStoreEntry->local_sequence, // Sybil sequence from sharded store
                'event_type' => $eventType,
                'event_version' => $eventVersion,
                'correlation_id' => $eventStoreEntry->correlation_id,
                'causation_id' => $causationId,
                'payload' => $canonicalPayload,
                'event_time' => now(),
            ]);

            // 5. Proxied Delivery State for Projectors
            $outbox = DomainOutbox::create([
                'event_id' => $domainEvent->id,
                'status' => 'pending',
                'attempts' => 0,
            ]);

            // 6. Real-time Broadcast (Redis Push) AFTER COMMIT ONLY
            DB::afterCommit(function () use ($domainEvent, $aggregateType, $aggregateId, $eventType, $canonicalPayload) {
                \Illuminate\Support\Facades\Redis::publish('events', json_encode([
                    'id' => $domainEvent->id,
                    'aggregate_type' => $aggregateType,
                    'aggregate_id' => $aggregateId,
                    'event_type' => $eventType,
                    'payload' => $canonicalPayload,
                ]));
            });

            // 7. Implicit Commit applies both to EventStore (sharded) and Outbox (dispatch)
            return $outbox;
        }, 5); 
    }

    public function publishIntegration(int $domainEventId, string $integrationType, string $target, array $payload, string $idempotencyKey): \App\Models\IntegrationOutbox
    {
        return \App\Models\IntegrationOutbox::create([
            'domain_event_id' => $domainEventId,
            'integration_type' => $integrationType,
            'target' => $target,
            'payload' => $payload,
            'idempotency_key' => $idempotencyKey,
            'status' => 'pending',
            'attempts' => 0,
        ]);
    }
}
