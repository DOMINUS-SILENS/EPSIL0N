<?php

declare(strict_types=1);

namespace Spiral\Kernel\Infrastructure\Persistence\MobileSync;

/**
 * Status of a processed event.
 *
 * @package Spiral\Kernel\Infrastructure\Persistence\MobileSync
 */
enum ProcessingStatus: string
{
    case Synced = 'synced';
    case Merged = 'merged';
    case Rejected = 'rejected';
    case Conflict = 'conflict';
}
