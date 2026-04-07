<?php

declare(strict_types=1);

namespace Spiral\Kernel\Domain\Sync;

/**
 * Status of an offline event in the sync lifecycle.
 *
 * @package Spiral\Kernel\Domain\Sync
 */
enum SyncStatus: string
{
    /**
     * Event is pending sync with the server.
     * Created offline, not yet transmitted.
     */
    case Pending = 'pending';

    /**
     * Event has been successfully synced to the server.
     */
    case Syncing = 'syncing';

    /**
     * Event has been acknowledged by the server.
     */
    case Synced = 'synced';

    /**
     * Event is in conflict with server state.
     * Requires resolution before sync can complete.
     */
    case Conflict = 'conflict';

    /**
     * Event was rejected by the server.
     * Business rule violation or schema incompatibility.
     */
    case Rejected = 'rejected';

    /**
     * Event was merged with conflicting server event.
     */
    case Merged = 'merged';
}
