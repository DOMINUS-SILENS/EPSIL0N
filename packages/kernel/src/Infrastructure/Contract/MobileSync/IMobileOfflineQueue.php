<?php

declare(strict_types=1);

namespace Spiral\Kernel\Infrastructure\Contract\MobileSync;

use Spiral\Kernel\Domain\Identity\DeviceId;
use Spiral\Kernel\Domain\Identity\TenantId;
use Spiral\Kernel\Domain\Sync\SyncVersion;
use Spiral\Kernel\Domain\Sync\SyncStatus;
use Spiral\Kernel\Domain\Shared\Event\DomainEvent;

/**
 * Queue interface for offline mobile event management.
 *
 * Sync Law 2: Every mobile write must be idempotent.
 *
 * IMobileOfflineQueue manages events created while offline:
 * - Stores events locally until sync is possible
 * - Tracks sync status per event
 * - Enables conflict detection via vector clocks
 * - Supports replay with vector clock merge
 *
 * @package Spiral\Kernel\Infrastructure\Contract\MobileSync
 */
interface IMobileOfflineQueue
{
    /**
     * Enqueue an event created offline.
     *
     * Events are stored with their vector clock for later conflict detection.
     * Returns a queue item ID for tracking.
     *
     * @param TenantId $tenantId Tenant for isolation
     * @param DeviceId $deviceId Device that created the event
     * @param DomainEvent $event The event to queue
     * @param SyncVersion $version Vector clock at time of creation
     * @return string Queue item ID
     */
    public function enqueue(
        TenantId $tenantId,
        DeviceId $deviceId,
        DomainEvent $event,
        SyncVersion $version,
    ): string;

    /**
     * Get all pending events for a device.
     *
     * Returns events in causal order (respecting happens-before).
     *
     * @param TenantId $tenantId Tenant for isolation
     * @param DeviceId $deviceId Device to query
     * @return list<QueuedEvent> Pending events
     */
    public function getPendingEvents(TenantId $tenantId, DeviceId $deviceId): array;

    /**
     * Get events by status for a device.
     *
     * @param TenantId $tenantId Tenant for isolation
     * @param DeviceId $deviceId Device to query
     * @param SyncStatus $status Status to filter by
     * @return list<QueuedEvent> Events matching status
     */
    public function getEventsByStatus(
        TenantId $tenantId,
        DeviceId $deviceId,
        SyncStatus $status,
    ): array;

    /**
     * Get the next batch of events ready for sync.
     *
     * Returns events that are pending and have no unresolved dependencies.
     *
     * @param TenantId $tenantId Tenant for isolation
     * @param DeviceId $deviceId Device to query
     * @param int $limit Maximum events to return
     * @return list<QueuedEvent> Events ready for sync
     */
    public function getSyncBatch(
        TenantId $tenantId,
        DeviceId $deviceId,
        int $limit = 50,
    ): array;

    /**
     * Update the status of a queued event.
     *
     * @param string $queueItemId Queue item ID
     * @param SyncStatus $status New status
     * @param string|null $error Optional error message for rejected events
     */
    public function updateStatus(
        string $queueItemId,
        SyncStatus $status,
        ?string $error = null,
    ): void;

    /**
     * Mark an event as synced successfully.
     *
     * @param string $queueItemId Queue item ID
     * @param SyncVersion $mergedVersion Merged vector clock after sync
     */
    public function markSynced(string $queueItemId, SyncVersion $mergedVersion): void;

    /**
     * Mark an event as in conflict.
     *
     * @param string $queueItemId Queue item ID
     * @param array<string, mixed> $conflictData Data about the conflict
     */
    public function markConflict(string $queueItemId, array $conflictData): void;

    /**
     * Remove a successfully synced event from the queue.
     *
     * Called after the event is confirmed persisted on the server.
     *
     * @param string $queueItemId Queue item ID
     */
    public function remove(string $queueItemId): void;

    /**
     * Get the current vector clock for a device.
     *
     * Returns the merged vector clock of all events in the queue.
     *
     * @param TenantId $tenantId Tenant for isolation
     * @param DeviceId $deviceId Device to query
     * @return SyncVersion Current vector clock
     */
    public function getDeviceVersion(TenantId $tenantId, DeviceId $deviceId): SyncVersion;

    /**
     * Get the queue size for a device.
     *
     * @param TenantId $tenantId Tenant for isolation
     * @param DeviceId $deviceId Device to query
     * @return int Number of events in queue
     */
    public function getQueueSize(TenantId $tenantId, DeviceId $deviceId): int;

    /**
     * Check if the queue has any conflicts.
     *
     * @param TenantId $tenantId Tenant for isolation
     * @param DeviceId $deviceId Device to query
     * @return bool True if any events are in conflict
     */
    public function hasConflicts(TenantId $tenantId, DeviceId $deviceId): bool;

    /**
     * Clear all events for a device.
     *
     * Use with caution - typically only for device deregistration.
     *
     * @param TenantId $tenantId Tenant for isolation
     * @param DeviceId $deviceId Device to clear
     */
    public function clear(TenantId $tenantId, DeviceId $deviceId): void;
}
