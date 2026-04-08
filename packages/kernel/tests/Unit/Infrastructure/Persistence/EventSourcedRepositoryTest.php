<?php

declare(strict_types=1);

namespace Spiral\Kernel\Tests\Unit\Infrastructure\Persistence;

use PHPUnit\Framework\TestCase;
use Spiral\Kernel\Domain\Identity\CausationId;
use Spiral\Kernel\Domain\Identity\CorrelationId;
use Spiral\Kernel\Domain\Identity\TenantId;
use Spiral\Kernel\Domain\Shared\Aggregate\AggregateRoot;
use Spiral\Kernel\Domain\Shared\Event\DomainEvent;
use Spiral\Kernel\Domain\Shared\Event\ExpectedVersion;
use Spiral\Kernel\Infrastructure\Contract\EventStore\IEventStore;
use Spiral\Kernel\Infrastructure\Persistence\EventStore\EventHydrator;
use Spiral\Kernel\Infrastructure\Persistence\EventSourcedRepository;
use Spiral\Kernel\Support\Exception\ConcurrencyConflictException;
use Spiral\Kernel\Support\Exception\DomainException;
use Spiral\Kernel\Support\Exception\EventStoreException;
use Spiral\Kernel\Support\Exception\BusinessRuleViolationException;

/**
 * Unit tests for EventSourcedRepository error handling.
 *
 * Tests verify that exceptions from EventStore operations are properly
 * propagated with complete context (tenant, aggregate ID, causation chains).
 *
 * @package Spiral\Kernel\Tests\Unit\Infrastructure\Persistence
 */
final class EventSourcedRepositoryTest extends TestCase
{
    private IEventStore $eventStore;
    private mixed $hydrator;
    private EventSourcedRepository $repository;
    private TenantId $tenantId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantId = TenantId::generate();
        $this->eventStore = $this->createMock(IEventStore::class);

        // Create a real EventHydrator with empty class map
        // It's not used during save() operations
        $this->hydrator = new EventHydrator([]);

        // Create repository with a test stream prefix
        $factory = fn(string $id, TenantId $tenant) => $this->createMockAggregate($id, $tenant);
        $this->repository = new EventSourcedRepository(
            $this->eventStore,
            $this->hydrator,
            $factory,
            'Order'
        );
    }

    /**
     * Test that ConcurrencyConflictException propagates unchanged.
     */
    public function testSavePropagatesConcurrencyConflictException(): void
    {
        $events = []; // Closure variable to hold events

        $aggregate = $this->createMock(AggregateRoot::class);
        $aggregate->method('getId')->willReturn('order-123');
        $aggregate->method('getTenantId')->willReturn($this->tenantId);
        $aggregate->method('getStreamVersion')->willReturn(0);
        $aggregate->method('popUncommittedEvents')->willReturnCallback(
            function () use (&$events) {
                $result = $events;
                $events = [];
                return $result;
            }
        );

        // Create event to be persisted
        $event = $this->createMockEvent('order-123', $this->tenantId);
        $events = [$event];

        $concurrencyException = new ConcurrencyConflictException(
            'Order',
            'order-123',
            1,  // expected version
            2   // actual version
        );

        $this->eventStore
            ->expects($this->once())
            ->method('append')
            ->willThrowException($concurrencyException);

        $this->expectException(ConcurrencyConflictException::class);
        $this->expectExceptionMessage('Concurrency conflict');

        $this->repository->save($aggregate);
    }

    /**
     * Test that EventStoreException propagates unchanged.
     */
    public function testSavePreservesAggregateContextOnEventStoreFailure(): void
    {
        $events = [];

        $aggregate = $this->createMock(AggregateRoot::class);
        $aggregate->method('getId')->willReturn('order-456');
        $aggregate->method('getTenantId')->willReturn($this->tenantId);
        $aggregate->method('getStreamVersion')->willReturn(0);
        $aggregate->method('popUncommittedEvents')->willReturnCallback(
            function () use (&$events) {
                $result = $events;
                $events = [];
                return $result;
            }
        );

        // Create event to be persisted
        $event = $this->createMockEvent('order-456', $this->tenantId);
        $events = [$event];

        $storeException = EventStoreException::failedToAppend(
            'Order:order-456',
            'Connection timeout'
        );

        $this->eventStore
            ->expects($this->once())
            ->method('append')
            ->willThrowException($storeException);

        $this->expectException(EventStoreException::class);
        $this->expectExceptionMessage('Failed to append');

        $this->repository->save($aggregate);
    }

    /**
     * Test that causation and correlation IDs are included in error context.
     */
    public function testSaveIncludesCausationChainOnError(): void
    {
        $events = [];

        $aggregate = $this->createMock(AggregateRoot::class);
        $aggregate->method('getId')->willReturn('order-789');
        $aggregate->method('getTenantId')->willReturn($this->tenantId);
        $aggregate->method('getStreamVersion')->willReturn(0);
        $aggregate->method('popUncommittedEvents')->willReturnCallback(
            function () use (&$events) {
                $result = $events;
                $events = [];
                return $result;
            }
        );

        // Create events with known causation/correlation IDs
        $correlationId = CorrelationId::generate();
        $causationId = CausationId::generate();
        $event = $this->createMockEvent('order-789', $this->tenantId, $correlationId, $causationId);
        $events = [$event];

        // Mock a generic exception to test wrapping
        $genericException = new \RuntimeException('Database connection failed');

        $this->eventStore
            ->expects($this->once())
            ->method('append')
            ->willThrowException($genericException);

        try {
            $this->repository->save($aggregate);
            $this->fail('Expected EventStoreException to be thrown');
        } catch (EventStoreException $e) {
            // Verify error message includes context
            $message = $e->getMessage();
            $this->assertStringContainsString('Order:order-789', $message);
            $this->assertStringContainsString($correlationId->toString(), $message);
            $this->assertStringContainsString($causationId->toString(), $message);
        }
    }

    /**
     * Test that no silent failures occur - all exceptions propagate.
     */
    public function testSaveNoSilentFailures(): void
    {
        $events = [];

        $aggregate = $this->createMock(AggregateRoot::class);
        $aggregate->method('getId')->willReturn('order-999');
        $aggregate->method('getTenantId')->willReturn($this->tenantId);
        $aggregate->method('getStreamVersion')->willReturn(0);
        $aggregate->method('popUncommittedEvents')->willReturnCallback(
            function () use (&$events) {
                $result = $events;
                $events = [];
                return $result;
            }
        );

        // Create event to be persisted
        $event = $this->createMockEvent('order-999', $this->tenantId);
        $events = [$event];

        // Throw a generic exception
        $unexpectedException = new \LogicException('Unexpected error in persistence');

        $this->eventStore
            ->expects($this->once())
            ->method('append')
            ->willThrowException($unexpectedException);

        // Exception should propagate (wrapped in EventStoreException)
        $this->expectException(EventStoreException::class);

        $this->repository->save($aggregate);
    }

    /**
     * Test that DomainException propagates unchanged.
     */
    public function testSavePropagatesDomainException(): void
    {
        $events = [];

        $aggregate = $this->createMock(AggregateRoot::class);
        $aggregate->method('getId')->willReturn('order-111');
        $aggregate->method('getTenantId')->willReturn($this->tenantId);
        $aggregate->method('getStreamVersion')->willReturn(0);
        $aggregate->method('popUncommittedEvents')->willReturnCallback(
            function () use (&$events) {
                $result = $events;
                $events = [];
                return $result;
            }
        );

        // Create event to be persisted
        $event = $this->createMockEvent('order-111', $this->tenantId);
        $events = [$event];

        $domainException = new BusinessRuleViolationException(
            'invalid-stream-config',
            'Invalid stream configuration'
        );

        $this->eventStore
            ->expects($this->once())
            ->method('append')
            ->willThrowException($domainException);

        $this->expectException(BusinessRuleViolationException::class);

        $this->repository->save($aggregate);
    }

    /**
     * Create a mock DomainEvent for testing.
     */
    private function createMockEvent(
        string $aggregateId,
        TenantId $tenantId,
        ?CorrelationId $correlationId = null,
        ?CausationId $causationId = null
    ): DomainEvent {
        $correlationId ??= CorrelationId::generate();
        $causationId ??= CausationId::generate();

        $event = $this->createMock(DomainEvent::class);

        $event->method('getCorrelationId')->willReturn($correlationId);
        $event->method('getCausationId')->willReturn($causationId);
        $event->method('getTenantId')->willReturn($tenantId);
        $event->method('getEventType')->willReturn('OrderPlaced');
        $event->method('getSchemaVersion')->willReturn('1.0');

        return $event;
    }
}
