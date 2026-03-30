<?php

namespace App\Services;

use App\Models\EventStore;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Logging;

class EventStoreService
{
    protected int $numShards = 16;

    public function __construct(protected
        SequenceService $sequenceService, protected
        SchemaRegistryService $schemaRegistry
        )
    {
    }

    /**
     * Append an event — transaction-agnostic.
     * Call this from within an existing DB::transaction() in OutboxService.
     * Does NOT open its own transaction; the caller owns atomicity.
     */
    public function append(
        string $aggregateType,
        string $aggregateId, // UUID string — no longer int
        string $eventType,
        array $payload,
        array $metadata = [],
        ?string $correlationId = null
        ): EventStore
    {
        // 1. Validate event schema (if registered)
        $this->validateEvent($eventType, $payload);

        // 2. Deterministic shard
        $shardId = $this->getShardForAggregate($aggregateType, $aggregateId);

        // 3. Atomic per-shard sequence (runs inside caller's transaction)
        $localSeq = $this->nextLocalSequence($shardId);

        // 4. Merkle chain
        $previousHash = $this->getPreviousHash($shardId);
        $merkleRoot = $this->computeMerkleRoot($shardId, $localSeq, $previousHash, $payload);

        $correlationId = $correlationId ?? (method_exists(Logging::class , 'getCorrelationId')
            ?\App\Helpers\Logging::getCorrelationId()
            : null);

        // 5. Insert — no wrapping transaction here; caller owns it
        $globalId = DB::table('event_store')->insertGetId([
            'shard_id' => $shardId,
            'local_sequence' => $localSeq,
            'event_type' => $eventType,
            'aggregate_type' => $aggregateType,
            'aggregate_id' => $aggregateId, // VARCHAR now
            'payload' => json_encode($payload),
            'metadata' => json_encode($metadata),
            'previous_hash' => $previousHash,
            'merkle_root' => $merkleRoot,
            'correlation_id' => $correlationId,
            'created_at' => now(),
        ]);

        $event = EventStore::find($globalId);

        // 6. HMAC signature (stored separately or logged — not blocking)
        $event->signature = $this->signEvent([
            'id' => $globalId,
            'shard_id' => $shardId,
            'event_type' => $eventType,
            'merkle_root' => $merkleRoot,
        ]);
        $event->saveQuietly();

        return $event;
    }

    /**
     * Reconstitution: fetch all events for an aggregate ordered by global insert id.
     * Uses global `id` (not local_sequence) so cross-shard ordering is stable.
     */
    public function getEventsForAggregate(string $aggregateType, string $aggregateId): \Illuminate\Support\Collection
    {
        return DB::table('event_store')
            ->where('aggregate_type', $aggregateType)
            ->where('aggregate_id', $aggregateId)
            ->orderBy('id') // global order — do NOT use local_sequence across shards
            ->get();
    }

    /**
     * SSE polling: events after a given global id (used by EventsController).
     */
    public function getEventsSince(int $lastEventId, int $limit = 50): \Illuminate\Support\Collection
    {
        return DB::table('event_store')
            ->where('id', '>', $lastEventId)
            ->orderBy('id')
            ->limit($limit)
            ->get(['id', 'aggregate_type', 'aggregate_id', 'event_type', 'payload', 'metadata', 'created_at']);
    }

    // -------------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------------

    protected function getShardForAggregate(string $aggregateType, string $aggregateId): int
    {
        return abs(crc32($aggregateType . ':' . $aggregateId)) % $this->numShards;
    }

    protected function nextLocalSequence(int $shardId): int
    {
        $affected = DB::update(
            'UPDATE event_shard_sequences SET seq = LAST_INSERT_ID(seq + 1) WHERE shard_id = ?',
        [$shardId]
        );

        if ($affected === 0) {
            DB::insert(
                'INSERT INTO event_shard_sequences (shard_id, seq) VALUES (?, 1)',
            [$shardId]
            );
            return 1;
        }

        return DB::selectOne('SELECT LAST_INSERT_ID() as seq')->seq;
    }

    protected function getPreviousHash(int $shardId): string
    {
        $last = DB::table('event_store')
            ->where('shard_id', $shardId)
            ->orderByDesc('local_sequence')
            ->value('merkle_root');

        return $last ?? str_repeat('0', 64); // genesis hash
    }

    protected function computeMerkleRoot(int $shardId, int $localSeq, string $prevHash, array $payload): string
    {
        $eventHash = hash('sha256', json_encode($payload));
        return hash('sha256', $prevHash . '|' . $eventHash . '|' . $localSeq);
    }

    protected function signEvent(array $data): string
    {
        $secret = config('app.event_signing_key', 'change-me-in-production');
        return hash_hmac('sha256', json_encode($data), $secret);
    }

    protected function validateEvent(string $eventType, array $payload): void
    {
        // Delegates to SchemaRegistryService — no-op if schema not registered
        if (method_exists($this->schemaRegistry, 'validate')) {
            $this->schemaRegistry->validate($eventType, $payload);
        }
    }
}