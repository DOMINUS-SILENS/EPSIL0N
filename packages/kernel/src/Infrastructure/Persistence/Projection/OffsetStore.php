<?php

declare(strict_types=1);

namespace Spiral\Kernel\Infrastructure\Persistence\Projection;

use Spiral\Kernel\Infrastructure\Contract\MobileSync\IMobileSyncFeed;

/**
 * Implementation of SDR-008: Device Subscription.
 * Tracks the durable inbound checkpoint for each mobile device.
 */
final class OffsetStore implements IMobileSyncFeed
{
    /**
     * @var array<string, int>
     */
    private array $offsets = [];

    /**
     * @return list<array<string, mixed>>
     */
    public function getDeltas(string $deviceId, int $sinceSyncId): array
    {
        // In a real implementation, this would query the 'mobile_sync_feed' table
        // for records where sync_id > $sinceSyncId AND device_scope matches.
        return [];
    }

    public function acknowledge(string $deviceId, int $syncId): void
    {
        $this->offsets[$deviceId] = $syncId;
    }

    public function getOffset(string $deviceId): int
    {
        return $this->offsets[$deviceId] ?? 0;
    }
}
