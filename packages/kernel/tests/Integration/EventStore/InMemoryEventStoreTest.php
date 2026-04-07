<?php

declare(strict_types=1);

namespace Spiral\Kernel\Tests\Integration\EventStore;

use PHPUnit\Framework\TestCase;
use Spiral\Kernel\Domain\Identity\TenantId;
use Spiral\Kernel\Domain\Identity\EventId;
use Spiral\Kernel\Domain\Identity\CorrelationId;
use Spiral\Kernel\Domain\Identity\CausationId;
use Spiral\Kernel\Domain\Shared\Event\ExpectedVersion;
use Spiral\Kernel\Support\Exception\ConcurrencyConflictException;
use Spiral\Kernel\Tests\Fixture\Aggregate\TestAggregateCreated;
use Spiral\Kernel\Tests\Fixture\Aggregate\TestAggregateNameChanged;
use Spiral\Kernel\Tests\Fixture\Aggregate\TestAggregateActivated;
use Spiral\Kernel\Tests\Fixture\Aggregate\TestAggregateDeactivated;
use Spiral\Kernel\Tests\Fixture\Persistence\InMemoryEventStore;
use DateTimeImmutable;

// Ensure test event classes are available
require_once __DIR__ . '/../../Fixture/Aggregate/TestAggregateEvents.php';

/**
 * Integration tests for InMemoryEventStore.
 *
 * Verifies:
 * - Event persistence per stream
 * - Optimistic concurrency control
 * - Tenant isolation
 * - Deterministic replay
 */
final class InMemoryEventStoreTest extends TestCase
{
    private InMemoryEventStore $store;
    private TenantId $tenant1;
    private TenantId $tenant2;

    protected function setUp(): void
    {
        $this->store = new InMemoryEventStore();
        $this->tenant1 = TenantId::fromString('00000000-0000-0000-0000-000000000001');
        $this->tenant2 = TenantId::fromString('00000000-0000-0000-0000-000000000002');
    }

    /** @test */
    public function event_persistence_roundtrip(): void
    {
        // Create deterministic test events
        $event1 = TestAggregateCreated::forTest('agg-1', 'Test Aggregate', new DateTimeImmutable('2026-01-01'), 'actor-1');
        $event2 = TestAggregateNameChanged::forTest('agg-1', 'Updated Name', new DateTimeImmutable('2026-01-02'), 'actor-1');

        // Append events
        $newVersion = $this->store->append(
            $this->tenant1,
            'aggregate:agg-1',
            ExpectedVersion::noStream(),
            [$event1, $event2]
        );

        // Verify new version matches event count
        $this->assertSame(2, $newVersion);

        // Load events back
        $loaded = $this->store->load($this->tenant1, 'aggregate:agg-1');

        $this->assertCount(2, $loaded);
        $this->assertSame('TestAggregateCreated', $loaded[0]->eventType);
        $this->assertSame('TestAggregateNameChanged', $loaded[1]->eventType);
        $this->assertSame(1, $loaded[0]->streamVersion);
        $this->assertSame(2, $loaded[1]->streamVersion);
    }

    /** @test */
    public function optimistic_concurrency_enforced(): void
    {
        $event1 = TestAggregateCreated::forTest('agg-2', 'Test', new DateTimeImmutable(), 'actor');
        $event2 = TestAggregateNameChanged::forTest('agg-2', 'Updated', new DateTimeImmutable(), 'actor');

        // First append succeeds
        $this->store->append($this->tenant1, 'aggregate:agg-2', ExpectedVersion::noStream(), [$event1]);

        // Second append with wrong version fails
        $this->expectException(ConcurrencyConflictException::class);
        $this->store->append(
            $this->tenant1,
            'aggregate:agg-2',
            ExpectedVersion::exact(999),  // Wrong version
            [$event2]
        );
    }

    /** @test */
    public function correct_version_succeeds(): void
    {
        $event1 = TestAggregateCreated::forTest('agg-3', 'Test', new DateTimeImmutable(), 'actor');
        $event2 = TestAggregateNameChanged::forTest('agg-3', 'Updated', new DateTimeImmutable(), 'actor');

        // First append with noStream
        $v1 = $this->store->append($this->tenant1, 'aggregate:agg-3', ExpectedVersion::noStream(), [$event1]);
        $this->assertSame(1, $v1);

        // Second append with exact version succeeds
        $v2 = $this->store->append($this->tenant1, 'aggregate:agg-3', ExpectedVersion::exact(1), [$event2]);
        $this->assertSame(2, $v2);

        // Verify both events are persisted
        $loaded = $this->store->load($this->tenant1, 'aggregate:agg-3');
        $this->assertCount(2, $loaded);
    }

    /** @test */
    public function tenant_isolation_enforced(): void
    {
        $event1 = TestAggregateCreated::forTest('agg-4', 'Test', new DateTimeImmutable(), 'actor');

        // Append to tenant1
        $this->store->append($this->tenant1, 'aggregate:agg-4', ExpectedVersion::noStream(), [$event1]);

        // Events should not be visible to tenant2
        $tenant2Events = $this->store->load($this->tenant2, 'aggregate:agg-4');
        $this->assertEmpty($tenant2Events);

        // But should be visible to tenant1
        $tenant1Events = $this->store->load($this->tenant1, 'aggregate:agg-4');
        $this->assertCount(1, $tenant1Events);
    }

    /** @test */
    public function replay_deterministic(): void
    {
        $event = TestAggregateCreated::forTest(
            'agg-5',
            'Test Aggregate',
            new DateTimeImmutable('2026-01-01T12:00:00Z'),
            'actor-id'
        );

        // Append event
        $this->store->append($this->tenant1, 'aggregate:agg-5', ExpectedVersion::noStream(), [$event]);

        // Load multiple times, should get identical results
        $load1 = $this->store->load($this->tenant1, 'aggregate:agg-5');
        $load2 = $this->store->load($this->tenant1, 'aggregate:agg-5');
        $load3 = $this->store->load($this->tenant1, 'aggregate:agg-5');

        // All loads should return identical events
        $this->assertCount(1, $load1);
        $this->assertCount(1, $load2);
        $this->assertCount(1, $load3);

        $this->assertSame($load1[0]->eventId->toString(), $load2[0]->eventId->toString());
        $this->assertSame($load1[0]->eventId->toString(), $load3[0]->eventId->toString());

        // Metadata should also be identical
        $this->assertSame(
            $load1[0]->metadata->eventId->toString(),
            $load2[0]->metadata->eventId->toString()
        );
    }

    /** @test */
    public function load_from_version_filters_correctly(): void
    {
        $event1 = TestAggregateCreated::forTest('agg-6', 'Event 1', new DateTimeImmutable(), 'actor');
        $event2 = TestAggregateNameChanged::forTest('agg-6', 'Event 2', new DateTimeImmutable(), 'actor');
        $event3 = TestAggregateNameChanged::forTest('agg-6', 'Event 3', new DateTimeImmutable(), 'actor');

        // Append all events
        $this->store->append(
            $this->tenant1,
            'aggregate:agg-6',
            ExpectedVersion::noStream(),
            [$event1, $event2, $event3]
        );

        // Load from version 2 should skip event 1
        $fromV2 = $this->store->load($this->tenant1, 'aggregate:agg-6', 2);
        $this->assertCount(2, $fromV2);
        $this->assertSame(2, $fromV2[0]->streamVersion);
        $this->assertSame(3, $fromV2[1]->streamVersion);

        // Load from version 3 should only get event 3
        $fromV3 = $this->store->load($this->tenant1, 'aggregate:agg-6', 3);
        $this->assertCount(1, $fromV3);
        $this->assertSame(3, $fromV3[0]->streamVersion);
    }

    /** @test */
    public function nonexistent_stream_returns_empty(): void
    {
        $loaded = $this->store->load($this->tenant1, 'nonexistent:stream');
        $this->assertEmpty($loaded);

        $version = $this->store->getStreamVersion($this->tenant1, 'nonexistent:stream');
        $this->assertSame(0, $version);
    }

    /** @test */
    public function stream_version_tracking_accurate(): void
    {
        $event = TestAggregateCreated::forTest('agg-7', 'Test', new DateTimeImmutable(), 'actor');

        // Initial version is 0
        $v0 = $this->store->getStreamVersion($this->tenant1, 'aggregate:agg-7');
        $this->assertSame(0, $v0);

        // After first append, version is 1
        $this->store->append($this->tenant1, 'aggregate:agg-7', ExpectedVersion::noStream(), [$event]);
        $v1 = $this->store->getStreamVersion($this->tenant1, 'aggregate:agg-7');
        $this->assertSame(1, $v1);

        // Append more events
        $event2 = TestAggregateNameChanged::forTest('agg-7', 'Updated', new DateTimeImmutable(), 'actor');
        $this->store->append($this->tenant1, 'aggregate:agg-7', ExpectedVersion::exact(1), [$event2]);
        $v2 = $this->store->getStreamVersion($this->tenant1, 'aggregate:agg-7');
        $this->assertSame(2, $v2);
    }

    /** @test */
    public function any_version_allows_append_regardless(): void
    {
        $event1 = TestAggregateCreated::forTest('agg-8', 'Event 1', new DateTimeImmutable(), 'actor');
        $event2 = TestAggregateNameChanged::forTest('agg-8', 'Event 2', new DateTimeImmutable(), 'actor');

        // Append event 1
        $this->store->append($this->tenant1, 'aggregate:agg-8', ExpectedVersion::noStream(), [$event1]);

        // Append event 2 with "any" version (should succeed even if we ignore real version)
        $v = $this->store->append(
            $this->tenant1,
            'aggregate:agg-8',
            ExpectedVersion::any(),
            [$event2]
        );

        $this->assertSame(2, $v);

        // Both events should be there
        $loaded = $this->store->load($this->tenant1, 'aggregate:agg-8');
        $this->assertCount(2, $loaded);
    }
}
