<?php

namespace App\Services\Sync;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * Sync Conflict Detector - Detects and resolves conflicts in offline-first sync
 * Handles concurrent modifications from multiple devices
 */
class SyncConflictDetector
{
    /** @var array Conflict strategies */
    public const STRATEGY_CLIENT_WINS = 'client_wins';
    public const STRATEGY_SERVER_WINS = 'server_wins';
    public const STRATEGY_MERGE = 'merge';
    public const STRATEGY_MANUAL = 'manual';

    /**
     * Detect conflicts between incoming events and server state
     *
     * @param array $events Incoming events from client
     * @param int $entrepriseId Company/tenant ID
     * @param string $deviceId Device identifier
     * @return array List of conflicts found
     */
    public function detectConflicts(array $events, int $entrepriseId, string $deviceId): array
    {
        $conflicts = [];

        foreach ($events as $event) {
            $conflict = $this->checkEventConflict($event, $entrepriseId);
            if ($conflict) {
                $conflicts[] = $conflict;
            }
        }

        return $conflicts;
    }

    /**
     * Check if a single event conflicts with server state
     */
    protected function checkEventConflict(array $event, int $entrepriseId): ?array
    {
        // Get the current server state for this aggregate
        $serverState = $this->getServerState(
            $event['aggregateType'],
            $event['aggregateId'],
            $entrepriseId
        );

        if (!$serverState) {
            return null; // No conflict - new entity
        }

        // Check if server has newer version
        $clientTimestamp = $event['occurredAt'] ?? $event['timestamp'] ?? now()->toIso8601String();
        $serverTimestamp = $serverState['updated_at'] ?? $serverState['created_at'] ?? now()->subYear();

        // If server is newer, it's a conflict
        if ($serverTimestamp > $clientTimestamp) {
            return [
                'event_id' => $event['eventId'],
                'aggregate_type' => $event['aggregateType'],
                'aggregate_id' => $event['aggregateId'],
                'event_type' => $event['type'],
                'client_timestamp' => $clientTimestamp,
                'server_timestamp' => $serverTimestamp,
                'server_version' => $serverState['sync_version'] ?? 1,
                'client_version' => $event['payload']['_version'] ?? 1,
                'conflict_type' => $this->classifyConflict($event, $serverState),
            ];
        }

        return null;
    }

    /**
     * Classify the type of conflict
     */
    protected function classifyConflict(array $event, array $serverState): string
    {
        $eventType = $event['type'] ?? '';

        // Field-level conflicts
        if (str_contains($eventType, 'Updated') || str_contains($eventType, 'Modified')) {
            $clientFields = array_keys($event['payload'] ?? []);
            $serverFields = array_keys($serverState);

            $overlappingFields = array_intersect($clientFields, $serverFields);
            if (!empty($overlappingFields)) {
                return 'field_conflict';
            }
        }

        // State transition conflicts
        if (isset($event['payload']['status']) && isset($serverState['status'])) {
            if ($event['payload']['status'] !== $serverState['status']) {
                return 'status_transition_conflict';
            }
        }

        // Delete conflicts
        if (!empty($serverState['deleted_at'])) {
            return 'deleted_on_server';
        }

        return 'concurrent_modification';
    }

    /**
     * Get current server state for an aggregate
     */
    protected function getServerState(string $aggregateType, string $aggregateId, int $entrepriseId): ?array
    {
        $table = $this->getTableForAggregate($aggregateType);
        if (!$table) {
            return null;
        }

        $record = DB::table($table)
            ->where('entreprise_id', $entrepriseId)
            ->where('id', $aggregateId)
            ->first();

        return $record ? (array) $record : null;
    }

    /**
     * Resolve a conflict using specified strategy
     */
    public function resolveConflict(string $eventId, string $strategy, int $entrepriseId): array
    {
        $conflict = DB::table('sync_conflicts')
            ->where('event_id', $eventId)
            ->where('entreprise_id', $entrepriseId)
            ->where('status', 'pending')
            ->first();

        if (!$conflict) {
            return [
                'event_id' => $eventId,
                'status' => 'not_found',
            ];
        }

        $resolution = match ($strategy) {
            self::STRATEGY_CLIENT_WINS => $this->applyClientVersion($conflict),
            self::STRATEGY_SERVER_WINS => $this->applyServerVersion($conflict),
            self::STRATEGY_MERGE => $this->mergeVersions($conflict),
            default => ['status' => 'unknown_strategy'],
        };

        // Update conflict record
        DB::table('sync_conflicts')
            ->where('id', $conflict->id)
            ->update([
                'status' => 'resolved',
                'resolution_strategy' => $strategy,
                'resolved_at' => now(),
            ]);

        return [
            'event_id' => $eventId,
            'status' => 'resolved',
            'strategy' => $strategy,
            'result' => $resolution,
        ];
    }

    /**
     * Apply client version (overwrite server)
     */
    protected function applyClientVersion(object $conflict): array
    {
        // Re-process the client event
        return [
            'action' => 'reprocess_client_event',
            'conflict_id' => $conflict->id,
        ];
    }

    /**
     * Keep server version (ignore client)
     */
    protected function applyServerVersion(object $conflict): array
    {
        // Mark client event as superseded
        return [
            'action' => 'ignore_client_event',
            'reason' => 'server_version_newer',
        ];
    }

    /**
     * Merge client and server versions
     */
    protected function mergeVersions(object $conflict): array
    {
        $clientPayload = json_decode($conflict->client_payload, true);
        $serverPayload = json_decode($conflict->server_payload, true);

        // Simple merge: client wins on overlapping fields
        $merged = array_merge($serverPayload, $clientPayload);

        return [
            'action' => 'merged',
            'merged_payload' => $merged,
        ];
    }

    /**
     * Queue conflict for manual resolution
     */
    public function queueForManualResolution(array $conflict, int $entrepriseId): void
    {
        DB::table('sync_conflicts')->insert([
            'entreprise_id' => $entrepriseId,
            'event_id' => $conflict['event_id'],
            'aggregate_type' => $conflict['aggregate_type'],
            'aggregate_id' => $conflict['aggregate_id'],
            'client_payload' => json_encode($conflict['client_payload'] ?? []),
            'server_payload' => json_encode($conflict['server_payload'] ?? []),
            'client_timestamp' => $conflict['client_timestamp'],
            'server_timestamp' => $conflict['server_timestamp'],
            'conflict_type' => $conflict['conflict_type'],
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Get pending conflicts for a company
     */
    public function getPendingConflicts(int $entrepriseId, int $limit = 50): array
    {
        return DB::table('sync_conflicts')
            ->where('entreprise_id', $entrepriseId)
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get table for aggregate type
     */
    protected function getTableForAggregate(string $aggregateType): ?string
    {
        $map = [
            'Order' => 'orders',
            'Customer' => 'contacts',
            'Article' => 'article',
            'Stock' => 'article_mouvement',
            'Depot' => 'depot',
        ];

        return $map[$aggregateType] ?? null;
    }

    /**
     * Get conflict statistics
     */
    public function getStats(int $entrepriseId): array
    {
        $pending = DB::table('sync_conflicts')
            ->where('entreprise_id', $entrepriseId)
            ->where('status', 'pending')
            ->count();

        $resolved = DB::table('sync_conflicts')
            ->where('entreprise_id', $entrepriseId)
            ->where('status', 'resolved')
            ->where('created_at', '>=', now()->subDay())
            ->count();

        $byType = DB::table('sync_conflicts')
            ->where('entreprise_id', $entrepriseId)
            ->where('status', 'pending')
            ->selectRaw('conflict_type, COUNT(*) as count')
            ->groupBy('conflict_type')
            ->pluck('count', 'conflict_type')
            ->toArray();

        return [
            'pending' => $pending,
            'resolved_24h' => $resolved,
            'by_type' => $byType,
        ];
    }
}
