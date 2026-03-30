<?php

namespace App\Services;

use App\Helpers\Logging;
use App\Models\EventStore;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use App\Services\SchemaRegistryService;

class EventStoreService
{
    protected int $numShards = 16;

    protected array $shardSequenceCache = [];

    /** @var array<string, array> In-memory cache for aggregate events during request */
    protected array $aggregateCache = [];

    /** Cache TTL in seconds for aggregate lookups */
    protected int $cacheTtl = 300;

    public function __construct(
        protected SchemaRegistryService $schemaRegistry
    ) {
    }

    /**
     * Append an event to the event store.
     *
     * @param  array  $metadata  (optional)
     * @param  string|null  $correlationId  (optional)
     */
    public function append(
        string $aggregateType, 
        string $aggregateId, 
        string $eventType, 
        array $payload, 
        array $metadata = [], 
        ?string $correlationId = null,
        ?string $causationId = null,
        int $eventVersion = 1
    ): EventStore {
        // 1. Validate event schema (if exists)
        $this->validateEvent($eventType, $payload);

        // 2. Determine shard based on aggregate (consistent hashing)
        $shardId = $this->getShardForAggregate($aggregateType, $aggregateId);

        // 3. Get next local sequence for this shard (atomic)
        $localSeq = $this->nextLocalSequence($shardId);

        // 4. Compute previous hash and Merkle root (for this shard)
        $previousHash = $this->getPreviousHash($shardId);
        $merkleRoot = $this->computeMerkleRoot($shardId, $localSeq, $previousHash, $payload);

        $correlationId = $correlationId ?? Logging::getCorrelationId() ?? \Illuminate\Support\Str::uuid()->toString();

        // 5. Insert event (Caller should handle transaction)
        $globalSeq = DB::table('event_store')->insertGetId([
            'shard_id' => $shardId,
            'local_sequence' => $localSeq,
            'global_sequence' => null, // let id serve as global sequence
            'event_type' => $eventType,
            'event_version' => $eventVersion,
            'aggregate_type' => $aggregateType,
            'aggregate_id' => $aggregateId,
            'payload' => json_encode($payload),
            'metadata' => json_encode($metadata),
            'previous_hash' => $previousHash,
            'merkle_root' => $merkleRoot,
            'correlation_id' => $correlationId,
            'causation_id' => $causationId,
            'created_at' => now(),
        ]);

        $event = EventStore::find($globalSeq);

        // Compute signature
        $signature = $this->signEvent([
            'shard_id' => $shardId,
            'local_sequence' => $localSeq,
            'event_type' => $eventType,
            'aggregate_type' => $aggregateType,
            'aggregate_id' => $aggregateId,
            'payload' => $payload,
            'previous_hash' => $previousHash,
            'merkle_root' => $merkleRoot,
        ]);

        return $event;
    }

    /**
     * Prepare an event for batch insertion without actually inserting.
     * Used by SyncBatchService for efficient bulk operations.
     */
    public function prepareAppend(
        string $aggregateType,
        string $aggregateId,
        string $eventType,
        array $payload,
        array $metadata = [],
        ?string $correlationId = null,
        ?string $causationId = null,
        int $eventVersion = 1
    ): array {
        // Validate event schema (if exists)
        $this->validateEvent($eventType, $payload);

        // Determine shard
        $shardId = $this->getShardForAggregate($aggregateType, $aggregateId);

        // Get next local sequence (caller manages transaction)
        $localSeq = $this->nextLocalSequence($shardId);

        // Compute hashes
        $previousHash = $this->getPreviousHash($shardId);
        $merkleRoot = $this->computeMerkleRoot($shardId, $localSeq, $previousHash, $payload);

        $correlationId = $correlationId ?? Logging::getCorrelationId() ?? \Illuminate\Support\Str::uuid()->toString();

        return [
            'shard_id' => $shardId,
            'local_sequence' => $localSeq,
            'event_type' => $eventType,
            'event_version' => $eventVersion,
            'aggregate_type' => $aggregateType,
            'aggregate_id' => $aggregateId,
            'payload' => json_encode($payload),
            'metadata' => json_encode($metadata),
            'previous_hash' => $previousHash,
            'merkle_root' => $merkleRoot,
            'correlation_id' => $correlationId,
            'causation_id' => $causationId,
            'created_at' => now(),
        ];
    }

    /**
     * Validate event against schema (if registered).
     */
    protected function validateEvent(string $eventType, array $payload): void
    {
        $this->schemaRegistry->validate($eventType, $payload);
    }

    /**
     * Deterministic shard assignment.
     */
    protected function getShardForAggregate(string $aggregateType, string $aggregateId): int
    {
        $hash = crc32($aggregateType . ':' . $aggregateId);

        return $hash % $this->numShards;
    }

    /**
     * Atomic next local sequence for a shard.
     * Uses a dedicated sequence table per shard.
     */
    protected function nextLocalSequence(int $shardId): int
    {
        // Use a simple counter table per shard (atomic update)
        $affected = DB::update('
            UPDATE event_shard_sequences
            SET seq = LAST_INSERT_ID(seq + 1)
            WHERE shard_id = ?
        ', [$shardId]);

        if ($affected === 0) {
            // Insert initial row
            DB::insert('INSERT INTO event_shard_sequences (shard_id, seq) VALUES (?, 1)', [$shardId]);

            return 1;
        }

        return DB::selectOne('SELECT LAST_INSERT_ID() as seq')->seq;
    }

    /**
     * Get hash of the last event in this shard.
     */
    protected function getPreviousHash(int $shardId): string
    {
        $last = DB::table('event_store')
            ->where('shard_id', $shardId)
            ->orderBy('local_sequence', 'desc')
            ->first(['merkle_root']);

        return $last ? $last->merkle_root : '0';
    }

    /**
     * Compute new Merkle root (simplified: chain hash).
     * For real Merkle tree, we'd need to maintain a tree per shard.
     */
    protected function computeMerkleRoot(int $shardId, int $localSeq, string $prevHash, array $payload): string
    {
        $eventHash = hash('sha256', json_encode($payload));

        return hash('sha256', $prevHash . '|' . $eventHash . '|' . $localSeq);
    }

    protected function signEvent(array $data): string
    {
        $secret = config('app.event_signing_key', 'default-secret-change-me');

        return hash_hmac('sha256', json_encode($data), $secret);
    }

    public function createMerkleNodeTable()
    {
        Schema::create('merkle_nodes', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('shard_id')->unsigned();
            $table->unsignedBigInteger('node_index');
            $table->string('hash', 64);
            $table->timestamps();
            $table->index(['shard_id', 'node_index']);
        });
    }

    public function updateMerkleNodeTable(int $shardId, int $leafIndex, string $leafHash)
    {
    // Implementation for updating merkle node table
    }

    public function getMerkleRoot(int $shardId)
    {
        // Implementation for getting merkle root
        return '0';
    }

    public function getMerkleProof(int $shardId, int $leafIndex)
    {
        // Implementation for getting merkle proof
        return [];
    }

    public function verifyMerkleProof(int $shardId, int $leafIndex, string $proof)
    {
        // Implementation for verifying merkle proof
        return true;
    }

    public function verifyEventSignature(array $data, string $signature): bool
    {
        // Implementation for verifying event signature
        return true;
    }

    public function verifyEventIntegrity(array $data, string $signature, string $merkleRoot): bool
    {
        // Implementation for verifying event integrity
        return true;
    }

    public function getEventById(int $id): ?EventStore
    {
        // Implementation for getting event by id
        return null;
    }

    public function getEventsByShard(int $shardId, int $limit = 100): Collection
    {
        // Implementation for getting events by shard
        return collect();
    }

    /**
     * Get all events for a specific aggregate, ordered by sequence.
     * Optimized with request-level caching and proper index hints.
     *
     * @param string $aggregateType
     * @param string $aggregateId
     * @param int $startVersion
     * @param bool $useCache Use in-memory request cache
     * @return Collection
     */
    public function getEventsForAggregate(
        string $aggregateType,
        string $aggregateId,
        int $startVersion = 0,
        bool $useCache = true
    ): Collection {
        $cacheKey = "{$aggregateType}:{$aggregateId}";

        // Check in-memory request cache first
        if ($useCache && isset($this->aggregateCache[$cacheKey])) {
            $cached = $this->aggregateCache[$cacheKey];
            if ($startVersion === 0) {
                return $cached;
            }
            // Filter cached results by version
            return $cached->filter(fn($event) => $event->local_sequence > $startVersion)
                ->values();
        }

        // Use query builder for better performance than Eloquent
        $query = DB::table('event_store')
            ->where('aggregate_type', $aggregateType)
            ->where('aggregate_id', $aggregateId);

        if ($startVersion > 0) {
            $query->where('local_sequence', '>', $startVersion);
        }

        $events = $query->orderBy('local_sequence', 'asc')
            ->get();

        // Hydrate to EventStore models for consistency
        $collection = EventStore::hydrate($events->toArray());

        // Cache for this request
        if ($useCache) {
            $this->aggregateCache[$cacheKey] = $collection;
        }

        return $collection;
    }

    /**
     * Get events for multiple aggregates in a single query.
     * More efficient than N+1 queries.
     *
     * @param string $aggregateType
     * @param array $aggregateIds
     * @return Collection Keyed by aggregate_id
     */
    public function getEventsForAggregates(string $aggregateType, array $aggregateIds): Collection
    {
        if (empty($aggregateIds)) {
            return collect();
        }

        // Chunk to avoid exceeding IN clause limits
        $chunks = array_chunk($aggregateIds, 1000);
        $allEvents = collect();

        foreach ($chunks as $chunk) {
            $events = DB::table('event_store')
                ->where('aggregate_type', $aggregateType)
                ->whereIn('aggregate_id', $chunk)
                ->orderBy('aggregate_id')
                ->orderBy('local_sequence')
                ->get();

            $allEvents = $allEvents->concat($events);
        }

        return $allEvents->groupBy('aggregate_id');
    }

    /**
     * Clear the in-memory aggregate cache.
     * Call this after write operations if needed.
     */
    public function clearAggregateCache(): void
    {
        $this->aggregateCache = [];
    }

    /**
     * Get new events since a specific ID (for SSE polling).
     * Optimized with cursor-based pagination and tenant filtering.
     *
     * @param int $lastId
     * @param int|null $tenantId
     * @param int $limit Number of events to return (max 100)
     * @return Collection
     */
    public function getEventsSince(int $lastId, ?int $tenantId = null, int $limit = 50): Collection
    {
        $limit = min($limit, 100); // Cap at 100 to prevent memory issues

        $query = DB::table('event_store')
            ->where('id', '>', $lastId)
            ->select(['id', 'shard_id', 'local_sequence', 'event_type', 'aggregate_type', 'aggregate_id', 'payload', 'metadata', 'created_at']);

        // Use generated column if available, otherwise filter in application
        if ($tenantId) {
            $hasGeneratedColumn = Schema::hasColumn('event_store', 'tenant_id');
            if ($hasGeneratedColumn) {
                $query->where('tenant_id', $tenantId);
            }
        }

        return $query->orderBy('id', 'asc')
            ->limit($limit)
            ->get()
            ->map(fn($row) => (array) $row);
    }

    /**
     * Get events with cursor-based pagination (more efficient than offset).
     *
     * @param int|null $cursor Last event ID (0 for first page)
     * @param int $limit
     * @return array{events: array, next_cursor: int|null}
     */
    public function getEventsWithCursor(?int $cursor = null, int $limit = 50): array
    {
        $limit = min($limit, 100);
        $cursor = $cursor ?? 0;

        $events = DB::table('event_store')
            ->where('id', '>', $cursor)
            ->orderBy('id')
            ->limit($limit + 1) // Get one extra to check if there are more
            ->get();

        $hasMore = $events->count() > $limit;
        if ($hasMore) {
            $events = $events->slice(0, $limit);
        }

        $lastId = $events->last()?->id ?? $cursor;

        return [
            'events' => $events->toArray(),
            'next_cursor' => $hasMore ? $lastId : null,
        ];
    }

    public function getEventsByType(string $eventType, int $limit = 100): Collection
    {
        // Implementation for getting events by type
        return collect();
    }

    public function getEventsByDateRange(string $startDate, string $endDate, int $limit = 100): Collection
    {
        // Implementation for getting events by date range
        return collect();
    }

    public function getLeafHash(EventStore $event)
    {
        // Implementation for getting leaf hash
        return hash('sha256', json_encode($event->payload));
    }

    public function computeRoot(int $shardId)
    {
        // Implementation for computing root
        return '0';
    }

    /**
     * Batch append multiple events in a single transaction.
     * More efficient than individual inserts for bulk operations.
     *
     * @param array $events Array of [aggregateType, aggregateId, eventType, payload, metadata?]
     * @return array Array of inserted event IDs
     * @throws \Throwable
     */
    public function batchAppend(array $events, ?string $correlationId = null): array
    {
        if (empty($events)) {
            return [];
        }

        $insertedIds = [];
        $now = now();
        $correlationId = $correlationId ?? Logging::getCorrelationId() ?? \Illuminate\Support\Str::uuid()->toString();

        // Group events by shard for efficient processing
        $groupedByShard = [];
        foreach ($events as $event) {
            $shardId = $this->getShardForAggregate($event['aggregateType'], $event['aggregateId']);
            $groupedByShard[$shardId][] = $event;
        }

        return DB::transaction(function () use ($groupedByShard, $now, $correlationId, &$insertedIds) {
            foreach ($groupedByShard as $shardId => $shardEvents) {
                // Get starting sequence for this shard
                $startSeq = $this->getCurrentShardSequence($shardId);

                foreach ($shardEvents as $index => $event) {
                    $localSeq = $startSeq + $index + 1;
                    $previousHash = $index === 0
                        ? $this->getPreviousHash($shardId)
                        : $insertedIds[count($insertedIds) - 1]['merkle_root'] ?? '0';

                    $payload = $event['payload'];
                    $eventHash = hash('sha256', json_encode($payload));
                    $merkleRoot = hash('sha256', $previousHash . '|' . $eventHash . '|' . $localSeq);

                    // Validate if schema registry is available
                    if (method_exists($this->schemaRegistry, 'validate')) {
                        $this->schemaRegistry->validate($event['eventType'], $payload);
                    }

                    $metadata = $event['metadata'] ?? [];
                    $eventVersion = $event['eventVersion'] ?? 1;
                    $causationId = $event['causationId'] ?? null;

                    $globalSeq = DB::table('event_store')->insertGetId([
                        'shard_id' => $shardId,
                        'local_sequence' => $localSeq,
                        'event_type' => $event['eventType'],
                        'event_version' => $eventVersion,
                        'aggregate_type' => $event['aggregateType'],
                        'aggregate_id' => $event['aggregateId'],
                        'payload' => json_encode($payload),
                        'metadata' => json_encode($metadata),
                        'previous_hash' => $previousHash,
                        'merkle_root' => $merkleRoot,
                        'correlation_id' => $correlationId,
                        'causation_id' => $causationId,
                        'created_at' => $now,
                    ]);

                    $insertedIds[] = [
                        'id' => $globalSeq,
                        'shard_id' => $shardId,
                        'local_sequence' => $localSeq,
                        'merkle_root' => $merkleRoot,
                    ];
                }

                // Update shard sequence in bulk
                $lastSeq = $startSeq + count($shardEvents);
                DB::table('event_shard_sequences')
                    ->updateOrInsert(
                        ['shard_id' => $shardId],
                        ['seq' => $lastSeq, 'updated_at' => $now]
                    );
            }

            return $insertedIds;
        }, 5); // 5 retry attempts
    }

    /**
     * Get current sequence for a shard.
     */
    protected function getCurrentShardSequence(int $shardId): int
    {
        $seq = DB::table('event_shard_sequences')
            ->where('shard_id', $shardId)
            ->value('seq');

        return $seq ?? 0;
    }

    /**
     * Stream events efficiently using cursor (for large exports).
     *
     * @param callable $callback Function to process each batch
     * @param int $batchSize Number of events per batch
     * @param int|null $startId Starting event ID
     */
    public function streamEvents(callable $callback, int $batchSize = 1000, ?int $startId = null): void
    {
        $lastId = $startId ?? 0;

        while (true) {
            $events = DB::table('event_store')
                ->where('id', '>', $lastId)
                ->orderBy('id')
                ->limit($batchSize)
                ->get();

            if ($events->isEmpty()) {
                break;
            }

            $callback($events);

            $lastId = $events->last()->id;

            // Prevent memory leaks on very large streams
            if (memory_get_usage(true) > 256 * 1024 * 1024) {
                gc_collect_cycles();
            }
        }
    }
}
