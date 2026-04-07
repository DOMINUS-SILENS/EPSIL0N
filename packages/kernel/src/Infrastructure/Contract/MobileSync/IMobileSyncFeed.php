<?php

declare(strict_types=1);

namespace Spiral\Kernel\Infrastructure\Contract\MobileSync;

/**
 * Sync Law 3: Every mobile read must be cursor-based.
 */
interface IMobileSyncFeed
{
    /**
     * Returns a set of deltas since the provided sync ID.
     *
     * @return array<int, array{sync_id: int, event: DomainEvent}>
     */
    public function getDeltas(string $deviceId, int $sinceSyncId): array;

    /**
     * Confirms receipt of a sync ID to advance the device offset.
     */
    public function acknowledge(string $deviceId, int $syncId): void;
}
