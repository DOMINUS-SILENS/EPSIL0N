<?php

declare(strict_types=1);

namespace Spiral\Kernel\Tests\Unit\Domain\Aggregate;

use Spiral\Kernel\Tests\KernelTestCase;
use Spiral\Kernel\Tests\Fixture\Aggregate\TestAggregate;
use Spiral\Kernel\Tests\Fixture\Aggregate\TestAggregateCreated;
use Spiral\Kernel\Tests\Fixture\Aggregate\TestAggregateNameChanged;
use Spiral\Kernel\Tests\Fixture\Aggregate\TestAggregateActivated;
use Spiral\Kernel\Tests\Fixture\Aggregate\TestAggregateDeactivated;
use Spiral\Kernel\Domain\Identity\TenantId;
use Spiral\Kernel\Domain\Identity\ActorId;
use Spiral\Kernel\Support\Exception\BusinessRuleViolationException;

/**
 * Comprehensive tests for Aggregate state management.
 * Tests cover: creation, state changes, and reconstitution.
 */
final class AggregateCreationTest extends KernelTestCase
{
    private function createTenantId(): TenantId
    {
        return TenantId::generate();
    }

    private function createActorId(): ActorId
    {
        return ActorId::generate();
    }

    // ========== Creation Tests ==========

    public function testCreateReturnsAggregate(): void
    {
        $aggregate = TestAggregate::create(
            'agg-123',
            $this->createTenantId(),
            'Test Aggregate',
            $this->createActorId()
        );

        $this->assertInstanceOf(TestAggregate::class, $aggregate);
    }

    public function testCreateSetsId(): void
    {
        $aggregate = TestAggregate::create(
            'agg-123',
            $this->createTenantId(),
            'Test Aggregate',
            $this->createActorId()
        );

        $this->assertSame('agg-123', $aggregate->id());
    }

    public function testCreateSetsTenantId(): void
    {
        $tenantId = $this->createTenantId();
        $aggregate = TestAggregate::create(
            'agg-123',
            $tenantId,
            'Test Aggregate',
            $this->createActorId()
        );

        $this->assertTrue($aggregate->tenantId()->equals($tenantId));
    }

    public function testCreateSetsName(): void
    {
        $aggregate = TestAggregate::create(
            'agg-123',
            $this->createTenantId(),
            'Test Aggregate',
            $this->createActorId()
        );

        $this->assertSame('Test Aggregate', $aggregate->name());
    }

    public function testCreateSetsActiveState(): void
    {
        $aggregate = TestAggregate::create(
            'agg-123',
            $this->createTenantId(),
            'Test Aggregate',
            $this->createActorId()
        );

        $this->assertTrue($aggregate->isActive());
    }

    public function testCreateSetsCreatedFlag(): void
    {
        $aggregate = TestAggregate::create(
            'agg-123',
            $this->createTenantId(),
            'Test Aggregate',
            $this->createActorId()
        );

        $this->assertTrue($aggregate->isCreated());
    }

    public function testCreateStartsAtVersionOne(): void
    {
        $aggregate = TestAggregate::create(
            'agg-123',
            $this->createTenantId(),
            'Test Aggregate',
            $this->createActorId()
        );

        $this->assertSame(1, $aggregate->version());
    }

    public function testCreateRecordsCreatedEvent(): void
    {
        $aggregate = TestAggregate::create(
            'agg-123',
            $this->createTenantId(),
            'Test Aggregate',
            $this->createActorId()
        );

        $events = $aggregate->peekEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(TestAggregateCreated::class, $events[0]);
    }

    // ========== Creation Validation Tests ==========

    public function testCreateRejectsEmptyName(): void
    {
        $this->expectException(BusinessRuleViolationException::class);
        $this->expectExceptionMessage('cannot be empty');

        TestAggregate::create(
            'agg-123',
            $this->createTenantId(),
            '',
            $this->createActorId()
        );
    }

    public function testCreateRejectsTooShortName(): void
    {
        $this->expectException(BusinessRuleViolationException::class);
        $this->expectExceptionMessage('at least 3 characters');

        TestAggregate::create(
            'agg-123',
            $this->createTenantId(),
            'ab',
            $this->createActorId()
        );
    }

    public function testCreateAcceptsExactlyThreeCharacterName(): void
    {
        $aggregate = TestAggregate::create(
            'agg-123',
            $this->createTenantId(),
            'abc',
            $this->createActorId()
        );

        $this->assertSame('abc', $aggregate->name());
    }
}

final class AggregateStateChangeTest extends KernelTestCase
{
    private function createTenantId(): TenantId
    {
        return TenantId::generate();
    }

    private function createActorId(): ActorId
    {
        return ActorId::generate();
    }

    private function createAggregate(): TestAggregate
    {
        return TestAggregate::create(
            'agg-123',
            $this->createTenantId(),
            'Original Name',
            $this->createActorId()
        );
    }

    // ========== Change Name Tests ==========

    public function testChangeNameUpdatesName(): void
    {
        $aggregate = $this->createAggregate();
        $aggregate->releaseEvents(); // Clear events

        $aggregate->changeName('New Name', $this->createActorId());

        $this->assertSame('New Name', $aggregate->name());
    }

    public function testChangeNameRecordsEvent(): void
    {
        $aggregate = $this->createAggregate();
        $aggregate->releaseEvents(); // Clear events

        $aggregate->changeName('New Name', $this->createActorId());

        $events = $aggregate->peekEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(TestAggregateNameChanged::class, $events[0]);
    }

    public function testChangeNameIncrementsVersion(): void
    {
        $aggregate = $this->createAggregate();
        $initialVersion = $aggregate->version();
        $aggregate->releaseEvents(); // Clear events

        $aggregate->changeName('New Name', $this->createActorId());

        $this->assertSame($initialVersion + 1, $aggregate->version());
    }

    public function testChangeNameIsIdempotentForSameName(): void
    {
        $aggregate = $this->createAggregate();
        $aggregate->releaseEvents(); // Clear events

        $aggregate->changeName('Original Name', $this->createActorId());

        $this->assertSame(0, $aggregate->nameChangeCount());
        $this->assertFalse($aggregate->hasUncommittedEvents());
    }

    public function testChangeNameMultipleTimes(): void
    {
        $aggregate = $this->createAggregate();
        $actorId = $this->createActorId();

        $aggregate->changeName('Name 1', $actorId);
        $aggregate->changeName('Name 2', $actorId);
        $aggregate->changeName('Name 3', $actorId);

        $this->assertSame('Name 3', $aggregate->name());
        $this->assertSame(3, $aggregate->nameChangeCount());
    }

    public function testChangeNameRejectsEmptyName(): void
    {
        $aggregate = $this->createAggregate();

        $this->expectException(BusinessRuleViolationException::class);
        $this->expectExceptionMessage('cannot be empty');

        $aggregate->changeName('', $this->createActorId());
    }

    public function testChangeNameOnNonCreatedAggregate(): void
    {
        $aggregate = new TestAggregate('agg-123', $this->createTenantId());

        $this->expectException(BusinessRuleViolationException::class);
        $this->expectExceptionMessage('non-created');

        $aggregate->changeName('New Name', $this->createActorId());
    }

    // ========== Deactivate Tests ==========

    public function testDeactivateSetsInactive(): void
    {
        $aggregate = $this->createAggregate();
        $aggregate->releaseEvents();

        $aggregate->deactivate('Test reason', $this->createActorId());

        $this->assertFalse($aggregate->isActive());
    }

    public function testDeactivateRecordsEvent(): void
    {
        $aggregate = $this->createAggregate();
        $aggregate->releaseEvents();

        $aggregate->deactivate('Test reason', $this->createActorId());

        $events = $aggregate->peekEvents();
        $this->assertInstanceOf(TestAggregateDeactivated::class, end($events));
    }

    public function testDeactivateRejectsEmptyReason(): void
    {
        $aggregate = $this->createAggregate();

        $this->expectException(BusinessRuleViolationException::class);
        $this->expectExceptionMessage('cannot be empty');

        $aggregate->deactivate('', $this->createActorId());
    }

    public function testDeactivateRejectsWhenAlreadyInactive(): void
    {
        $aggregate = $this->createAggregate();
        $actorId = $this->createActorId();
        $aggregate->deactivate('First reason', $actorId);

        $this->expectException(BusinessRuleViolationException::class);
        $this->expectExceptionMessage('already inactive');

        $aggregate->deactivate('Second reason', $actorId);
    }

    // ========== Activate Tests ==========

    public function testActivateAfterDeactivate(): void
    {
        $aggregate = $this->createAggregate();
        $actorId = $this->createActorId();
        $aggregate->deactivate('Test reason', $actorId);
        $aggregate->releaseEvents();

        $aggregate->activate($actorId);

        $this->assertTrue($aggregate->isActive());
    }

    public function testActivateIsIdempotentWhenAlreadyActive(): void
    {
        $aggregate = $this->createAggregate();
        $aggregate->releaseEvents();

        $aggregate->activate($this->createActorId());

        $this->assertFalse($aggregate->hasUncommittedEvents());
    }
}

final class AggregateInvariantTest extends KernelTestCase
{
    private function createTenantId(): TenantId
    {
        return TenantId::generate();
    }

    private function createActorId(): ActorId
    {
        return ActorId::generate();
    }

    private function createAggregate(): TestAggregate
    {
        return TestAggregate::create(
            'agg-123',
            $this->createTenantId(),
            'Original Name',
            $this->createActorId()
        );
    }

    // ========== Business Rule Enforcement Tests ==========

    public function testNameChangeLimitEnforced(): void
    {
        $aggregate = $this->createAggregate();
        $actorId = $this->createActorId();

        // Change name 10 times (the limit)
        for ($i = 0; $i < 10; $i++) {
            $aggregate->changeName("Name $i", $actorId);
        }

        $this->expectException(BusinessRuleViolationException::class);
        $this->expectExceptionMessage('Maximum number of name changes');

        // 11th change should fail
        $aggregate->changeName('Final Name', $actorId);
    }

    public function testStateConsistencyAfterFailedOperation(): void
    {
        $aggregate = $this->createAggregate();
        $actorId = $this->createActorId();

        // Fill up name changes
        for ($i = 0; $i < 10; $i++) {
            $aggregate->changeName("Name $i", $actorId);
        }

        $nameBefore = $aggregate->name();
        $versionBefore = $aggregate->version();

        try {
            $aggregate->changeName('Should Fail', $actorId);
        } catch (BusinessRuleViolationException $e) {
            // Expected
        }

        // State should be unchanged
        $this->assertSame($nameBefore, $aggregate->name());
        $this->assertSame($versionBefore, $aggregate->version());
    }

    public function testInvalidOperationDoesNotRecordEvent(): void
    {
        $aggregate = $this->createAggregate();
        $actorId = $this->createActorId();

        // Fill up name changes
        for ($i = 0; $i < 10; $i++) {
            $aggregate->changeName("Name $i", $actorId);
        }

        $eventCountBefore = count($aggregate->peekEvents());

        try {
            $aggregate->changeName('Should Fail', $actorId);
        } catch (BusinessRuleViolationException $e) {
            // Expected
        }

        // No new events should be recorded
        $this->assertSame($eventCountBefore, count($aggregate->peekEvents()));
    }

    // ========== State Transition Rule Tests ==========

    public function testCannotChangeNameOnUncreatedAggregate(): void
    {
        $aggregate = new TestAggregate('agg-123', $this->createTenantId());

        $this->expectException(BusinessRuleViolationException::class);
        $this->expectExceptionMessage('non-created');

        $aggregate->changeName('New Name', $this->createActorId());
    }

    public function testCannotDeactivateOnUncreatedAggregate(): void
    {
        $aggregate = new TestAggregate('agg-123', $this->createTenantId());

        $this->expectException(BusinessRuleViolationException::class);
        $this->expectExceptionMessage('non-created');

        $aggregate->deactivate('Reason', $this->createActorId());
    }
}

final class AggregateEventEmissionTest extends KernelTestCase
{
    private function createTenantId(): TenantId
    {
        return TenantId::generate();
    }

    private function createActorId(): ActorId
    {
        return ActorId::generate();
    }

    private function createAggregate(): TestAggregate
    {
        return TestAggregate::create(
            'agg-123',
            $this->createTenantId(),
            'Original Name',
            $this->createActorId()
        );
    }

    // ========== Event Recording Tests ==========

    public function testCreateEmitsCreatedEvent(): void
    {
        $aggregate = $this->createAggregate();
        $events = $aggregate->peekEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(TestAggregateCreated::class, $events[0]);
    }

    public function testCreatedEventContainsCorrectData(): void
    {
        $tenantId = $this->createTenantId();
        $actorId = $this->createActorId();

        $aggregate = TestAggregate::create(
            'agg-123',
            $tenantId,
            'Test Name',
            $actorId
        );

        /** @var TestAggregateCreated $event */
        $event = $aggregate->peekEvents()[0];

        $this->assertSame('agg-123', $event->aggregateId);
        $this->assertSame($tenantId->toString(), $event->tenantId);
        $this->assertSame('Test Name', $event->name);
        $this->assertSame($actorId->toString(), $event->createdBy);
        $this->assertInstanceOf(\DateTimeImmutable::class, $event->createdAt);
    }

    public function testNameChangeEmitsNameChangedEvent(): void
    {
        $aggregate = $this->createAggregate();
        $aggregate->releaseEvents();

        $aggregate->changeName('New Name', $this->createActorId());

        $events = $aggregate->peekEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(TestAggregateNameChanged::class, $events[0]);
    }

    public function testNameChangedEventContainsCorrectData(): void
    {
        $aggregate = $this->createAggregate();
        $actorId = $this->createActorId();
        $aggregate->releaseEvents();

        $aggregate->changeName('New Name', $actorId);

        /** @var TestAggregateNameChanged $event */
        $event = $aggregate->peekEvents()[0];

        $this->assertSame('agg-123', $event->aggregateId);
        $this->assertSame('New Name', $event->newName);
        $this->assertSame($actorId->toString(), $event->changedBy);
        $this->assertInstanceOf(\DateTimeImmutable::class, $event->changedAt);
    }

    public function testMultipleEventsAccumulate(): void
    {
        $aggregate = $this->createAggregate();
        $actorId = $this->createActorId();

        $aggregate->changeName('Name 1', $actorId);
        $aggregate->changeName('Name 2', $actorId);
        $aggregate->deactivate('Reason', $actorId);

        $events = $aggregate->peekEvents();
        $this->assertCount(4, $events); // Create + 2 name changes + deactivate
    }

    // ========== Event Release Tests ==========

    public function testReleaseEventsReturnsAllEvents(): void
    {
        $aggregate = $this->createAggregate();
        $aggregate->changeName('New Name', $this->createActorId());

        $events = $aggregate->releaseEvents();

        $this->assertCount(2, $events);
    }

    public function testReleaseEventsClearsEvents(): void
    {
        $aggregate = $this->createAggregate();
        $aggregate->changeName('New Name', $this->createActorId());

        $aggregate->releaseEvents();

        $this->assertFalse($aggregate->hasUncommittedEvents());
        $this->assertCount(0, $aggregate->peekEvents());
    }

    public function testReleaseEventsReturnsEmptyArrayWhenNoEvents(): void
    {
        $aggregate = $this->createAggregate();
        $aggregate->releaseEvents();

        $events = $aggregate->releaseEvents();

        $this->assertIsArray($events);
        $this->assertCount(0, $events);
    }

    // ========== Event Ordering Tests ==========

    public function testEventsAreInOrder(): void
    {
        $aggregate = $this->createAggregate();
        $actorId = $this->createActorId();

        $aggregate->changeName('Name 1', $actorId);
        $aggregate->changeName('Name 2', $actorId);

        $events = $aggregate->peekEvents();

        $this->assertInstanceOf(TestAggregateCreated::class, $events[0]);
        $this->assertInstanceOf(TestAggregateNameChanged::class, $events[1]);
        $this->assertInstanceOf(TestAggregateNameChanged::class, $events[2]);
    }

    public function testVersionMatchesEventCount(): void
    {
        $aggregate = $this->createAggregate();
        $actorId = $this->createActorId();

        $this->assertSame(1, $aggregate->version());

        $aggregate->changeName('Name 1', $actorId);
        $this->assertSame(2, $aggregate->version());

        $aggregate->changeName('Name 2', $actorId);
        $this->assertSame(3, $aggregate->version());
    }
}

final class AggregateReconstitutionTest extends KernelTestCase
{
    private function createTenantId(): TenantId
    {
        return TenantId::generate();
    }

    private function createActorId(): ActorId
    {
        return ActorId::generate();
    }

    // ========== Reconstitution Tests ==========

    public function testReconstituteFromSingleEvent(): void
    {
        $tenantId = $this->createTenantId();
        $actorId = $this->createActorId();

        $event = new TestAggregateCreated(
            'agg-123',
            $tenantId->toString(),
            'Test Name',
            new \DateTimeImmutable(),
            $actorId->toString()
        );

        $aggregate = TestAggregate::reconstitute('agg-123', $tenantId, [$event]);

        $this->assertSame('agg-123', $aggregate->id());
        $this->assertSame('Test Name', $aggregate->name());
        $this->assertTrue($aggregate->isActive());
        $this->assertTrue($aggregate->isCreated());
    }

    public function testReconstituteFromMultipleEvents(): void
    {
        $tenantId = $this->createTenantId();
        $actorId = $this->createActorId();

        $events = [
            new TestAggregateCreated(
                'agg-123',
                $tenantId->toString(),
                'Original Name',
                new \DateTimeImmutable(),
                $actorId->toString()
            ),
            new TestAggregateNameChanged(
                'agg-123',
                'Changed Name',
                new \DateTimeImmutable(),
                $actorId->toString()
            ),
        ];

        $aggregate = TestAggregate::reconstitute('agg-123', $tenantId, $events);

        $this->assertSame('Changed Name', $aggregate->name());
        $this->assertSame(1, $aggregate->nameChangeCount());
    }

    public function testReconstituteSetsCorrectVersion(): void
    {
        $tenantId = $this->createTenantId();
        $actorId = $this->createActorId();

        $events = [
            new TestAggregateCreated(
                'agg-123',
                $tenantId->toString(),
                'Name',
                new \DateTimeImmutable(),
                $actorId->toString()
            ),
            new TestAggregateNameChanged(
                'agg-123',
                'Name 1',
                new \DateTimeImmutable(),
                $actorId->toString()
            ),
            new TestAggregateNameChanged(
                'agg-123',
                'Name 2',
                new \DateTimeImmutable(),
                $actorId->toString()
            ),
        ];

        $aggregate = TestAggregate::reconstitute('agg-123', $tenantId, $events);

        $this->assertSame(3, $aggregate->version());
    }

    public function testReconstituteNoUncommittedEvents(): void
    {
        $tenantId = $this->createTenantId();
        $actorId = $this->createActorId();

        $event = new TestAggregateCreated(
            'agg-123',
            $tenantId->toString(),
            'Test Name',
            new \DateTimeImmutable(),
            $actorId->toString()
        );

        $aggregate = TestAggregate::reconstitute('agg-123', $tenantId, [$event]);

        $this->assertFalse($aggregate->hasUncommittedEvents());
    }

    public function testReconstituteDeterministic(): void
    {
        $tenantId = $this->createTenantId();
        $actorId = $this->createActorId();

        $events = [
            new TestAggregateCreated(
                'agg-123',
                $tenantId->toString(),
                'Name',
                new \DateTimeImmutable(),
                $actorId->toString()
            ),
            new TestAggregateNameChanged(
                'agg-123',
                'Changed',
                new \DateTimeImmutable(),
                $actorId->toString()
            ),
        ];

        $aggregate1 = TestAggregate::reconstitute('agg-123', $tenantId, $events);
        $aggregate2 = TestAggregate::reconstitute('agg-123', $tenantId, $events);

        $this->assertSame($aggregate1->name(), $aggregate2->name());
        $this->assertSame($aggregate1->version(), $aggregate2->version());
        $this->assertSame($aggregate1->isActive(), $aggregate2->isActive());
    }

    public function testReconstituteWithUnknownEventType(): void
    {
        $tenantId = $this->createTenantId();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unknown event type');

        TestAggregate::reconstitute('agg-123', $tenantId, [new \stdClass()]);
    }

    public function testReconstituteWithDeactivation(): void
    {
        $tenantId = $this->createTenantId();
        $actorId = $this->createActorId();

        $events = [
            new TestAggregateCreated(
                'agg-123',
                $tenantId->toString(),
                'Name',
                new \DateTimeImmutable(),
                $actorId->toString()
            ),
            new TestAggregateDeactivated(
                'agg-123',
                'Test reason',
                new \DateTimeImmutable(),
                $actorId->toString()
            ),
        ];

        $aggregate = TestAggregate::reconstitute('agg-123', $tenantId, $events);

        $this->assertFalse($aggregate->isActive());
    }

    public function testReconstituteWithReactivation(): void
    {
        $tenantId = $this->createTenantId();
        $actorId = $this->createActorId();

        $events = [
            new TestAggregateCreated(
                'agg-123',
                $tenantId->toString(),
                'Name',
                new \DateTimeImmutable(),
                $actorId->toString()
            ),
            new TestAggregateDeactivated(
                'agg-123',
                'Test reason',
                new \DateTimeImmutable(),
                $actorId->toString()
            ),
            new TestAggregateActivated(
                'agg-123',
                new \DateTimeImmutable(),
                $actorId->toString()
            ),
        ];

        $aggregate = TestAggregate::reconstitute('agg-123', $tenantId, $events);

        $this->assertTrue($aggregate->isActive());
    }

    public function testReconstituteFromEmptyEventList(): void
    {
        $tenantId = $this->createTenantId();

        $aggregate = TestAggregate::reconstitute('agg-123', $tenantId, []);

        $this->assertSame('agg-123', $aggregate->id());
        $this->assertFalse($aggregate->isCreated());
        $this->assertSame(0, $aggregate->version());
    }
}

final class AggregateTenantIsolationTest extends KernelTestCase
{
    private function createActorId(): ActorId
    {
        return ActorId::generate();
    }

    // ========== Tenant Isolation Tests ==========

    public function testAggregatePreservesTenantId(): void
    {
        $tenantId = TenantId::generate();

        $aggregate = TestAggregate::create(
            'agg-123',
            $tenantId,
            'Test Name',
            $this->createActorId()
        );

        $this->assertTrue($aggregate->tenantId()->equals($tenantId));
    }

    public function testTenantIdImmutableAfterCreation(): void
    {
        $tenantId = TenantId::generate();

        $aggregate = TestAggregate::create(
            'agg-123',
            $tenantId,
            'Test Name',
            $this->createActorId()
        );

        $originalTenantId = $aggregate->tenantId();
        $aggregate->changeName('New Name', $this->createActorId());

        $this->assertTrue($aggregate->tenantId()->equals($originalTenantId));
    }

    public function testTenantIdInEvent(): void
    {
        $tenantId = TenantId::generate();

        $aggregate = TestAggregate::create(
            'agg-123',
            $tenantId,
            'Test Name',
            $this->createActorId()
        );

        /** @var TestAggregateCreated $event */
        $event = $aggregate->peekEvents()[0];

        $this->assertSame($tenantId->toString(), $event->tenantId);
    }
}
