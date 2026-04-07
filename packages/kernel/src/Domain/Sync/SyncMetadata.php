<?php

declare(strict_types=1);

namespace Spiral\Kernel\Domain\Sync;

use Spiral\Kernel\Domain\Identity\DeviceId;
use DateTimeImmutable;

/**
 * Metadata envelope for offline mobile sync operations.
 *
 * SyncMetadata captures the context needed for conflict detection,
 * resolution, and audit trails in offline-first scenarios.
 *
 * Every event created offline should carry SyncMetadata to enable:
 * - Device attribution (which device created this event)
 * - Vector clock ordering (causal relationships)
 * - Offline timing (when it was created vs when synced)
 * - Sync status tracking (pending, synced, conflict)
 *
 * @package Spiral\Kernel\Domain\Sync
 */
final class SyncMetadata
{
    public function __construct(
        public readonly DeviceId $deviceId,
        public readonly SyncVersion $syncVersion,
        public readonly DateTimeImmutable $offlineCreatedAt,
        public readonly ?DateTimeImmutable $syncedAt = null,
        public readonly SyncStatus $status = SyncStatus::Pending,
    ) {}

    /**
     * Create sync metadata for a new offline event.
     */
    public static function forOfflineEvent(
        DeviceId $deviceId,
        SyncVersion $syncVersion,
    ): self {
        return new self(
            deviceId: $deviceId,
            syncVersion: $syncVersion,
            offlineCreatedAt: new DateTimeImmutable(),
            syncedAt: null,
            status: SyncStatus::Pending,
        );
    }

    /**
     * Create sync metadata for an event that has been synced.
     */
    public static function forSyncedEvent(
        DeviceId $deviceId,
        SyncVersion $syncVersion,
        DateTimeImmutable $offlineCreatedAt,
        DateTimeImmutable $syncedAt,
    ): self {
        return new self(
            deviceId: $deviceId,
            syncVersion: $syncVersion,
            offlineCreatedAt: $offlineCreatedAt,
            syncedAt: $syncedAt,
            status: SyncStatus::Synced,
        );
    }

    /**
     * Create sync metadata for an event in conflict.
     */
    public static function forConflictEvent(
        DeviceId $deviceId,
        SyncVersion $syncVersion,
        DateTimeImmutable $offlineCreatedAt,
    ): self {
        return new self(
            deviceId: $deviceId,
            syncVersion: $syncVersion,
            offlineCreatedAt: $offlineCreatedAt,
            syncedAt: null,
            status: SyncStatus::Conflict,
        );
    }

    /**
     * Mark this event as synced.
     */
    public function markSynced(DateTimeImmutable $syncedAt): self
    {
        return new self(
            deviceId: $this->deviceId,
            syncVersion: $this->syncVersion,
            offlineCreatedAt: $this->offlineCreatedAt,
            syncedAt: $syncedAt,
            status: SyncStatus::Synced,
        );
    }

    /**
     * Mark this event as in conflict.
     */
    public function markConflict(): self
    {
        return new self(
            deviceId: $this->deviceId,
            syncVersion: $this->syncVersion,
            offlineCreatedAt: $this->offlineCreatedAt,
            syncedAt: $this->syncedAt,
            status: SyncStatus::Conflict,
        );
    }

    /**
     * Check if this event is pending sync.
     */
    public function isPending(): bool
    {
        return $this->status === SyncStatus::Pending;
    }

    /**
     * Check if this event has been synced.
     */
    public function isSynced(): bool
    {
        return $this->status === SyncStatus::Synced;
    }

    /**
     * Check if this event is in conflict.
     */
    public function isInConflict(): bool
    {
        return $this->status === SyncStatus::Conflict;
    }

    /**
     * Check if this event was created offline.
     */
    public function wasCreatedOffline(): bool
    {
        return $this->syncedAt !== null || $this->isPending();
    }

    /**
     * Get the time delta between offline creation and sync.
     * Returns null if not yet synced.
     */
    public function getSyncDelay(): ?\DateInterval
    {
        if ($this->syncedAt === null) {
            return null;
        }
        return $this->offlineCreatedAt->diff($this->syncedAt);
    }

    /**
     * Convert to array for serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'deviceId' => $this->deviceId->toString(),
            'syncVersion' => $this->syncVersion->toArray(),
            'offlineCreatedAt' => $this->offlineCreatedAt->format(DateTimeImmutable::ATOM),
            'syncedAt' => $this->syncedAt?->format(DateTimeImmutable::ATOM),
            'status' => $this->status->value,
        ];
    }

    /**
     * Create from array representation.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        /** @var non-empty-string $deviceIdStr */
        $deviceIdStr = $data['deviceId'];
        /** @var array<non-empty-string, int<0, max>> $syncVersionData */
        $syncVersionData = $data['syncVersion'];
        /** @var string $offlineCreatedAtStr */
        $offlineCreatedAtStr = $data['offlineCreatedAt'];
        /** @var ?string $syncedAtStr */
        $syncedAtStr = $data['syncedAt'] ?? null;
        /** @var int|string $statusValue */
        $statusValue = $data['status'];

        return new self(
            deviceId: DeviceId::fromString($deviceIdStr),
            syncVersion: SyncVersion::fromArray($syncVersionData),
            offlineCreatedAt: new DateTimeImmutable($offlineCreatedAtStr),
            syncedAt: $syncedAtStr !== null ? new DateTimeImmutable($syncedAtStr) : null,
            status: SyncStatus::from($statusValue),
        );
    }
}
