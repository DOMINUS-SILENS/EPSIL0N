<?php

declare(strict_types=1);

namespace Spiral\Kernel\Infrastructure\Persistence\MobileSync;

use Spiral\Kernel\Domain\Identity\DeviceId;
use Spiral\Kernel\Domain\Identity\TenantId;
use Spiral\Kernel\Domain\Sync\SyncVersion;
use Spiral\Kernel\Domain\Sync\SyncStatus;
use Spiral\Kernel\Domain\Shared\Event\DomainEvent;
use Spiral\Kernel\Infrastructure\Contract\MobileSync\IMobileOfflineQueue;
use Spiral\Kernel\Infrastructure\Contract\MobileSync\QueuedEvent;
use Doctrine\DBAL\Connection;
use Ramsey\Uuid\Uuid;

/**
 * PostgreSQL implementation of the offline mobile queue.
 *
 * Stores offline events with vector clocks for conflict detection
 * and sync management.
 *
 * @package Spiral\Kernel\Infrastructure\Persistence\MobileSync
 */
final class PostgreSQLOfflineQueue implements IMobileOfflineQueue
{
    private const TABLE_NAME = 'mobile_offline_queue';

    public function __construct(
        private readonly Connection $connection,
        private readonly EventSerializer $serializer,
    ) {}

    public function enqueue(
        TenantId $tenantId,
        DeviceId $deviceId,
        DomainEvent $event,
        SyncVersion $version,
    ): string {
        $queueItemId = Uuid::uuid4()->toString();

        $this->connection->insert(self::TABLE_NAME, [
            'id' => $queueItemId,
            'tenant_id' => $tenantId->toString(),
            'device_id' => $deviceId->toString(),
            'event_type' => $event->getEventType(),
            'event_payload' => json_encode($event->toArray()),
            'sync_version' => json_encode($version->toArray()),
            'status' => SyncStatus::Pending->value,
            'queued_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'synced_at' => null,
            'error_message' => null,
            'conflict_data' => null,
        ]);

        return $queueItemId;
    }

    public function getPendingEvents(TenantId $tenantId, DeviceId $deviceId): array
    {
        $rows = $this->connection->fetchAllAssociative(
            sprintf(
                'SELECT * FROM %s WHERE tenant_id = ? AND device_id = ? ORDER BY queued_at ASC',
                self::TABLE_NAME
            ),
            [$tenantId->toString(), $deviceId->toString()]
        );

        return array_map(fn($row) => $this->hydrateQueuedEvent($row), $rows);
    }

    public function getEventsByStatus(
        TenantId $tenantId,
        DeviceId $deviceId,
        SyncStatus $status,
    ): array {
        $rows = $this->connection->fetchAllAssociative(
            sprintf(
                'SELECT * FROM %s WHERE tenant_id = ? AND device_id = ? AND status = ? ORDER BY queued_at ASC',
                self::TABLE_NAME
            ),
            [$tenantId->toString(), $deviceId->toString(), $status->value]
        );

        return array_map(fn($row) => $this->hydrateQueuedEvent($row), $rows);
    }

    public function getSyncBatch(
        TenantId $tenantId,
        DeviceId $deviceId,
        int $limit = 50,
    ): array {
        $rows = $this->connection->fetchAllAssociative(
            sprintf(
                'SELECT * FROM %s WHERE tenant_id = ? AND device_id = ? AND status = ? ORDER BY queued_at ASC LIMIT ?',
                self::TABLE_NAME
            ),
            [$tenantId->toString(), $deviceId->toString(), SyncStatus::Pending->value, $limit]
        );

        return array_map(fn($row) => $this->hydrateQueuedEvent($row), $rows);
    }

    public function updateStatus(
        string $queueItemId,
        SyncStatus $status,
        ?string $error = null,
    ): void {
        $this->connection->update(
            self::TABLE_NAME,
            [
                'status' => $status->value,
                'error_message' => $error,
            ],
            ['id' => $queueItemId]
        );
    }

    public function markSynced(string $queueItemId, SyncVersion $mergedVersion): void
    {
        $this->connection->update(
            self::TABLE_NAME,
            [
                'status' => SyncStatus::Synced->value,
                'sync_version' => json_encode($mergedVersion->toArray()),
                'synced_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            ],
            ['id' => $queueItemId]
        );
    }

    public function markConflict(string $queueItemId, array $conflictData): void
    {
        $this->connection->update(
            self::TABLE_NAME,
            [
                'status' => SyncStatus::Conflict->value,
                'conflict_data' => json_encode($conflictData),
            ],
            ['id' => $queueItemId]
        );
    }

    public function remove(string $queueItemId): void
    {
        $this->connection->delete(self::TABLE_NAME, ['id' => $queueItemId]);
    }

    public function getDeviceVersion(TenantId $tenantId, DeviceId $deviceId): SyncVersion
    {
        $rows = $this->connection->fetchAllAssociative(
            sprintf(
                'SELECT sync_version FROM %s WHERE tenant_id = ? AND device_id = ?',
                self::TABLE_NAME
            ),
            [$tenantId->toString(), $deviceId->toString()]
        );

        if (empty($rows)) {
            return SyncVersion::empty();
        }

        $merged = SyncVersion::empty();
        foreach ($rows as $row) {
            $version = SyncVersion::fromArray(json_decode($row['sync_version'], true));
            $merged = $merged->merge($version);
        }

        return $merged;
    }

    public function getQueueSize(TenantId $tenantId, DeviceId $deviceId): int
    {
        return (int) $this->connection->fetchOne(
            sprintf(
                'SELECT COUNT(*) FROM %s WHERE tenant_id = ? AND device_id = ?',
                self::TABLE_NAME
            ),
            [$tenantId->toString(), $deviceId->toString()]
        );
    }

    public function hasConflicts(TenantId $tenantId, DeviceId $deviceId): bool
    {
        return (bool) $this->connection->fetchOne(
            sprintf(
                'SELECT EXISTS(SELECT 1 FROM %s WHERE tenant_id = ? AND device_id = ? AND status = ?)',
                self::TABLE_NAME
            ),
            [$tenantId->toString(), $deviceId->toString(), SyncStatus::Conflict->value]
        );
    }

    public function clear(TenantId $tenantId, DeviceId $deviceId): void
    {
        $this->connection->delete(
            self::TABLE_NAME,
            [
                'tenant_id' => $tenantId->toString(),
                'device_id' => $deviceId->toString(),
            ]
        );
    }

    /**
     * Hydrate a database row to a QueuedEvent object.
     */
    private function hydrateQueuedEvent(array $row): QueuedEvent
    {
        $eventData = json_decode($row['event_payload'], true);
        $event = $this->serializer->deserialize($eventData, $row['event_type']);

        return QueuedEvent::fromArray([
            'queueItemId' => $row['id'],
            'tenantId' => $row['tenant_id'],
            'deviceId' => $row['device_id'],
            'syncVersion' => json_decode($row['sync_version'], true),
            'status' => $row['status'],
            'queuedAt' => $row['queued_at'],
            'syncedAt' => $row['synced_at'],
            'errorMessage' => $row['error_message'],
            'conflictData' => $row['conflict_data'] ? json_decode($row['conflict_data'], true) : null,
        ], $event);
    }
}
