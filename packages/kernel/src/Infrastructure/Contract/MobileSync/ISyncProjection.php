<?php

declare(strict_types=1);

namespace Spiral\Kernel\Infrastructure\Contract\MobileSync;

use Spiral\Kernel\Domain\Identity\DeviceId;
use Spiral\Kernel\Domain\Identity\TenantId;
use Spiral\Kernel\Domain\Sync\SyncVersion;
use Spiral\Kernel\Domain\Shared\Event\StoredEvent;

/**
 * Projection interface for delta-based mobile sync queries.
 *
 * Sync Law 3: Every mobile read must be cursor-based.
 *
 * ISyncProjection provides efficient delta queries for mobile clients,
 * returning only events that changed since the last sync checkpoint.
 * This minimizes bandwidth and enables offline-first architecture.
 *
 * @package Spiral\Kernel\Infrastructure\Contract\MobileSync
 */
interface ISyncProjection
{
    /**
     * Get all events that have occurred since the given sync version.
     *
     * Uses vector clock comparison to determine what the device hasn't seen.
     * Returns events in causal order (respecting happens-before relationships).
     *
     * @param TenantId $tenantId Tenant for isolation
     * @param DeviceId $deviceId Device requesting the delta
     * @param SyncVersion $sinceVersion Last synced vector clock from this device
     * @param int|null $limit Maximum events to return (null = unlimited)
     * @return list<StoredEvent> Events in causal order
     */
    public function getDeltaSince(
        TenantId $tenantId,
        DeviceId $deviceId,
        SyncVersion $sinceVersion,
        ?int $limit = null,
    ): array;

    /**
     * Get the current sync version for an aggregate stream.
     *
     * Used by mobile clients to quickly detect if they're out of sync
     * without fetching the entire event stream.
     *
     * @param TenantId $tenantId Tenant for isolation
     * @param string $aggregateType Aggregate type (e.g., "Order", "Customer")
     * @param string $aggregateId Aggregate ID
     * @return SyncVersion Current vector clock for this aggregate
     */
    public function getSyncVersion(
        TenantId $tenantId,
        string $aggregateType,
        string $aggregateId,
    ): SyncVersion;

    /**
     * Get a hash of the current state for quick conflict detection.
     *
     * Mobile clients can compare this hash with their local state hash
     * to detect conflicts before syncing.
     *
     * @param TenantId $tenantId Tenant for isolation
     * @param string $aggregateType Aggregate type
     * @param string $aggregateId Aggregate ID
     * @return string SHA-256 hash of the aggregate state
     */
    public function getStateHash(
        TenantId $tenantId,
        string $aggregateType,
        string $aggregateId,
    ): string;

    /**
     * Get events for multiple aggregates in a single query.
     *
     * Efficient batch sync for mobile clients with multiple pending aggregates.
     *
     * @param TenantId $tenantId Tenant for isolation
     * @param DeviceId $deviceId Device requesting the delta
     * @param SyncVersion $sinceVersion Last synced vector clock
     * @param list<array{type: string, id: string}> $aggregates Aggregates to query
     * @return list<StoredEvent> Events in causal order
     */
    public function getBatchDelta(
        TenantId $tenantId,
        DeviceId $deviceId,
        SyncVersion $sinceVersion,
        array $aggregates,
    ): array;

    /**
     * Get the last sync checkpoint for a device.
     *
     * Returns the vector clock that was last acknowledged by this device.
     *
     * @param TenantId $tenantId Tenant for isolation
     * @param DeviceId $deviceId Device to query
     * @return SyncVersion|null Last checkpoint, or null if never synced
     */
    public function getLastCheckpoint(
        TenantId $tenantId,
        DeviceId $deviceId,
    ): ?SyncVersion;

    /**
     * Update the sync checkpoint for a device after successful sync.
     *
     * @param TenantId $tenantId Tenant for isolation
     * @param DeviceId $deviceId Device that synced
     * @param SyncVersion $version New checkpoint version
     */
    public function updateCheckpoint(
        TenantId $tenantId,
        DeviceId $deviceId,
        SyncVersion $version,
    ): void;
}
