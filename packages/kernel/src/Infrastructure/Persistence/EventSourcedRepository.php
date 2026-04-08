<?php

declare(strict_types=1);

namespace Spiral\Kernel\Infrastructure\Persistence;

use Spiral\Kernel\Domain\Identity\TenantId;
use Spiral\Kernel\Domain\Shared\Aggregate\AggregateRoot;
use Spiral\Kernel\Domain\Shared\Event\DomainEvent;
use Spiral\Kernel\Domain\Shared\Event\ExpectedVersion;
use Spiral\Kernel\Domain\Shared\ValueObject\ValueObject;
use Spiral\Kernel\Infrastructure\Contract\EventStore\IEventStore;
use Spiral\Kernel\Infrastructure\Contract\Persistence\IRepository;
use Spiral\Kernel\Infrastructure\Persistence\EventStore\EventHydrator;
use Spiral\Kernel\Support\Exception\NotFoundException;

/**
 * Generic Event-Sourced Repository.
 *
 * Coordinates between the Event Store and AggregateRoot to manage the
 * lifecycle of event-sourced aggregates.
 *
 * @template T of AggregateRoot
 * @template TId of ValueObject
 * @implements IRepository<T, TId>
 */
final class EventSourcedRepository implements IRepository
{
    /**
     * @param IEventStore $eventStore The underlying event store
     * @param EventHydrator $hydrator Hydrator to convert StoredEvent to DomainEvent
     * @param callable(string, TenantId): T $factory Factory to create a fresh aggregate instance
     * @param string $streamPrefix Prefix for the event stream (e.g., "Order")
     */
    public function __construct(
        private readonly IEventStore $eventStore,
        private readonly EventHydrator $hydrator,
        private readonly mixed $factory,
        private readonly string $streamPrefix,
    ) {}

    /**
     * Reconstitutes an aggregate from its event stream.
     *
     * @param TId $id
     * @param TenantId $tenantId
     * @return T|null
     */
    public function getById(ValueObject $id, TenantId $tenantId): ?AggregateRoot
    {
        $streamId = $this->resolveStreamId((string)$id);

        try {
            $storedEvents = $this->eventStore->load($tenantId, $streamId);
        } catch (NotFoundException) {
            return null;
        }
        $version = $this->eventStore->getStreamVersion($tenantId, $streamId);

        // Hydrate StoredEvents to DomainEvents
        $events = $this->hydrator->hydrateAll($storedEvents);

        /** @var T $aggregate */
        $aggregate = ($this->factory)((string)$id, $tenantId);
        $aggregate->reconstituteFromEvents($events, $version);

        return $aggregate;
    }

    /**
     * Persists uncommitted events from an aggregate.
     *
     * @param T $aggregate
     */
    public function save(AggregateRoot $aggregate): void
    {
        $events = $aggregate->popUncommittedEvents();
        if (\count($events) === 0) {
            return;
        }

        $tenantId = $aggregate->getTenantId();
        $streamId = $this->resolveStreamId($aggregate->getId());

        $expectedVersion = $aggregate->getStreamVersion() === 0
            ? ExpectedVersion::noStream()
            : ExpectedVersion::exact($aggregate->getStreamVersion());

        /** @var list<DomainEvent> $events */
        $newVersion = $this->eventStore->append(
            $tenantId,
            $streamId,
            $expectedVersion,
            $events
        );

        $aggregate->markCommitted($newVersion);
    }

    private function resolveStreamId(string $id): string
    {
        return sprintf('%s:%s', $this->streamPrefix, $id);
    }
}
