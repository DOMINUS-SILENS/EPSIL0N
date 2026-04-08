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
use Spiral\Kernel\Support\Exception\ConcurrencyConflictException;
use Spiral\Kernel\Support\Exception\DomainException;
use Spiral\Kernel\Support\Exception\EventStoreException;
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
     * @throws ConcurrencyConflictException If expected version doesn't match
     * @throws DomainException On structural errors
     * @throws EventStoreException On database failures or unexpected persistence errors
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

        try {
            /** @var list<DomainEvent> $events */
            $newVersion = $this->eventStore->append(
                $tenantId,
                $streamId,
                $expectedVersion,
                $events
            );

            $aggregate->markCommitted($newVersion);
        } catch (ConcurrencyConflictException $e) {
            // Re-throw concurrency conflicts as-is; they already have version context
            throw $e;
        } catch (DomainException $e) {
            // Re-throw domain exceptions as-is; they indicate structural errors
            throw $e;
        } catch (EventStoreException $e) {
            // Re-throw event store exceptions as-is; they indicate persistence failures
            throw $e;
        } catch (\Throwable $e) {
            // Wrap any other unexpected exceptions with aggregate and event context
            $contextMessage = $this->buildErrorContextMessage($aggregate, $events, $streamId);
            throw EventStoreException::failedToAppend(
                $streamId,
                $contextMessage
            );
        }
    }

    /**
     * Build an informative error context message from aggregate and event information.
     *
     * @param AggregateRoot $aggregate The aggregate being saved
     * @param list<DomainEvent> $events The uncommitted events
     * @param string $streamId The stream identifier
     */
    private function buildErrorContextMessage(AggregateRoot $aggregate, array $events, string $streamId): string
    {
        $aggregateId = $aggregate->getId();
        $tenantId = $aggregate->getTenantId()->toString();
        $correlationId = 'N/A';
        $causationId = 'N/A';

        if (\count($events) > 0) {
            $firstEvent = $events[0];
            $correlationId = $firstEvent->getCorrelationId()->toString();
            $causationId = $firstEvent->getCausationId()->toString();
        }

        return \sprintf(
            '[%s:%s] for tenant [%s] (events: %d, correlation: %s, causation: %s)',
            $this->streamPrefix,
            $aggregateId,
            $tenantId,
            \count($events),
            $correlationId,
            $causationId
        );
    }

    private function resolveStreamId(string $id): string
    {
        return sprintf('%s:%s', $this->streamPrefix, $id);
    }
}
