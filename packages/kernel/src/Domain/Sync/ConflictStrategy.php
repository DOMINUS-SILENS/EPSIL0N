<?php

declare(strict_types=1);

namespace Spiral\Kernel\Domain\Sync;

/**
 * Strategy for resolving conflicts in offline mobile sync.
 *
 * Each strategy defines how concurrent edits from different devices
 * should be resolved when they conflict.
 *
 * @package Spiral\Kernel\Domain\Sync
 */
enum ConflictStrategy: string
{
    /**
     * Last writer wins based on timestamp comparison.
     * Simple but may lose data from earlier writers.
     */
    case LastWriterWins = 'last_writer_wins';

    /**
     * First writer wins based on timestamp comparison.
     * Preserves earliest data, rejects later edits.
     */
    case FirstWriterWins = 'first_writer_wins';

    /**
     * Device with higher priority wins.
     * Priority is configured per tenant/device type.
     * Useful for "manager overrides employee" scenarios.
     */
    case DevicePriority = 'device_priority';

    /**
     * Server always wins in conflict scenarios.
     * Simplest for compliance, but may frustrate offline users.
     */
    case ServerWins = 'server_wins';

    /**
     * Client (offline device) always wins.
     * Optimistic approach, but may cause data inconsistency.
     */
    case ClientWins = 'client_wins';

    /**
     * Use operational transform to merge changes.
     * Most sophisticated, preserves intent of both edits.
     * Requires domain-specific merge logic.
     */
    case OperationalTransform = 'operational_transform';
}
