<?php

declare(strict_types=1);

namespace Spiral\Kernel\Tests\Fixture\Persistence;

use Spiral\Kernel\Domain\Identity\TenantId;
use Spiral\Kernel\Domain\Shared\Event\DomainEvent;
use Spiral\Kernel\Domain\Shared\Event\ExpectedVersion;
use Spiral\Kernel\Domain\Shared\Event\StoredEvent;
use Spiral\Kernel\Infrastructure\Contract\EventStore\IEventStore;
use Spiral\Kernel\Support\Exception\ConcurrencyConflictException;

/**
 * In-memory event store for testing.
 *
 * Provides complete event sourcing functionality without database:
 * - Event persistence per stream
 * - Optimistic concurrency control
 * - Tenant isolation
 * - Event ordering by version
 *
 * @package Spiral\Kernel\Tests\Fixture\Persistence
 */
final class InMemoryEventStore implements IEventStore
{
    /**
     * Storage: [tenantId => [streamId => [version => StoredEvent]]]
     *
     * @var array<string, array<string, array<int, StoredEvent>>>
     */
    private array $streams = [];

    /**
     * Version tracking per stream: [tenantId => [streamId => currentVersion]]
     *
     * @var array<string, array<string, int>>
     */
    private array $versions = [];

    public function append(
        TenantId $tenantId,
        string $streamId,
        ExpectedVersion $expectedVersion,
        array $events,
    ): int {
        $tenantKey = $tenantId->toString();
        $currentVersion = $this->getStreamVersion($tenantId, $streamId);

        // Verify expected version constraint
        if (!$expectedVersion->isSatisfiedBy($currentVersion)) {
            throw new ConcurrencyConflictException(
                'Stream',
                $streamId,
                $expectedVersion->isExact() ? $expectedVersion->version() : 0,
                $currentVersion
            );
        }

        // Initialize tenant/stream if needed
        if (!isset($this->streams[$tenantKey])) {
            $this->streams[$tenantKey] = [];
            $this->versions[$tenantKey] = [];
        }
        if (!isset($this->streams[$tenantKey][$streamId])) {
            $this->streams[$tenantKey][$streamId] = [];
        }

        // Append events, incrementing version for each
        $newVersion = $currentVersion;
        foreach ($events as $event) {
            $newVersion++;
            $storedEvent = StoredEvent::fromDomainEvent(
                $event,
                $streamId,
                $newVersion
            );
            $this->streams[$tenantKey][$streamId][$newVersion] = $storedEvent;
        }

        // Update version tracking
        $this->versions[$tenantKey][$streamId] = $newVersion;

        return $newVersion;
    }

    public function load(
        TenantId $tenantId,
        string $streamId,
        int $fromVersion = 0,
        ?int $maxCount = null,
    ): array {
        $tenantKey = $tenantId->toString();

        // Return empty if stream doesn't exist
        if (!isset($this->streams[$tenantKey][$streamId])) {
            return [];
        }

        $stream = $this->streams[$tenantKey][$streamId];
        $result = [];

        // Start version is 1-based (0 means beginning)
        $startVersion = $fromVersion === 0 ? 1 : $fromVersion;

        foreach ($stream as $version => $event) {
            if ($version >= $startVersion) {
                $result[] = $event;
                if ($maxCount !== null && count($result) >= $maxCount) {
                    break;
                }
            }
        }

        return $result;
    }

    public function loadReverse(
        TenantId $tenantId,
        string $streamId,
        int $fromVersion = 0,
        ?int $maxCount = null,
    ): array {
        $tenantKey = $tenantId->toString();

        // Return empty if stream doesn't exist
        if (!isset($this->streams[$tenantKey][$streamId])) {
            return [];
        }

        $stream = $this->streams[$tenantKey][$streamId];
        $result = [];

        // Load in reverse order
        $events = array_reverse($stream);
        $startVersion = $fromVersion === 0 ? PHP_INT_MAX : $fromVersion;

        foreach ($events as $version => $event) {
            if ($version <= $startVersion) {
                $result[] = $event;
                if ($maxCount !== null && count($result) >= $maxCount) {
                    break;
                }
            }
        }

        return $result;
    }

    public function getStreamVersion(TenantId $tenantId, string $streamId): int
    {
        $tenantKey = $tenantId->toString();

        if (!isset($this->versions[$tenantKey][$streamId])) {
            return 0;
        }

        return $this->versions[$tenantKey][$streamId];
    }

    /**
     * Clear all stored events (testing utility).
     */
    public function clear(): void
    {
        $this->streams = [];
        $this->versions = [];
    }
}
