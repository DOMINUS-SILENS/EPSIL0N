<?php

declare(strict_types=1);

namespace Spiral\Kernel\Infrastructure\Projection\Sync;

use Spiral\Kernel\Domain\Shared\Event\DomainEvent;

interface IMobileSyncFeed
{
    /**
     * Distribute a domain event to the sync feed for all active devices in the tenant.
     */
    public function addToSyncFeed(DomainEvent $event): void;

    /**
     * Fetch events for a specific device since its last known offset.
     *
     * @return array<int, array{sync_id: int, event: DomainEvent}>
     */
    public function getDeltas(string $deviceId, int $sinceSyncId): array;

    /**
     * Update the device offset after a successful sync.
     */
    public function acknowledge(string $deviceId, int $syncId): void;
}
