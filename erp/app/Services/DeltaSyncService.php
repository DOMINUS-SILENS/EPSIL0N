<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * Delta Sync Service - Efficiently sync only changed data
 * Tracks device sync state and returns minimal data for mobile sync
 */
class DeltaSyncService
{
    /** @var int Default chunk size for large syncs */
    protected int $chunkSize = 500;

    /** @var int Maximum records per sync */
    protected int $maxRecords = 5000;

    /**
     * Get delta for a specific entity type
     *
     * @param string $entity Entity type (orders, customers, articles, etc.)
     * @param int $entrepriseId Company/tenant ID
     * @param string $deviceId Device identifier
     * @param string|null $lastSyncAt Last sync timestamp
     * @param int $limit Max records to return
     * @param string|null $cursor Pagination cursor
     * @return array Delta data with metadata
     */
    public function getDelta(
        string $entity,
        int $entrepriseId,
        string $deviceId,
        ?string $lastSyncAt,
        int $limit = 500,
        ?string $cursor = null
    ): array {
        $limit = min($limit, $this->maxRecords);

        // Get table name and model for entity
        $tableData = $this->getTableForEntity($entity);
        if (!$tableData) {
            return $this->emptyDelta('unknown_entity');
        }

        [$table, $modelClass] = $tableData;

        // Build query
        $query = DB::table($table)
            ->where('entreprise_id', $entrepriseId)
            ->where(function ($q) use ($lastSyncAt) {
                if ($lastSyncAt) {
                    $q->where('updated_at', '>=', $lastSyncAt);
                }
            });

        // Apply cursor pagination
        if ($cursor) {
            $decodedCursor = $this->decodeCursor($cursor);
            if ($decodedCursor) {
                $query->where('id', '>', $decodedCursor['id']);
            }
        }

        // Get records with tombstones for deleted items
        $records = $query->orderBy('id')
            ->limit($limit + 1) // Get one extra to check for more
            ->get();

        $hasMore = $records->count() > $limit;
        if ($hasMore) {
            $records = $records->slice(0, $limit);
        }

        // Transform records
        $items = $records->map(function ($record) {
            $record = (array) $record;

            // Add tombstone flag for deleted records
            if (!empty($record['deleted_at'])) {
                return [
                    'id' => $record['id'],
                    'tombstone' => true,
                    'deleted_at' => $record['deleted_at'],
                ];
            }

            return $record;
        })->toArray();

        // Generate next cursor
        $nextCursor = null;
        if ($hasMore && !empty($items)) {
            $lastItem = end($items);
            $nextCursor = $this->encodeCursor(['id' => $lastItem['id']]);
        }

        // Update sync checkpoint
        $this->updateSyncCheckpoint($deviceId, $entity, $entrepriseId);

        return [
            'entity' => $entity,
            'items' => $items,
            'count' => count($items),
            'has_more' => $hasMore,
            'next_cursor' => $nextCursor,
            'sync_timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Get delta for multiple entities in parallel
     *
     * @param array $entities List of entity types
     * @param int $entrepriseId
     * @param string $deviceId
     * @param string $lastSyncAt
     * @return array
     */
    public function getMultiEntityDelta(
        array $entities,
        int $entrepriseId,
        string $deviceId,
        string $lastSyncAt
    ): array {
        $results = [];
        $hasMoreGlobal = false;
        $nextCursors = [];

        foreach ($entities as $entity) {
            $delta = $this->getDelta($entity, $entrepriseId, $deviceId, $lastSyncAt);
            $results[$entity] = $delta['items'];

            if ($delta['has_more']) {
                $hasMoreGlobal = true;
                $nextCursors[$entity] = $delta['next_cursor'];
            }
        }

        return [
            'data' => $results,
            'meta' => [
                'sync_timestamp' => now()->toIso8601String(),
                'has_more' => $hasMoreGlobal,
                'next_cursors' => $nextCursors,
            ]
        ];
    }

    /**
     * Get sync checkpoint for a device
     */
    public function getSyncCheckpoint(string $deviceId, string $entity, int $entrepriseId): ?array
    {
        $key = "sync_checkpoint:{$entrepriseId}:{$deviceId}:{$entity}";

        return Cache::get($key);
    }

    /**
     * Update sync checkpoint
     */
    public function updateSyncCheckpoint(string $deviceId, string $entity, int $entrepriseId): void
    {
        $key = "sync_checkpoint:{$entrepriseId}:{$deviceId}:{$entity}";

        Cache::put($key, [
            'last_sync_at' => now()->toIso8601String(),
            'entity' => $entity,
        ], now()->addDays(7));

        // Also update database for persistence
        DB::table('device_sync_state')->updateOrInsert(
            [
                'entreprise_id' => $entrepriseId,
                'device_id' => $deviceId,
                'entity_type' => $entity,
            ],
            [
                'last_sync_timestamp' => now()->timestamp,
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Get entity summary - counts and last modified
     * Useful for initial sync planning
     */
    public function getEntitySummary(string $entity, int $entrepriseId): array
    {
        $tableData = $this->getTableForEntity($entity);
        if (!$tableData) {
            return ['error' => 'unknown_entity'];
        }

        [$table] = $tableData;

        $total = DB::table($table)
            ->where('entreprise_id', $entrepriseId)
            ->whereNull('deleted_at')
            ->count();

        $lastModified = DB::table($table)
            ->where('entreprise_id', $entrepriseId)
            ->max('updated_at');

        return [
            'entity' => $entity,
            'total_count' => $total,
            'last_modified' => $lastModified,
            'estimated_sync_time_sec' => ceil($total / 100), // Rough estimate
        ];
    }

    /**
     * Get sync plan - which entities need sync
     *
     * @param int $entrepriseId
     * @param string $deviceId
     * @param string $lastFullSync
     * @return array
     */
    public function getSyncPlan(int $entrepriseId, string $deviceId, string $lastFullSync): array
    {
        $entities = ['articles', 'customers', 'orders', 'depots', 'prices'];
        $plan = [];

        foreach ($entities as $entity) {
            $summary = $this->getEntitySummary($entity, $entrepriseId);

            if (isset($summary['last_modified']) && $summary['last_modified'] > $lastFullSync) {
                $plan[] = [
                    'entity' => $entity,
                    'action' => 'delta',
                    'estimated_count' => $summary['total_count'],
                    'last_modified' => $summary['last_modified'],
                ];
            } else {
                $plan[] = [
                    'entity' => $entity,
                    'action' => 'skip',
                    'reason' => 'no_changes',
                ];
            }
        }

        return [
            'plan' => $plan,
            'total_entities_to_sync' => count(array_filter($plan, fn($p) => $p['action'] === 'delta')),
        ];
    }

    /**
     * Get table and model for entity type
     */
    protected function getTableForEntity(string $entity): ?array
    {
        $map = [
            'articles' => ['article', \App\Models\Article::class],
            'orders' => ['orders', \App\Models\Order::class],
            'customers' => ['contacts', \App\Models\Contact::class],
            'depots' => ['depot', \App\Models\Depot::class],
            'prices' => ['article_unite', \App\Models\ArticleUnite::class],
            'movements' => ['article_mouvement', \App\Models\ArticleMovement::class],
        ];

        return $map[$entity] ?? null;
    }

    /**
     * Encode cursor for pagination
     */
    protected function encodeCursor(array $data): string
    {
        return base64_encode(json_encode($data));
    }

    /**
     * Decode cursor
     */
    protected function decodeCursor(string $cursor): ?array
    {
        try {
            return json_decode(base64_decode($cursor), true);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Empty delta response
     */
    protected function emptyDelta(string $reason): array
    {
        return [
            'items' => [],
            'count' => 0,
            'has_more' => false,
            'next_cursor' => null,
            'reason' => $reason,
        ];
    }
}
