<?php

declare(strict_types=1);

namespace Spiral\Kernel\Tests\Integration\EventStore;

use PHPUnit\Framework\TestCase;
use Spiral\Kernel\Domain\Identity\ActorId;
use Spiral\Kernel\Domain\Identity\CausationId;
use Spiral\Kernel\Domain\Identity\CorrelationId;
use Spiral\Kernel\Domain\Identity\TenantId;
use Spiral\Kernel\Domain\Shared\Aggregate\AggregateRoot;
use Spiral\Kernel\Domain\Shared\Event\DomainEvent;
use Spiral\Kernel\Domain\Shared\Event\ExpectedVersion;
use Spiral\Kernel\Domain\Shared\Event\StoredEvent;
use Spiral\Kernel\Infrastructure\Contract\EventStore\IEventStore;
use Spiral\Kernel\Infrastructure\Persistence\EventStore\PostgreSqlEventStore;
use Spiral\Kernel\Support\Exception\ConcurrencyConflictException;
use Spiral\Kernel\Tests\Fixture\Event\TestAggregateCreated;
use Spiral\Kernel\Tests\Fixture\Event\TestAggregateRenamed;

/**
 * Test aggregate for integration testing.
 */
final class TestAggregate extends AggregateRoot
{
    private string $name = '';

    public function getName(): string
    {
        return $this->name;
    }

    public static function create(
        string $id,
        TenantId $tenantId,
        string $name,
        ActorId $createdBy,
    ): self {
        $aggregate = new self($id, $tenantId);
        $aggregate->markAsNew();

        $event = new TestAggregateCreated(
            aggregateId: $id,
            tenantId: $tenantId,
            name: $name,
            createdBy: $createdBy,
            correlationId: CorrelationId::generate(),
            causationId: CausationId::generate(),
        );

        $aggregate->raise($event);
        return $aggregate;
    }

    public function rename(string $newName, ActorId $renamedBy): void
    {
        $event = new TestAggregateRenamed(
            aggregateId: $this->getId(),
            tenantId: $this->getTenantId(),
            newName: $newName,
            renamedBy: $renamedBy,
            correlationId: CorrelationId::generate(),
            causationId: CausationId::generate(),
        );

        $this->raise($event);
    }

    protected function apply(object $event): void
    {
        match (true) {
            $event instanceof TestAggregateCreated => $this->name = $event->name,
            $event instanceof TestAggregateRenamed => $this->name = $event->newName,
            default => throw new \RuntimeException('Unknown event type: ' . $event::class),
        };
    }

    public static function getStreamId(TenantId $tenantId, string $aggregateId): string
    {
        return \sprintf('TestAggregate:%s:%s', $tenantId->toString(), $aggregateId);
    }
}

/**
 * Integration tests for Event Store runtime spine.
 *
 * Tests:
 * - Create → persist
 * - Persist → reload (reconstitution)
 * - Replay correctness
 * - Stale version conflict
 * - Tenant isolation
 * - Serialization roundtrip
 */
class EventStoreRuntimeTest extends TestCase
{
    private ?PostgreSqlEventStore $eventStore = null;
    private ?\PDO $connection = null;
    private TenantId $tenantA;
    private TenantId $tenantB;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
            $port = $_ENV['DB_PORT'] ?? '5432';
            $db = $_ENV['DB_DATABASE'] ?? 'epsilone_kernel_test';
            $user = $_ENV['DB_USER'] ?? 'postgres';
            $pass = $_ENV['DB_PASSWORD'] ?? 'password';

            $dsn = "pgsql:host={$host};port={$port};dbname={$db}";
            $this->connection = new \PDO($dsn, $user, $pass, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]);

            $this->ensureTableExists();
            $this->eventStore = new PostgreSqlEventStore($this->connection);

            $this->tenantA = TenantId::fromString('11111111-1111-1111-1111-111111111111');
            $this->tenantB = TenantId::fromString('22222222-2222-2222-2222-222222222222');
        } catch (\PDOException $e) {
            $this->markTestSkipped('Database not available: ' . $e->getMessage());
        }
    }

    private function ensureTableExists(): void
    {
        $this->connection->exec('
            CREATE TABLE IF NOT EXISTS event_store (
                id BIGSERIAL PRIMARY KEY,
                event_id VARCHAR(36) NOT NULL,
                tenant_id VARCHAR(36) NOT NULL,
                stream_id VARCHAR(255) NOT NULL,
                stream_version INTEGER NOT NULL,
                event_type VARCHAR(255) NOT NULL,
                event_class_name VARCHAR(512) NOT NULL,
                payload JSONB NOT NULL,
                metadata JSONB NOT NULL,
                occurred_at TIMESTAMPTZ NOT NULL,
                UNIQUE (stream_id, stream_version)
            )
        ');

        $this->connection->exec('
            CREATE INDEX IF NOT EXISTS idx_event_store_tenant_stream 
            ON event_store (tenant_id, stream_id, stream_version)
        ');
    }

    protected function tearDown(): void
    {
        if ($this->connection !== null) {
            $this->connection->exec('TRUNCATE TABLE event_store RESTART IDENTITY CASCADE');
        }
        parent::tearDown();
    }

    public function testCreateAndPersist(): void
    {
        $aggregate = TestAggregate::create(
            'agg-001',
            $this->tenantA,
            'Test Aggregate',
            ActorId::generate(),
        );

        $streamId = TestAggregate::getStreamId($this->tenantA, 'agg-001');
        $events = $aggregate->popUncommittedEvents();

        $version = $this->eventStore->append(
            $this->tenantA,
            $streamId,
            ExpectedVersion::noStream(),
            $events,
        );

        $this->assertEquals(2, $version); // 2 events: created + initial state
        $this->assertTrue($this->eventStore->streamExists($this->tenantA, $streamId));
    }

    public function testPersistAndReload(): void
    {
        // Create and persist
        $aggregate = TestAggregate::create(
            'agg-002',
            $this->tenantA,
            'Original Name',
            ActorId::generate(),
        );

        $streamId = TestAggregate::getStreamId($this->tenantA, 'agg-002');
        $events = $aggregate->popUncommittedEvents();

        $this->eventStore->append(
            $this->tenantA,
            $streamId,
            ExpectedVersion::noStream(),
            $events,
        );

        // Reload from event store
        $storedEvents = $this->eventStore->load($this->tenantA, $streamId);

        $this->assertCount(2, $storedEvents); // Created + name event

        // Reconstitute aggregate
        $reconstituted = new TestAggregate('agg-002', $this->tenantA);
        $domainEvents = $this->storedEventsToDomainEvents($storedEvents);
        $reconstituted->reconstituteFromEvents($domainEvents, \count($storedEvents));

        $this->assertEquals('Original Name', $reconstituted->getName());
    }

    public function testReplayCorrectness(): void
    {
        // Create aggregate with multiple events
        $aggregate = TestAggregate::create(
            'agg-003',
            $this->tenantA,
            'Name 1',
            ActorId::generate(),
        );

        $streamId = TestAggregate::getStreamId($this->tenantA, 'agg-003');
        $events = $aggregate->popUncommittedEvents();
        $this->eventStore->append($this->tenantA, $streamId, ExpectedVersion::noStream(), $events);

        // Add more events
        $aggregate->rename('Name 2', ActorId::generate());
        $events = $aggregate->popUncommittedEvents();
        $this->eventStore->append($this->tenantA, $streamId, ExpectedVersion::exact(2), $events);

        $aggregate->rename('Name 3', ActorId::generate());
        $events = $aggregate->popUncommittedEvents();
        $this->eventStore->append($this->tenantA, $streamId, ExpectedVersion::exact(3), $events);

        // Reload and verify final state
        $storedEvents = $this->eventStore->load($this->tenantA, $streamId);
        $reconstituted = new TestAggregate('agg-003', $this->tenantA);
        $domainEvents = $this->storedEventsToDomainEvents($storedEvents);
        $reconstituted->reconstituteFromEvents($domainEvents, \count($storedEvents));

        $this->assertEquals('Name 3', $reconstituted->getName());
        $this->assertEquals(4, $reconstituted->getVersion());
    }

    public function testStaleVersionConflict(): void
    {
        $aggregate = TestAggregate::create(
            'agg-004',
            $this->tenantA,
            'Test',
            ActorId::generate(),
        );

        $streamId = TestAggregate::getStreamId($this->tenantA, 'agg-004');
        $events = $aggregate->popUncommittedEvents();
        $this->eventStore->append($this->tenantA, $streamId, ExpectedVersion::noStream(), $events);

        // Try to append with stale version
        $this->expectException(ConcurrencyConflictException::class);

        $aggregate2 = TestAggregate::create(
            'agg-004',
            $this->tenantA,
            'Another',
            ActorId::generate(),
        );
        $events2 = $aggregate2->popUncommittedEvents();

        // Try to append at version 0 when stream is at version 2
        $this->eventStore->append(
            $this->tenantA,
            $streamId,
            ExpectedVersion::noStream(), // Expects empty stream but it's not
            $events2,
        );
    }

    public function testTenantIsolation(): void
    {
        // Create aggregate for tenant A
        $aggregate = TestAggregate::create(
            'agg-005',
            $this->tenantA,
            'Tenant A Data',
            ActorId::generate(),
        );

        $streamId = TestAggregate::getStreamId($this->tenantA, 'agg-005');
        $events = $aggregate->popUncommittedEvents();
        $this->eventStore->append($this->tenantA, $streamId, ExpectedVersion::noStream(), $events);

        // Tenant B should not see Tenant A's data
        $this->assertFalse($this->eventStore->streamExists($this->tenantB, $streamId));

        // Tenant B can create their own aggregate with same ID
        $aggregateB = TestAggregate::create(
            'agg-005',
            $this->tenantB,
            'Tenant B Data',
            ActorId::generate(),
        );

        $streamIdB = TestAggregate::getStreamId($this->tenantB, 'agg-005');
        $eventsB = $aggregateB->popUncommittedEvents();
        $this->eventStore->append($this->tenantB, $streamIdB, ExpectedVersion::noStream(), $eventsB);

        // Verify separate streams
        $this->assertTrue($this->eventStore->streamExists($this->tenantA, $streamId));
        $this->assertTrue($this->eventStore->streamExists($this->tenantB, $streamIdB));

        // Load and verify isolation
        $eventsA = $this->eventStore->load($this->tenantA, $streamId);
        $eventsB = $this->eventStore->load($this->tenantB, $streamIdB);

        $this->assertStringContainsString('Tenant A Data', \json_encode($eventsA[0]->payload));
        $this->assertStringContainsString('Tenant B Data', \json_encode($eventsB[0]->payload));
    }

    public function testSerializationRoundtrip(): void
    {
        $aggregate = TestAggregate::create(
            'agg-006',
            $this->tenantA,
            'Serialization Test',
            ActorId::generate(),
        );

        $streamId = TestAggregate::getStreamId($this->tenantA, 'agg-006');
        $events = $aggregate->popUncommittedEvents();

        $this->eventStore->append($this->tenantA, $streamId, ExpectedVersion::noStream(), $events);

        // Load events
        $storedEvents = $this->eventStore->load($this->tenantA, $streamId);

        // Verify stored event structure
        /** @var StoredEvent $storedEvent */
        $storedEvent = $storedEvents[0];

        $this->assertNotEmpty($storedEvent->eventId->toString());
        $this->assertEquals($this->tenantA->toString(), $storedEvent->tenantId->toString());
        $this->assertEquals($streamId, $storedEvent->streamId);
        $this->assertIsArray($storedEvent->payload);
        $this->assertIsArray($storedEvent->metadata->toArray());
    }

    public function testEmptyStreamBehavior(): void
    {
        $streamId = 'non-existent-stream';

        $this->assertFalse($this->eventStore->streamExists($this->tenantA, $streamId));
        $this->assertEquals(0, $this->eventStore->getStreamVersion($this->tenantA, $streamId));

        $events = $this->eventStore->load($this->tenantA, $streamId);
        $this->assertEmpty($events);
    }

    /**
     * Test: Exact version acceptance for various version numbers.
     */
    public function testExactVersionAcceptance(): void
    {
        // Create aggregate - this creates stream with version 2 (created + initial state)
        $aggregate = TestAggregate::create(
            'exact-version-test',
            $this->tenantA,
            'Test',
            ActorId::generate(),
        );

        $streamId = TestAggregate::getStreamId($this->tenantA, 'exact-version-test');
        $events = $aggregate->popUncommittedEvents();
        $this->eventStore->append($this->tenantA, $streamId, ExpectedVersion::noStream(), $events);

        // Verify stream is at version 2
        $this->assertEquals(2, $this->eventStore->getStreamVersion($this->tenantA, $streamId));

        // Test exact(2) - should accept and advance to version 3
        $aggregate->rename('Name 2', ActorId::generate());
        $events = $aggregate->popUncommittedEvents();
        $newVersion = $this->eventStore->append($this->tenantA, $streamId, ExpectedVersion::exact(2), $events);
        $this->assertEquals(3, $newVersion);

        // Test exact(3) - should accept and advance to version 4
        $aggregate->rename('Name 3', ActorId::generate());
        $events = $aggregate->popUncommittedEvents();
        $newVersion = $this->eventStore->append($this->tenantA, $streamId, ExpectedVersion::exact(3), $events);
        $this->assertEquals(4, $newVersion);

        // Test exact(4) - should accept and advance to version 5
        $aggregate->rename('Name 4', ActorId::generate());
        $events = $aggregate->popUncommittedEvents();
        $newVersion = $this->eventStore->append($this->tenantA, $streamId, ExpectedVersion::exact(4), $events);
        $this->assertEquals(5, $newVersion);
    }

    /**
     * Test: Exact version rejection when version doesn't match.
     */
    public function testExactVersionRejectionStale(): void
    {
        // Create aggregate with 2 events (version 2)
        $aggregate = TestAggregate::create(
            'stale-version-test',
            $this->tenantA,
            'Test',
            ActorId::generate(),
        );

        $streamId = TestAggregate::getStreamId($this->tenantA, 'stale-version-test');
        $events = $aggregate->popUncommittedEvents();
        $this->eventStore->append($this->tenantA, $streamId, ExpectedVersion::noStream(), $events);

        // Add one more event to advance stream to version 3
        $aggregate->rename('Name 2', ActorId::generate());
        $events = $aggregate->popUncommittedEvents();
        $this->eventStore->append($this->tenantA, $streamId, ExpectedVersion::exact(2), $events);

        // Stream is now at version 3
        $this->assertEquals(3, $this->eventStore->getStreamVersion($this->tenantA, $streamId));

        // Try to append with stale version 2 - should fail
        $this->expectException(ConcurrencyConflictException::class);

        $staleAggregate = TestAggregate::create(
            'stale-version-test',
            $this->tenantA,
            'Stale',
            ActorId::generate(),
        );
        $staleEvents = $staleAggregate->popUncommittedEvents();

        $this->eventStore->append(
            $this->tenantA,
            $streamId,
            ExpectedVersion::exact(2), // Stream is at 3, expecting 2
            $staleEvents,
        );
    }

    /**
     * Test: Exact version rejection when expecting future version.
     */
    public function testExactVersionRejectionFuture(): void
    {
        // Create aggregate with 2 events (version 2)
        $aggregate = TestAggregate::create(
            'future-version-test',
            $this->tenantA,
            'Test',
            ActorId::generate(),
        );

        $streamId = TestAggregate::getStreamId($this->tenantA, 'future-version-test');
        $events = $aggregate->popUncommittedEvents();
        $this->eventStore->append($this->tenantA, $streamId, ExpectedVersion::noStream(), $events);

        // Stream is at version 2
        $this->assertEquals(2, $this->eventStore->getStreamVersion($this->tenantA, $streamId));

        // Try to append expecting version 5 (future) - should fail
        $this->expectException(ConcurrencyConflictException::class);

        $futureAggregate = TestAggregate::create(
            'future-version-test',
            $this->tenantA,
            'Future',
            ActorId::generate(),
        );
        $futureEvents = $futureAggregate->popUncommittedEvents();

        $this->eventStore->append(
            $this->tenantA,
            $streamId,
            ExpectedVersion::exact(5), // Stream is at 2, expecting 5
            $futureEvents,
        );
    }

    /**
     * Test: noStream() mode rejects when stream already exists.
     */
    public function testNoStreamRejection(): void
    {
        // Create aggregate
        $aggregate = TestAggregate::create(
            'no-stream-reject-test',
            $this->tenantA,
            'Test',
            ActorId::generate(),
        );

        $streamId = TestAggregate::getStreamId($this->tenantA, 'no-stream-reject-test');
        $events = $aggregate->popUncommittedEvents();
        $this->eventStore->append($this->tenantA, $streamId, ExpectedVersion::noStream(), $events);

        // Stream now exists
        $this->assertTrue($this->eventStore->streamExists($this->tenantA, $streamId));

        // Try to create another aggregate with noStream() - should fail
        $this->expectException(ConcurrencyConflictException::class);

        $duplicateAggregate = TestAggregate::create(
            'no-stream-reject-test',
            $this->tenantA,
            'Duplicate',
            ActorId::generate(),
        );
        $duplicateEvents = $duplicateAggregate->popUncommittedEvents();

        $this->eventStore->append(
            $this->tenantA,
            $streamId,
            ExpectedVersion::noStream(), // Stream exists, should reject
            $duplicateEvents,
        );
    }

    /**
     * Test: any() mode always permits append regardless of version.
     */
    public function testAnyModeAlwaysPermits(): void
    {
        // Create aggregate
        $aggregate = TestAggregate::create(
            'any-mode-test',
            $this->tenantA,
            'Test',
            ActorId::generate(),
        );

        $streamId = TestAggregate::getStreamId($this->tenantA, 'any-mode-test');
        $events = $aggregate->popUncommittedEvents();
        $this->eventStore->append($this->tenantA, $streamId, ExpectedVersion::noStream(), $events);

        // Add multiple events using any() - should always succeed
        for ($i = 1; $i <= 5; $i++) {
            $aggregate->rename("Name {$i}", ActorId::generate());
            $events = $aggregate->popUncommittedEvents();
            $newVersion = $this->eventStore->append(
                $this->tenantA,
                $streamId,
                ExpectedVersion::any(), // Always permits
                $events
            );
            $this->assertEquals(2 + $i, $newVersion);
        }

        // Verify final version
        $this->assertEquals(7, $this->eventStore->getStreamVersion($this->tenantA, $streamId));
    }

    /**
     * @param list<StoredEvent> $storedEvents
     * @return list<DomainEvent>
     */
    private function storedEventsToDomainEvents(array $storedEvents): array
    {
        // In real implementation, this would use an event upgrader/deserializer
        // For testing, we reconstruct from stored payload
        $events = [];
        foreach ($storedEvents as $stored) {
            $className = $stored->eventClassName;
            if (\class_exists($className)) {
                $events[] = new $className(
                    $stored->payload['aggregateId'],
                    $stored->tenantId,
                    $stored->payload['name'] ?? $stored->payload['newName'] ?? '',
                    ActorId::fromString($stored->payload['createdBy'] ?? $stored->payload['renamedBy'] ?? ActorId::system()->toString()),
                    CorrelationId::fromString($stored->payload['correlationId']),
                    CausationId::fromString($stored->payload['causationId']),
                );
            }
        }
        return $events;
    }
}
