<?php

declare(strict_types=1);

namespace Spiral\Kernel\Infrastructure\Contract\EventStore;

use Spiral\Kernel\Domain\Identity\TenantId;
use Spiral\Kernel\Domain\Shared\Event\DomainEvent;
use Spiral\Kernel\Domain\Shared\Event\ExpectedVersion;
use Spiral\Kernel\Domain\Shared\Event\StoredEvent;
use Spiral\Kernel\Support\Exception\ConcurrencyConflictException;

/**
 * Event Store contract for appending and loading domain events.
 *
 * This is the minimal runtime contract - append and load only.
 * Not more abstractions yet.
 */
interface IEventStore
{
    /**
     * Append events to a stream.
     *
     * @param TenantId $tenantId Tenant for isolation
     * @param string $streamId Unique stream identifier (e.g., "Order:order-123")
     * @param ExpectedVersion $expectedVersion Concurrency control
     * @param list<DomainEvent> $events Events to append
     *
     * @return int The new stream version after append
     *
     * @throws ConcurrencyConflictException If expected version doesn't match
     * @throws \Spiral\Kernel\Support\Exception\DomainException On structural errors
     */
    public function append(
        TenantId $tenantId,
        string $streamId,
        ExpectedVersion $expectedVersion,
        array $events,
    ): int;

    /**
     * Load events from a stream.
     *
     * @param TenantId $tenantId Tenant for isolation
     * @param string $streamId Unique stream identifier
     * @param int $fromVersion Load events from this version (0 = beginning)
     * @param int|null $maxCount Maximum events to load (null = all)
     *
     * @return list<StoredEvent> Events in chronological order
     */
    public function load(
        TenantId $tenantId,
        string $streamId,
        int $fromVersion = 0,
        ?int $maxCount = null,
    ): array;

    /**
     * Load events from a stream in reverse order (newest first).
     *
     * Useful for "get latest state" operations.
     *
     * @param TenantId $tenantId Tenant for isolation
     * @param string $streamId Unique stream identifier
     * @param int $fromVersion Load events from this version (0 = latest)
     * @param int|null $maxCount Maximum events to load
     *
     * @return list<StoredEvent> Events in reverse chronological order
     */
    public function loadReverse(
        TenantId $tenantId,
        string $streamId,
        int $fromVersion = 0,
        ?int $maxCount = null,
    ): array;

    /**
     * Get the current stream version (number of events).
     *
     * @return int Number of events in stream, or 0 if stream doesn't exist
     */
    public function getStreamVersion(TenantId $tenantId, string $streamId): int;
}
