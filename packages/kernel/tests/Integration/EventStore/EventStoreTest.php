<?php

declare(strict_types=1);

namespace Spiral\Kernel\Tests\Integration\EventStore;

use Spiral\Kernel\Tests\Integration\IntegrationTestCase;

/**
 * Integration tests for Event Store.
 *
 * These tests verify:
 * - Event append operations
 * - Event retrieval by aggregate ID
 * - Event replay functionality
 * - Event versioning and ordering
 *
 * Requires: PostgreSQL with event store tables (Phase 5+)
 *
 * @package Spiral\Kernel\Tests\Integration\EventStore
 */
final class EventStoreTest extends IntegrationTestCase
{
    public function testAppendEvent(): void
    {
        $this->skipIfEventStoreNotAvailable();

        // TODO: Implement when IEventStore is available
        // $eventStore = $this->createEventStore();
        // $event = DomainEvent::create(...);
        // $eventStore->append($event);
        // $this->assertTrue($eventStore->hasEvent($event->id()));
    }

    public function testRetrieveEventsByAggregateId(): void
    {
        $this->skipIfEventStoreNotAvailable();

        // TODO: Implement when IEventStore is available
        // $eventStore = $this->createEventStore();
        // $events = $eventStore->getEventsForAggregate($aggregateId);
        // $this->assertCount(3, $events);
    }

    public function testReplayEventsFromVersion(): void
    {
        $this->skipIfEventStoreNotAvailable();

        // TODO: Implement when IEventStore is available
        // $eventStore = $this->createEventStore();
        // $events = $eventStore->replayFromVersion($aggregateId, 5);
        // $this->assertGreaterThanOrEqual(5, $events[0]->version());
    }

    public function testEventOrdering(): void
    {
        $this->skipIfEventStoreNotAvailable();

        // TODO: Verify events are returned in correct order
    }

    public function testAppendMultipleEventsAtomically(): void
    {
        $this->skipIfEventStoreNotAvailable();

        // TODO: Test transactional append of multiple events
    }
}
