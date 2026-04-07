<?php

declare(strict_types=1);

namespace Spiral\Kernel\Infrastructure\Contract\MobileSync;

use Spiral\Kernel\Domain\Identity\DeviceId;
use Spiral\Kernel\Domain\Identity\TenantId;
use Spiral\Kernel\Domain\Sync\SyncVersion;
use Spiral\Kernel\Domain\Sync\SyncStatus;
use Spiral\Kernel\Domain\Shared\Event\DomainEvent;
use DateTimeImmutable;

/**
 * Represents an event in the offline queue.
 *
 * QueuedEvent wraps a domain event with sync-specific metadata
 * for tracking and conflict resolution.
 *
 * @package Spiral\Kernel\Infrastructure\Contract\MobileSync
 */
final class QueuedEvent
{
    public function __construct(
        public readonly string $queueItemId,
        public readonly TenantId $tenantId,
        public readonly DeviceId $deviceId,
        public readonly DomainEvent $event,
        public readonly SyncVersion $syncVersion,
        public readonly SyncStatus $status,
        public readonly DateTimeImmutable $queuedAt,
        public readonly ?DateTimeImmutable $syncedAt = null,
        public readonly ?string $errorMessage = null,
        /** @var array<string, mixed>|null */
        public readonly ?array $conflictData = null,
    ) {}

    /**
     * Check if this event is ready for sync.
     */
    public function isReadyForSync(): bool
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
     * Get the time this event has been in the queue.
     */
    public function getQueueDuration(): \DateInterval
    {
        $end = $this->syncedAt ?? new DateTimeImmutable();
        return $this->queuedAt->diff($end);
    }

    /**
     * Create from array representation.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, DomainEvent $event): self
    {
        return new self(
            queueItemId: $data['queueItemId'],
            tenantId: TenantId::fromString($data['tenantId']),
            deviceId: DeviceId::fromString($data['deviceId']),
            event: $event,
            syncVersion: SyncVersion::fromArray($data['syncVersion']),
            status: SyncStatus::from($data['status']),
            queuedAt: new DateTimeImmutable($data['queuedAt']),
            syncedAt: isset($data['syncedAt']) ? new DateTimeImmutable($data['syncedAt']) : null,
            errorMessage: $data['errorMessage'] ?? null,
            conflictData: $data['conflictData'] ?? null,
        );
    }

    /**
     * Convert to array for serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'queueItemId' => $this->queueItemId,
            'tenantId' => $this->tenantId->toString(),
            'deviceId' => $this->deviceId->toString(),
            'syncVersion' => $this->syncVersion->toArray(),
            'status' => $this->status->value,
            'queuedAt' => $this->queuedAt->format(DateTimeImmutable::ATOM),
            'syncedAt' => $this->syncedAt?->format(DateTimeImmutable::ATOM),
            'errorMessage' => $this->errorMessage,
            'conflictData' => $this->conflictData,
        ];
    }
}
