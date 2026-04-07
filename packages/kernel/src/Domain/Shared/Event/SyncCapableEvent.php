<?php

declare(strict_types=1);

namespace Spiral\Kernel\Domain\Shared\Event;

use Spiral\Kernel\Domain\Sync\SyncMetadata;

/**
 * Optional interface for events that participate in offline mobile sync.
 *
 * Events implementing this interface carry additional metadata:
 * - Device attribution for conflict resolution
 * - Vector clock for causal ordering
 * - Offline creation timestamp
 *
 * Most events do NOT need to implement this interface.
 * Use only for events that may be created while offline on mobile devices.
 */
interface SyncCapableEvent
{
    public function getSyncMetadata(): ?SyncMetadata;
}