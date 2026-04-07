<?php

declare(strict_types=1);

namespace Spiral\Kernel\Infrastructure\Projection\Sync;

use Spiral\Kernel\Domain\Shared\Event\DomainEvent;
use PDO;

class PostgresqlMobileSyncFeed implements IMobileSyncFeed
{
    public function __construct(
        private readonly PDO $db
    ) {}

    public function addToSyncFeed(DomainEvent $event): void
    {
        // SFA Law: Distribute events to the feed.
        // In a real system, we might check which devices are "active" for the tenant.
        // For this implementation, we record the event in the global feed.

        $sql = "INSERT INTO mobile_sync_feed (tenant_id, aggregate_type, aggregate_id, event_type, event_id, payload, created_at)
                VALUES (:tenant_id, :aggregate_type, :aggregate_id, :event_type, :event_id, :payload, NOW())";

        $data = $event->toArray();

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'tenant_id'     => $event->getTenantId()->toString(),
            'aggregate_type' => $data['aggregate_type'] ?? 'unknown',
            'aggregate_id'   => $data['aggregate_id'] ?? 'unknown',
            'event_type'     => $event->getEventType(),
            'event_id'      => $event->getEventId()->toString(),
            'payload'       => json_encode($data),
        ]);
    }

    public function getDeltas(string $deviceId, int $sinceSyncId): array
    {
        // SFA Law: Chronological order, device-specific cursors.
        $sql = "SELECT sync_id, event_type, event_id, payload
                FROM mobile_sync_feed
                WHERE sync_id > :sinceSyncId
                AND tenant_id = (SELECT tenant_id FROM device_offsets WHERE device_id = :deviceId LIMIT 1)
                ORDER BY sync_id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'sinceSyncId' => $sinceSyncId,
            'deviceId'     => $deviceId,
        ]);

        $deltas = [];
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        /** @var array<int, array{sync_id: int|string, event_type: string, event_id: string, payload: string}> $rows */
        foreach ($rows as $row) {
            $deltas[] = [
                'sync_id' => (int) $row['sync_id'],
                'event'   => $this->hydrateEvent($row),
            ];
        }

        return $deltas;
    }

    public function acknowledge(string $deviceId, int $syncId): void
    {
        // SFA Law: Update acknowledged offsets.
        $sql = "UPDATE device_offsets
                SET last_sync_id = :syncId, updated_at = NOW()
                WHERE device_id = :deviceId";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'syncId'   => $syncId,
            'deviceId' => $deviceId,
        ]);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrateEvent(array $row): DomainEvent
    {
        // In a full implementation, this would use a Serializer to recreate the DomainEvent object.
        // For the kernel substrate, we'll return a generic proxy or mock that implements DomainEvent.
        // Since we don't have a generic Event factory yet, we represent the concept.
        throw new \RuntimeException("Event hydration requires a concrete Serializer implementation.");
    }
}
