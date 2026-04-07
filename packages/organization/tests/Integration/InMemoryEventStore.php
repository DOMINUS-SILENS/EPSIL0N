<?php

declare(strict_types=1);

namespace Spiral\Organization\Tests\Integration;

use Spiral\Kernel\Domain\Identity\TenantId;
use Spiral\Kernel\Domain\Shared\Event\DomainEvent;
use Spiral\Kernel\Domain\Shared\Event\ExpectedVersion;
use Spiral\Kernel\Domain\Shared\Event\StoredEvent;
use Spiral\Kernel\Infrastructure\Contract\EventStore\IEventStore;
use Spiral\Kernel\Support\Exception\ConcurrencyConflictException;

/**
 * In-memory implementation of IEventStore for testing.
 *
 * Provides fast, isolated testing without database dependencies.
 */
final class InMemoryEventStore implements IEventStore
{
    /**
     * @var array<string, list<StoredEvent>>
     * Format: tenantId:streamId => [StoredEvent, ...]
     */
    private array $streams = [];

    public function append(
        TenantId $tenantId,
        string $streamId,
        ExpectedVersion $expectedVersion,
        array $events,
    ): int {
        $key = $this->buildKey($tenantId, $streamId);
        $currentVersion = $this->getStreamVersion($tenantId, $streamId);

        // Check optimistic concurrency
        if (!$expectedVersion->isSatisfiedBy($currentVersion)) {
            $expectedValue = $expectedVersion->isExact() ? $expectedVersion->version() : 0;
            throw new ConcurrencyConflictException(
                aggregateType: 'Stream',
                aggregateId: $streamId,
                expectedVersion: $expectedValue,
                actualVersion: $currentVersion,
                previous: null,
            );
        }

        // Store events
        if (!isset($this->streams[$key])) {
            $this->streams[$key] = [];
        }

        $streamVersion = $currentVersion;
        foreach ($events as $event) {
            $streamVersion++;
            $this->streams[$key][] = StoredEvent::fromDomainEvent(
                $event,
                $streamId,
                $streamVersion
            );
        }

        return $streamVersion;
    }

    public function load(
        TenantId $tenantId,
        string $streamId,
        int $fromVersion = 0,
        ?int $maxCount = null,
    ): array {
        $key = $this->buildKey($tenantId, $streamId);

        if (!isset($this->streams[$key])) {
            return [];
        }

        $events = [];
        foreach ($this->streams[$key] as $storedEvent) {
            if ($storedEvent->streamVersion > $fromVersion) {
                $events[] = $storedEvent;
                if ($maxCount !== null && \count($events) >= $maxCount) {
                    break;
                }
            }
        }

        return $events;
    }

    public function loadReverse(
        TenantId $tenantId,
        string $streamId,
        int $fromVersion = 0,
        ?int $maxCount = null,
    ): array {
        $events = $this->load($tenantId, $streamId, 0);
        $events = array_reverse($events);

        if ($fromVersion > 0) {
            $events = array_filter($events, fn($e) => $e->streamVersion <= $fromVersion);
        }

        if ($maxCount !== null) {
            $events = array_slice($events, 0, $maxCount);
        }

        return array_values($events);
    }

    public function getStreamVersion(TenantId $tenantId, string $streamId): int
    {
        $key = $this->buildKey($tenantId, $streamId);

        return isset($this->streams[$key]) ? \count($this->streams[$key]) : 0;
    }

    public function streamExists(TenantId $tenantId, string $streamId): bool
    {
        $key = $this->buildKey($tenantId, $streamId);

        return isset($this->streams[$key]) && \count($this->streams[$key]) > 0;
    }

    public function deleteStream(TenantId $tenantId, string $streamId): void
    {
        $key = $this->buildKey($tenantId, $streamId);
        unset($this->streams[$key]);
    }

    /**
     * Clear all stored data (for test isolation).
     */
    public function clear(): void
    {
        $this->streams = [];
    }

    private function buildKey(TenantId $tenantId, string $streamId): string
    {
        return sprintf('%s:%s', $tenantId->toString(), $streamId);
    }
}
