<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\IdempotencyService;
use App\Services\SequenceValidator;
use App\Services\EventStoreService;
use App\Services\SyncBatchService;
use App\Services\Sync\SyncConflictDetector;
use App\Models\DomainEvent;
use App\Models\DomainOutbox;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;

/**
 * Sync Controller - Optimized for SFA Mobile Sync
 * Handles batch ingestion from mobile devices with high performance
 */
class SyncController extends Controller
{
    protected int $maxEventsPerBatch = 500;
    protected int $maxPayloadSize = 10 * 1024 * 1024; // 10MB

    public function __construct(
        protected SyncBatchService $syncBatch,
        protected SyncConflictDetector $conflictDetector
    ) {}

    /**
     * Main sync ingestion endpoint - Optimized for batch processing
     *
     * POST /api/sync/ingest
     */
    public function ingest(Request $request)
    {
        // Validate request size
        $contentLength = $request->header('Content-Length');
        if ($contentLength && $contentLength > $this->maxPayloadSize) {
            return response()->json([
                'acked' => false,
                'error' => 'Payload too large',
                'max_size' => $this->maxPayloadSize,
            ], 413);
        }

        $payload = $request->validate([
            'deviceId' => 'required|string|max:255',
            'userId' => 'required|string|max:255',
            'batchId' => 'required|string|max:255',
            'events' => 'required|array|max:' . $this->maxEventsPerBatch,
            'events.*.eventId' => 'required|string|max:255',
            'events.*.aggregateId' => 'required|string|max:255',
            'events.*.aggregateType' => 'required|string|max:100',
            'events.*.sequence' => 'required|integer|min:1',
            'events.*.type' => 'required|string|max:100',
            'events.*.version' => 'integer|min:1',
            'events.*.occurredAt' => 'required|date',
            'events.*.payload' => 'required|array',
            'events.*.causationId' => 'nullable|string',
            'events.*.correlationId' => 'nullable|string',
            'conflictResolution' => 'nullable|array', // For handling conflicts
            'lastSyncAt' => 'nullable|date', // For delta sync hints
        ]);

        $entrepriseId = auth()->user()->entreprise_id ?? 1;

        // Check batch idempotency (fast path)
        $cacheKey = "batch:{$payload['batchId']}";
        if (Cache::has($cacheKey)) {
            return response()->json([
                'acked' => true,
                'note' => 'batch already processed',
                'cached' => true,
            ]);
        }

        // Check for conflicts before processing
        $conflicts = $this->conflictDetector->detectConflicts(
            $payload['events'],
            $entrepriseId,
            $payload['deviceId']
        );

        if (!empty($conflicts)) {
            return response()->json([
                'acked' => false,
                'error' => 'conflicts_detected',
                'conflicts' => $conflicts,
                'resolution_options' => [
                    'client_wins' => 'Use client version',
                    'server_wins' => 'Use server version',
                    'merge' => 'Attempt automatic merge',
                ],
            ], 409);
        }

        // Process batch using optimized service
        try {
            $result = $this->syncBatch->processBatch(
                $payload['events'],
                $payload['deviceId'],
                $payload['userId'],
                $payload['batchId'],
                $entrepriseId
            );

            // Cache batch result briefly
            Cache::put($cacheKey, true, 60);

            return response()->json($result);

        } catch (\Throwable $e) {
            logger()->error('Sync batch failed', [
                'error' => $e->getMessage(),
                'batchId' => $payload['batchId'],
                'deviceId' => $payload['deviceId'],
            ]);

            return response()->json([
                'acked' => false,
                'error' => 'sync_failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delta sync endpoint - Returns only changed data since last sync
     *
     * GET /api/sync/delta?last_sync_at=2024-01-01T00:00:00Z&entity=orders,customers
     */
    public function delta(Request $request)
    {
        $validated = $request->validate([
            'last_sync_at' => 'required|date',
            'entities' => 'required|string', // Comma-separated: orders,customers,articles
            'limit' => 'integer|min:1|max:1000',
            'cursor' => 'nullable|string',
        ]);

        $entrepriseId = auth()->user()->entreprise_id ?? 1;
        $lastSyncAt = $validated['last_sync_at'];
        $entities = explode(',', $validated['entities']);
        $limit = $validated['limit'] ?? 100;
        $cursor = $validated['cursor'] ?? null;

        $results = [];
        $nextCursors = [];

        foreach ($entities as $entity) {
            $delta = $this->getDeltaForEntity($entity, $lastSyncAt, $entrepriseId, $limit, $cursor);
            $results[$entity] = $delta['data'];
            if ($delta['has_more']) {
                $nextCursors[$entity] = $delta['next_cursor'];
            }
        }

        return response()->json([
            'data' => $results,
            'meta' => [
                'sync_time' => now()->toIso8601String(),
                'has_more' => !empty($nextCursors),
                'next_cursors' => $nextCursors,
            ]
        ]);
    }

    /**
     * Conflict resolution endpoint
     *
     * POST /api/sync/resolve-conflicts
     */
    public function resolveConflicts(Request $request)
    {
        $validated = $request->validate([
            'resolutions' => 'required|array',
            'resolutions.*.eventId' => 'required|string',
            'resolutions.*.strategy' => 'required|in:client_wins,server_wins,merge',
        ]);

        $entrepriseId = auth()->user()->entreprise_id ?? 1;
        $results = [];

        foreach ($validated['resolutions'] as $resolution) {
            $result = $this->conflictDetector->resolveConflict(
                $resolution['eventId'],
                $resolution['strategy'],
                $entrepriseId
            );
            $results[] = $result;
        }

        return response()->json([
            'resolved' => $results,
        ]);
    }

    /**
     * Sync status endpoint - Check sync health
     *
     * GET /api/sync/status
     */
    public function status(Request $request)
    {
        $entrepriseId = auth()->user()->entreprise_id ?? 1;
        $deviceId = $request->query('deviceId');

        // Get last sync timestamp for device
        $lastSync = $deviceId ? Cache::get("device:last_sync:{$deviceId}") : null;

        // Get queue stats
        $pendingOutbox = DomainOutbox::where('status', 'pending')->count();
        $failedOutbox = DomainOutbox::where('status', 'failed')->count();

        // Get recent event count
        $recentEvents = DomainEvent::where('tenant_id', $entrepriseId)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        return response()->json([
            'status' => 'healthy',
            'last_server_sync' => now()->toIso8601String(),
            'device_last_sync' => $lastSync,
            'pending_events' => $pendingOutbox,
            'failed_events' => $failedOutbox,
            'recent_events_1h' => $recentEvents,
            'estimated_lag_ms' => $pendingOutbox > 0 ? $pendingOutbox * 50 : 0,
        ]);
    }

    /**
     * Resume sync endpoint - Get events after a specific checkpoint
     *
     * GET /api/sync/resume?last_event_id=12345&limit=100
     */
    public function resume(Request $request)
    {
        $validated = $request->validate([
            'last_event_id' => 'required|integer|min:0',
            'limit' => 'integer|min:1|max:500',
        ]);

        $entrepriseId = auth()->user()->entreprise_id ?? 1;
        $lastEventId = $validated['last_event_id'];
        $limit = $validated['limit'] ?? 100;

        $events = DomainEvent::where('tenant_id', $entrepriseId)
            ->where('id', '>', $lastEventId)
            ->with('eventStore')
            ->orderBy('id')
            ->limit($limit + 1)
            ->get();

        $hasMore = $events->count() > $limit;
        if ($hasMore) {
            $events = $events->slice(0, $limit);
        }

        $lastId = $events->last()?->id ?? $lastEventId;

        return response()->json([
            'events' => $events->map(fn($e) => [
                'id' => $e->id,
                'type' => $e->event_type,
                'aggregate_type' => $e->aggregate_type,
                'aggregate_id' => $e->aggregate_id,
                'payload' => $e->payload,
                'timestamp' => $e->event_time,
            ]),
            'meta' => [
                'last_event_id' => $lastId,
                'has_more' => $hasMore,
                'count' => $events->count(),
            ]
        ]);
    }

    /**
     * Legacy ingest method - kept for backward compatibility
     * Can be removed once all clients migrate to new endpoint
     */
    public function ingestLegacy(
        Request $request,
        IdempotencyService $idempotency,
        SequenceValidator $seqValidator,
        EventStoreService $eventStore
    ) {
        $payload = $request->validate([
            'deviceId' => 'required|string',
            'userId' => 'required|string',
            'batchId' => 'required|string',
            'events' => 'required|array',
            'events.*.eventId' => 'required|string',
            'events.*.aggregateId' => 'required|string',
            'events.*.aggregateType' => 'required|string',
            'events.*.sequence' => 'required|integer',
            'events.*.type' => 'required|string',
            'events.*.occurredAt' => 'required|date',
            'events.*.payload' => 'required|array',
        ]);

        $entrepriseId = auth()->user()->entreprise_id ?? 1;

        // Global batch idempotency check
        if ($idempotency->exists($payload['batchId'])) {
            return response()->json(['acked' => true, 'note' => 'batch already processed']);
        }

        $results = [];

        foreach ($payload['events'] as $event) {
            // Event-level idempotency
            if ($idempotency->exists($event['eventId'])) {
                $results[] = ['eventId' => $event['eventId'], 'status' => 'ALREADY_ACKNOWLEDGED'];
                continue;
            }

            // Strict Causal Sequence Validation
            if (!$seqValidator->isValid($event['aggregateType'], $event['aggregateId'], (int)$event['sequence'], $entrepriseId)) {
                $results[] = ['eventId' => $event['eventId'], 'status' => 'CAUSALITY_VIOLATION'];
                continue;
            }

            try {
                // Transactional Appending
                DB::transaction(function () use ($event, $entrepriseId, $payload, $eventStore) {
                    $eventVersion = $event['version'] ?? 1;
                    $causationId = $event['causationId'] ?? null;
                    $correlationId = $event['correlationId'] ?? null;

                    $eventStoreEntry = $eventStore->append(
                        $event['aggregateType'],
                        $event['aggregateId'],
                        $event['type'],
                        $event['payload'],
                        ['tenant_id' => $entrepriseId],
                        $correlationId,
                        $causationId,
                        $eventVersion
                    );

                    $domainEvent = DomainEvent::create([
                        'tenant_id' => $entrepriseId,
                        'event_store_id' => $eventStoreEntry->id,
                        'aggregate_type' => $event['aggregateType'],
                        'aggregate_id' => $event['aggregateId'],
                        'sequence' => $event['sequence'],
                        'event_type' => $event['type'],
                        'event_version' => $eventVersion,
                        'causation_id' => $causationId,
                        'correlation_id' => $correlationId,
                        'payload' => $event['payload'],
                        'event_time' => $event['occurredAt'],
                        'source_device_id' => $payload['deviceId'],
                        'source_user_id' => $payload['userId'],
                    ]);

                    DomainOutbox::create([
                        'event_id' => $domainEvent->id,
                        'status' => 'pending',
                        'attempts' => 0,
                    ]);

                    Redis::publish('events', json_encode([
                        'id' => $domainEvent->id,
                        'aggregate_type' => $event['aggregateType'],
                        'aggregate_id' => $event['aggregateId'],
                        'event_type' => $event['type'],
                        'payload' => $event['payload'],
                    ]));
                }, 5);

                $idempotency->record($event['eventId']);
                $results[] = ['eventId' => $event['eventId'], 'status' => 'ACCEPTED'];

            } catch (\DomainException | \InvalidArgumentException | \LogicException $e) {
                $results[] = ['eventId' => $event['eventId'], 'status' => 'LATE_SEMANTIC_INVALID', 'reason' => $e->getMessage()];
            } catch (\Throwable $e) {
                if (str_contains(get_class($e), 'SchemaMismatch')) {
                    $results[] = ['eventId' => $event['eventId'], 'status' => 'SCHEMA_INVALID', 'reason' => $e->getMessage()];
                } elseif (str_contains($e->getMessage(), 'Authorization') || str_contains($e->getMessage(), 'Authority')) {
                    $results[] = ['eventId' => $event['eventId'], 'status' => 'AUTHORITY_CONFLICT', 'reason' => $e->getMessage()];
                } else {
                    throw $e;
                }
            }
        }

        $idempotency->record($payload['batchId']);

        return response()->json([
            'acked' => true,
            'results' => $results
        ]);
    }

    /**
     * Get sync plan - Returns sync strategy and entity metadata
     *
     * GET /api/sync/plan
     */
    public function plan(Request $request)
    {
        $validated = $request->validate([
            'device_id' => 'nullable|string|max:255',
            'capabilities' => 'nullable|array', // Device capabilities (compression, batch_size, etc.)
        ]);

        $entrepriseId = auth()->user()->entreprise_id ?? 1;

        // Get entity counts and last sync info
        $entities = [
            'articles' => [
                'sync_priority' => 'high',
                'sync_mode' => 'delta',
                'estimated_count' => \App\Models\Article::where('entreprise_id', $entrepriseId)->count(),
                'last_modified' => \App\Models\Article::where('entreprise_id', $entrepriseId)->max('updated_at'),
            ],
            'customers' => [
                'sync_priority' => 'high',
                'sync_mode' => 'delta',
                'estimated_count' => \App\Models\Contact::where('entreprise_id', $entrepriseId)->count(),
                'last_modified' => \App\Models\Contact::where('entreprise_id', $entrepriseId)->max('updated_at'),
            ],
            'orders' => [
                'sync_priority' => 'critical',
                'sync_mode' => 'bidirectional',
                'estimated_count' => \App\Models\Order::where('entreprise_id', $entrepriseId)->count(),
                'last_modified' => \App\Models\Order::where('entreprise_id', $entrepriseId)->max('updated_at'),
            ],
            'depots' => [
                'sync_priority' => 'medium',
                'sync_mode' => 'delta',
                'estimated_count' => \App\Models\Depot::where('entreprise_id', $entrepriseId)->count(),
                'last_modified' => \App\Models\Depot::where('entreprise_id', $entrepriseId)->max('updated_at'),
            ],
        ];

        // Calculate recommended batch sizes based on device capabilities
        $deviceCapabilities = $validated['capabilities'] ?? [];
        $maxBatchSize = $deviceCapabilities['max_batch_size'] ?? 100;
        $supportsCompression = $deviceCapabilities['compression'] ?? false;

        return response()->json([
            'sync_plan' => [
                'version' => '2.0',
                'strategy' => 'delta_first',
                'entities' => $entities,
                'recommendations' => [
                    'batch_size' => min($maxBatchSize, $this->maxEventsPerBatch),
                    'use_compression' => $supportsCompression,
                    'sync_order' => ['orders', 'articles', 'customers', 'depots'],
                    'polling_interval_seconds' => 30,
                    'max_retry_attempts' => 3,
                ],
                'server_time' => now()->toIso8601String(),
            ]
        ]);
    }

    /**
     * Get entity summary for sync planning
     *
     * GET /api/sync/summary/{entity}
     */
    public function entitySummary(Request $request, string $entity)
    {
        $validated = $request->validate([
            'since' => 'nullable|date',
        ]);

        $entrepriseId = auth()->user()->entreprise_id ?? 1;
        $since = $validated['since'] ?? now()->subDay();

        $model = $this->getModelForEntity($entity);

        if (!$model) {
            return response()->json([
                'error' => 'Unknown entity type',
                'valid_entities' => ['articles', 'orders', 'customers', 'depots'],
            ], 404);
        }

        $companyColumn = method_exists($model, 'getCompanyColumn')
            ? $model->getCompanyColumn()
            : (str_contains($model, 'Article') || str_contains($model, 'Depot') || str_contains($model, 'Contact')
                ? 'entreprise_id'
                : 'entreprise_id');

        $totalCount = $model::where($companyColumn, $entrepriseId)->count();
        $modifiedSince = $model::where($companyColumn, $entrepriseId)
            ->where('updated_at', '>=', $since)
            ->count();

        $lastModified = $model::where($companyColumn, $entrepriseId)->max('updated_at');
        $newestId = $model::where($companyColumn, $entrepriseId)->max('id');

        // Calculate data size estimate (rough approximation)
        $sampleSize = min(10, $modifiedSince);
        $avgSize = 0;
        if ($sampleSize > 0) {
            $samples = $model::where($companyColumn, $entrepriseId)
                ->orderBy('id', 'desc')
                ->limit($sampleSize)
                ->get();
            $totalSize = $samples->sum(fn($item) => strlen(json_encode($item)));
            $avgSize = $totalSize / $sampleSize;
        }

        return response()->json([
            'entity' => $entity,
            'summary' => [
                'total_count' => $totalCount,
                'modified_since' => $modifiedSince,
                'last_modified' => $lastModified,
                'newest_id' => $newestId,
                'estimated_size_bytes' => $modifiedSince * $avgSize,
                'since' => $since,
            ],
            'sync_recommendation' => $modifiedSince > 1000 ? 'use_delta' : 'full_sync',
        ]);
    }

    /**
     * Get delta data for a specific entity type
     */
    protected function getDeltaForEntity(string $entity, string $lastSyncAt, int $entrepriseId, int $limit, ?string $cursor): array
    {
        $model = $this->getModelForEntity($entity);

        if (!$model) {
            return ['data' => [], 'has_more' => false, 'next_cursor' => null];
        }

        $query = $model::query()
            ->where('entreprise_id', $entrepriseId)
            ->where('updated_at', '>', $lastSyncAt);

        if ($cursor) {
            $query->where('id', '>', $cursor);
        }

        $data = $query->orderBy('id')
            ->limit($limit + 1)
            ->get();

        $hasMore = $data->count() > $limit;
        if ($hasMore) {
            $data = $data->slice(0, $limit);
        }

        return [
            'data' => $data->map->toArray(),
            'has_more' => $hasMore,
            'next_cursor' => $hasMore ? $data->last()->id : null,
        ];
    }

    /**
     * Get model class for entity type
     */
    protected function getModelForEntity(string $entity): ?string
    {
        $map = [
            'articles' => \App\Models\Article::class,
            'orders' => \App\Models\Order::class,
            'customers' => \App\Models\Contact::class,
            'depots' => \App\Models\Depot::class,
        ];

        return $map[$entity] ?? null;
    }
}
