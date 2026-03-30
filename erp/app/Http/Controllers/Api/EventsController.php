<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\EventStoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Events Controller - Optimized for SFA Mobile Sync
 *
 * Provides both polling and streaming endpoints for event synchronization.
 * Replaces blocking Redis subscribe with efficient polling for better scalability.
 */
class EventsController extends Controller
{
    /** @var int Maximum number of events per poll request */
    protected int $maxEventsPerRequest = 100;

    /** @var int SSE long-poll timeout in seconds */
    protected int $sseTimeout = 25;

    /** @var int Cache TTL for event metadata */
    protected int $cacheTtl = 60;

    public function __construct(
        private EventStoreService $eventStore
    ) {}

    /**
     * Polling endpoint - Recommended for mobile sync
     * Efficiently returns events since a given ID with cursor-based pagination
     *
     * GET /api/events/poll?last_event_id=0&limit=50&tenant_id=1
     */
    public function poll(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'last_event_id' => 'integer|min:0',
            'limit' => 'integer|min:1|max:100',
            'tenant_id' => 'integer|nullable',
            'aggregate_type' => 'string|nullable',
            'aggregate_ids' => 'array|nullable',
        ]);

        $lastEventId = $validated['last_event_id'] ?? 0;
        $limit = min($validated['limit'] ?? 50, $this->maxEventsPerRequest);
        $tenantId = $validated['tenant_id'] ?? null;

        // Build optimized query using generated columns
        $query = DB::table('event_store')
            ->where('id', '>', $lastEventId)
            ->select([
                'id', 'shard_id', 'local_sequence', 'event_type',
                'aggregate_type', 'aggregate_id', 'payload', 'metadata',
                'created_at', 'correlation_id'
            ]);

        // Use generated tenant_id column if available
        if ($tenantId && DB::table('information_schema.columns')
            ->where('table_name', 'event_store')
            ->where('column_name', 'tenant_id')
            ->exists()) {
            $query->where('tenant_id', $tenantId);
        }

        // Filter by aggregate type if specified
        if (!empty($validated['aggregate_type'])) {
            $query->where('aggregate_type', $validated['aggregate_type']);
        }

        // Filter by specific aggregates if provided
        if (!empty($validated['aggregate_ids'])) {
            $query->whereIn('aggregate_id', array_slice($validated['aggregate_ids'], 0, 100));
        }

        $events = $query->orderBy('id')
            ->limit($limit + 1) // Get one extra to determine has_more
            ->get();

        $hasMore = $events->count() > $limit;
        if ($hasMore) {
            $events = $events->slice(0, $limit);
        }

        $lastId = $events->last()?->id ?? $lastEventId;

        // Track sync metrics per tenant for monitoring
        if ($tenantId) {
            $this->trackSyncMetrics($tenantId, $events->count());
        }

        return response()->json([
            'events' => $events->map(fn($e) => [
                'id' => $e->id,
                'type' => $e->event_type,
                'aggregate_type' => $e->aggregate_type,
                'aggregate_id' => $e->aggregate_id,
                'payload' => json_decode($e->payload, true),
                'metadata' => json_decode($e->metadata, true),
                'timestamp' => $e->created_at,
                'correlation_id' => $e->correlation_id,
            ]),
            'meta' => [
                'last_event_id' => $lastId,
                'has_more' => $hasMore,
                'count' => $events->count(),
                'next_poll_at' => now()->toIso8601String(),
            ]
        ]);
    }

    /**
     * Long-polling endpoint - Hybrid between polling and streaming
     * Waits for events up to timeout, then returns immediately when events available
     *
     * GET /api/events/long-poll?last_event_id=0&timeout=25
     */
    public function longPoll(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'last_event_id' => 'integer|min:0',
            'timeout' => 'integer|min:1|max:30',
            'tenant_id' => 'integer|nullable',
        ]);

        $lastEventId = $validated['last_event_id'] ?? 0;
        $timeout = min($validated['timeout'] ?? $this->seeTimeout, 30);
        $tenantId = $validated['tenant_id'] ?? null;
        $startTime = time();

        // Poll loop with exponential backoff
        $pollInterval = 100000; // 100ms initial
        $maxInterval = 1000000; // 1s max

        while ((time() - $startTime) < $timeout) {
            $events = $this->fetchEventsSince($lastEventId, $tenantId, 50);

            if ($events->isNotEmpty()) {
                return response()->json([
                    'events' => $events,
                    'meta' => [
                        'last_event_id' => $events->last()['id'] ?? $lastEventId,
                        'wait_time_ms' => (time() - $startTime) * 1000,
                        'has_events' => true,
                    ]
                ]);
            }

            // Exponential backoff
            usleep($pollInterval);
            $pollInterval = min($pollInterval * 1.5, $maxInterval);
        }

        // Timeout reached with no events
        return response()->json([
            'events' => [],
            'meta' => [
                'last_event_id' => $lastEventId,
                'wait_time_ms' => $timeout * 1000,
                'has_events' => false,
                'message' => 'No new events within timeout period'
            ]
        ]);
    }

    /**
     * Streaming endpoint using Server-Sent Events
     * Non-blocking implementation using periodic polling instead of Redis subscribe
     * More scalable for high-concurrency scenarios
     *
     * GET /api/events/stream?last_event_id=0
     */
    public function stream(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'last_event_id' => 'integer|min:0',
            'tenant_id' => 'integer|nullable',
        ]);

        $lastEventId = $validated['last_event_id'] ?? 0;
        $tenantId = $validated['tenant_id'] ?? null;

        $response = new StreamedResponse(function () use ($lastEventId, $tenantId) {
            $currentLastId = $lastEventId;
            $startTime = time();
            $maxExecutionTime = $this->seeTimeout;
            $emptyPollCount = 0;
            $maxEmptyPolls = 100;

            // Send initial connection event
            echo "event: connected\n";
            echo "data: " . json_encode(['time' => now()->toIso8601String()]) . "\n\n";
            ob_flush();
            flush();

            while (true) {
                // Check for connection abort or timeout
                if (connection_aborted() || (time() - $startTime > $maxExecutionTime)) {
                    break;
                }

                // Fetch new events
                $events = $this->fetchEventsSince($currentLastId, $tenantId, 10);

                if ($events->isNotEmpty()) {
                    foreach ($events as $event) {
                        echo "id: " . $event['id'] . "\n";
                        echo "event: " . $event['type'] . "\n";
                        echo "data: " . json_encode($event) . "\n\n";
                        $currentLastId = $event['id'];
                    }
                    ob_flush();
                    flush();
                    $emptyPollCount = 0;
                } else {
                    // Send keepalive comment
                    echo ":heartbeat\n\n";
                    ob_flush();
                    flush();

                    $emptyPollCount++;
                    if ($emptyPollCount >= $maxEmptyPolls) {
                        break; // Too many empty polls, close connection
                    }
                }

                // Short sleep between polls
                usleep(100000); // 100ms
            }
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('X-Accel-Buffering', 'no');
        $response->headers->set('Access-Control-Allow-Origin', '*');

        return $response;
    }

    /**
     * Get latest event ID for a tenant (for initial sync)
     *
     * GET /api/events/latest-id?tenant_id=1
     */
    public function latestEventId(Request $request): \Illuminate\Http\JsonResponse
    {
        $tenantId = $request->input('tenant_id');

        $query = DB::table('event_store');

        if ($tenantId && DB::table('information_schema.columns')
            ->where('table_name', 'event_store')
            ->where('column_name', 'tenant_id')
            ->exists()) {
            $query->where('tenant_id', $tenantId);
        }

        $latestId = $query->max('id') ?? 0;
        $eventCount = $query->clone()->count();

        return response()->json([
            'latest_event_id' => $latestId,
            'total_events' => $eventCount,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Batch sync endpoint - for mobile initial sync or catch-up
     *
     * POST /api/events/batch
     */
    public function batch(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'event_ids' => 'required|array|min:1|max:500',
            'event_ids.*' => 'integer|min:1',
        ]);

        $eventIds = array_unique($validated['event_ids']);

        // Batch fetch in chunks to avoid large IN clauses
        $events = collect();
        foreach (array_chunk($eventIds, 100) as $chunk) {
            $chunkEvents = DB::table('event_store')
                ->whereIn('id', $chunk)
                ->orderBy('id')
                ->get()
                ->map(fn($e) => [
                    'id' => $e->id,
                    'type' => $e->event_type,
                    'aggregate_type' => $e->aggregate_type,
                    'aggregate_id' => $e->aggregate_id,
                    'payload' => json_decode($e->payload, true),
                    'metadata' => json_decode($e->metadata, true),
                    'timestamp' => $e->created_at,
                ]);

            $events = $events->merge($chunkEvents);
        }

        return response()->json([
            'events' => $events->sortBy('id')->values(),
            'requested_count' => count($eventIds),
            'found_count' => $events->count(),
        ]);
    }

    /**
     * Fetch events since a given ID with caching optimization
     */
    protected function fetchEventsSince(int $lastId, ?int $tenantId, int $limit): \Illuminate\Support\Collection
    {
        $cacheKey = "events:{$tenantId}:{$lastId}:{$limit}";

        // Short-circuit cache for very recent events (100ms TTL)
        return Cache::store('array')->remember($cacheKey, 0.1, function () use ($lastId, $tenantId, $limit) {
            $query = DB::table('event_store')
                ->where('id', '>', $lastId)
                ->select([
                    'id', 'event_type', 'aggregate_type', 'aggregate_id',
                    'payload', 'metadata', 'created_at'
                ])
                ->orderBy('id')
                ->limit($limit);

            if ($tenantId) {
                $hasTenantColumn = DB::table('information_schema.columns')
                    ->where('table_name', 'event_store')
                    ->where('column_name', 'tenant_id')
                    ->exists();

                if ($hasTenantColumn) {
                    $query->where('tenant_id', $tenantId);
                }
            }

            return $query->get()->map(fn($e) => [
                'id' => $e->id,
                'type' => $e->event_type,
                'aggregate_type' => $e->aggregate_type,
                'aggregate_id' => $e->aggregate_id,
                'payload' => json_decode($e->payload, true),
                'metadata' => json_decode($e->metadata, true),
                'timestamp' => $e->created_at,
            ]);
        });
    }

    /**
     * Track sync metrics for monitoring
     */
    protected function trackSyncMetrics(int $tenantId, int $eventCount): void
    {
        $key = "sync_metrics:{$tenantId}:" . now()->format('Y-m-d-H');

        Cache::increment("{$key}:requests", 1);
        Cache::increment("{$key}:events", $eventCount);
        Cache::put("{$key}:last_sync", now(), 3600);
    }
}
