<?php

declare(strict_types=1);

namespace Spiral\Kernel\Tests\Integration\EventStore;

use PHPUnit\Framework\TestCase;
use Spiral\Kernel\Domain\Identity\ActorId;
use Spiral\Kernel\Domain\Identity\CorrelationId;
use Spiral\Kernel\Domain\Identity\CausationId;
use Spiral\Kernel\Domain\Identity\TenantId;
use Spiral\Kernel\Domain\Shared\Aggregate\AggregateRoot;
use Spiral\Kernel\Domain\Shared\Event\DomainEvent;
use Spiral\Kernel\Domain\Shared\Event\ExpectedVersion;
use Spiral\Kernel\Domain\Shared\Event\StoredEvent;
use Spiral\Kernel\Infrastructure\Persistence\EventStore\EventHydrator;
use Spiral\Kernel\Infrastructure\Persistence\EventStore\EventSerializer;
use Spiral\Kernel\Infrastructure\Persistence\EventStore\PostgreSqlEventStore;
use Spiral\Kernel\Support\Exception\ConcurrencyConflictException;
use Spiral\Kernel\Tests\Fixture\Event\TestAggregateCreated;
use Spiral\Kernel\Tests\Fixture\Event\TestAggregateRenamed;

/**
 * Test aggregate for hostile path testing.
 */
final class HostileTestAggregate extends AggregateRoot
{
    private string $name = '';

    public function getAggregateName(): string
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
        return \sprintf('HostileTestAggregate:%s:%s', $tenantId->toString(), $aggregateId);
    }
}

/**
 * Hostile-path integration tests for Event Store.
 *
 * Tests failure conditions, not just happy path:
 * - Stale concurrency under dual-load
 * - Cross-tenant spoofing attempts
 * - Malformed event data rejection
 * - Replay integrity under corruption
 */
class EventStoreHostilePathTest extends TestCase
{
    private ?PostgreSqlEventStore $eventStore = null;
    private ?\PDO $connection = null;
    private TenantId $tenantA;
    private TenantId $tenantB;
    private EventSerializer $serializer;
    private EventHydrator $hydrator;

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
            $this->serializer = new EventSerializer();
            $this->hydrator = new EventHydrator();

            $this->tenantA = TenantId::fromString('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa');
            $this->tenantB = TenantId::fromString('bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb');
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

    /**
     * Test: Stale concurrency under dual-load scenario.
     *
     * Scenario:
     * 1. Load same aggregate twice (simulating two concurrent requests)
     * 2. Mutate instance A
     * 3. Persist A
     * 4. Mutate instance B (stale)
     * 5. Persist B
     * 6. Assert concurrency exception is thrown
     */
    public function testStaleConcurrencyConflict(): void
    {
        // Step 1: Create and persist initial aggregate
        $aggregateA = HostileTestAggregate::create(
            'concurrency-test',
            $this->tenantA,
            'Initial Name',
            ActorId::generate(),
        );

        $streamId = HostileTestAggregate::getStreamId($this->tenantA, 'concurrency-test');
        $events = $aggregateA->popUncommittedEvents();
        $this->eventStore->append($this->tenantA, $streamId, ExpectedVersion::noStream(), $events);

        // Step 2: Load aggregate twice (simulating concurrent requests)
        $storedEvents = $this->eventStore->load($this->tenantA, $streamId);
        
        // Create two aggregate instances from same event stream
        $instanceA = new HostileTestAggregate('concurrency-test', $this->tenantA);
        $instanceA->reconstituteFromEvents($this->storedEventsToDomainEvents($storedEvents), \count($storedEvents));

        $instanceB = new HostileTestAggregate('concurrency-test', $this->tenantA);
        $instanceB->reconstituteFromEvents($this->storedEventsToDomainEvents($storedEvents), \count($storedEvents));

        // Step 3: Mutate instance A
        $instanceA->rename('Name from Instance A', ActorId::generate());
        $eventsA = $instanceA->popUncommittedEvents();

        // Step 4: Persist A (should succeed)
        $this->eventStore->append($this->tenantA, $streamId, ExpectedVersion::exact(2), $eventsA);

        // Step 5: Mutate instance B (stale - doesn't know about A's changes)
        $instanceB->rename('Name from Instance B', ActorId::generate());
        $eventsB = $instanceB->popUncommittedEvents();

        // Step 6: Try to persist B - should fail with concurrency conflict
        $this->expectException(ConcurrencyConflictException::class);
        
        $this->eventStore->append(
            $this->tenantA,
            $streamId,
            ExpectedVersion::exact(2), // Expects version 2, but stream is now at version 3
            $eventsB,
        );
    }

    /**
     * Test: Cross-tenant stream spoofing attempt.
     *
     * Scenario:
     * 1. Tenant A creates aggregate
     * 2. Tenant B tries to append to Tenant A's stream
     * 3. Should be rejected (streams are tenant-scoped)
     */
    public function testCrossTenantStreamSpoofingRejected(): void
    {
        // Tenant A creates aggregate
        $aggregateA = HostileTestAggregate::create(
            'spoof-test',
            $this->tenantA,
            'Tenant A Data',
            ActorId::generate(),
        );

        $streamId = HostileTestAggregate::getStreamId($this->tenantA, 'spoof-test');
        $events = $aggregateA->popUncommittedEvents();
        $this->eventStore->append($this->tenantA, $streamId, ExpectedVersion::noStream(), $events);

        // Verify Tenant A's stream exists
        $this->assertTrue($this->eventStore->streamExists($this->tenantA, $streamId));

        // Verify Tenant B cannot see Tenant A's stream
        $this->assertFalse($this->eventStore->streamExists($this->tenantB, $streamId));

        // Tenant B tries to load Tenant A's stream - should get empty/not found
        $events = $this->eventStore->load($this->tenantB, $streamId);
        $this->assertEmpty($events);

        // Tenant B creates their own aggregate with same ID
        $aggregateB = HostileTestAggregate::create(
            'spoof-test',
            $this->tenantB,
            'Tenant B Data',
            ActorId::generate(),
        );

        $streamIdB = HostileTestAggregate::getStreamId($this->tenantB, 'spoof-test');
        $eventsB = $aggregateB->popUncommittedEvents();
        $this->eventStore->append($this->tenantB, $streamIdB, ExpectedVersion::noStream(), $eventsB);

        // Verify separate streams
        $this->assertTrue($this->eventStore->streamExists($this->tenantA, $streamId));
        $this->assertTrue($this->eventStore->streamExists($this->tenantB, $streamIdB));

        // Verify data is isolated
        $tenantAEvents = $this->eventStore->load($this->tenantA, $streamId);
        $tenantBEvents = $this->eventStore->load($this->tenantB, $streamIdB);

        $this->assertStringContainsString('Tenant A Data', \json_encode($tenantAEvents[0]->payload));
        $this->assertStringContainsString('Tenant B Data', \json_encode($tenantBEvents[0]->payload));
    }

    /**
     * Test: Malformed persisted event fails loudly.
     *
     * Scenario:
     * 1. Insert invalid event data directly into database
     * 2. Try to hydrate
     * 3. Assert failure is deterministic and loud
     */
    public function testMalformedEventFailsLoudly(): void
    {
        // Insert malformed event directly into database
        $streamId = 'malformed-test-stream';
        
        $this->connection->exec(\sprintf(
            "INSERT INTO event_store (event_id, tenant_id, stream_id, stream_version, event_type, event_class_name, payload, metadata, occurred_at)
             VALUES ('%s', '%s', '%s', 0, 'TestEvent', 'NonExistentEventClass', '{\"invalid\": true}', '{}', NOW())",
            'cccccccc-cccc-cccc-cccc-cccccccccccc',
            $this->tenantA->toString(),
            $streamId
        ));

        // Try to load - should get the raw stored event
        $storedEvents = $this->eventStore->load($this->tenantA, $streamId);
        $this->assertCount(1, $storedEvents);

        // Try to hydrate with unknown class - should fail
        $this->expectException(\RuntimeException::class);
        $this->hydrator->hydrate($storedEvents[0]);
    }

    /**
     * Test: Missing required field in event payload fails.
     */
    public function testMissingRequiredFieldFails(): void
    {
        // Create a valid stored event but with missing required field in payload
        $streamId = 'missing-field-test-stream';
        
        // Insert event with missing 'tenantId' field
        $this->connection->exec(\sprintf(
            "INSERT INTO event_store (event_id, tenant_id, stream_id, stream_version, event_type, event_class_name, payload, metadata, occurred_at)
             VALUES ('%s', '%s', '%s', 0, 'TestAggregateCreated', 'Spiral\Kernel\Tests\Fixture\Event\TestAggregateCreated', '{\"aggregateId\": \"test-1\", \"name\": \"Test\"}', '{\"eventId\": \"%s\", \"tenantId\": \"%s\", \"correlationId\": \"%s\", \"causationId\": \"%s\", \"occurredAt\": \"2024-01-01T00:00:00+00:00\", \"schemaVersion\": \"1.0\"}', NOW())",
            'dddddddd-dddd-dddd-dddd-dddddddddddd',
            $this->tenantA->toString(),
            $streamId,
            'eeeeeeee-eeee-eeee-eeee-eeeeeeeeeeee',
            $this->tenantA->toString(),
            'ffffffff-ffff-ffff-ffff-ffffffffffff',
            'gggggggg-gggg-gggg-gggg-gggggggggggg'
        ));

        $storedEvents = $this->eventStore->load($this->tenantA, $streamId);
        
        // Hydration should fail due to missing tenantId in payload
        $this->expectException(\InvalidArgumentException::class);
        $this->hydrator->hydrate($storedEvents[0]);
    }

    /**
     * Test: Replay integrity with multiple events.
     *
     * Scenario:
     * 1. Create aggregate with multiple events (created, renamed x3)
     * 2. Persist all events
     * 3. Reload from persisted stream
     * 4. Assert exact final state matches
     */
    public function testReplayIntegrityMultipleEvents(): void
    {
        // Create aggregate
        $aggregate = HostileTestAggregate::create(
            'replay-test',
            $this->tenantA,
            'Name 0',
            ActorId::generate(),
        );

        $streamId = HostileTestAggregate::getStreamId($this->tenantA, 'replay-test');
        $events = $aggregate->popUncommittedEvents();
        $this->eventStore->append($this->tenantA, $streamId, ExpectedVersion::noStream(), $events);

        // Add multiple rename events
        for ($i = 1; $i <= 5; $i++) {
            $aggregate->rename("Name {$i}", ActorId::generate());
            $events = $aggregate->popUncommittedEvents();
            $this->eventStore->append(
                $this->tenantA,
                $streamId,
                ExpectedVersion::exact(1 + ($i - 1) * 2), // Account for created + each rename
                $events
            );
        }

        // Reload and verify final state
        $storedEvents = $this->eventStore->load($this->tenantA, $streamId);
        
        // Should have: 1 created + 5 renames = 6 events
        $this->assertCount(6, $storedEvents);

        // Reconstitute
        $reconstituted = new HostileTestAggregate('replay-test', $this->tenantA);
        $domainEvents = $this->storedEventsToDomainEvents($storedEvents);
        $reconstituted->reconstituteFromEvents($domainEvents, \count($storedEvents));

        // Final state should be "Name 5"
        $this->assertEquals('Name 5', $reconstituted->getAggregateName());
        $this->assertEquals(6, $reconstituted->getVersion());
    }

    /**
     * Test: Empty payload fails validation.
     */
    public function testEmptyPayloadFails(): void
    {
        $streamId = 'empty-payload-test-stream';
        
        // Insert event with empty object payload
        $this->connection->exec(\sprintf(
            "INSERT INTO event_store (event_id, tenant_id, stream_id, stream_version, event_type, event_class_name, payload, metadata, occurred_at)
             VALUES ('%s', '%s', '%s', 0, 'TestAggregateCreated', 'Spiral\Kernel\Tests\Fixture\Event\TestAggregateCreated', '{}', '{\"eventId\": \"%s\", \"tenantId\": \"%s\", \"correlationId\": \"%s\", \"causationId\": \"%s\", \"occurredAt\": \"2024-01-01T00:00:00+00:00\", \"schemaVersion\": \"1.0\"}', NOW())",
            'hhhhhhhh-hhhh-hhhh-hhhh-hhhhhhhhhhhh',
            $this->tenantA->toString(),
            $streamId,
            'iiiiiiii-iiii-iiii-iiii-iiiiiiiiiiii',
            $this->tenantA->toString(),
            'jjjjjjjj-jjjj-jjjj-jjjj-jjjjjjjjjjjj',
            'kkkkkkkk-kkkk-kkkk-kkkk-kkkkkkkkkkkk'
        ));

        $storedEvents = $this->eventStore->load($this->tenantA, $streamId);
        
        // Hydration should fail due to missing required fields
        $this->expectException(\InvalidArgumentException::class);
        $this->hydrator->hydrate($storedEvents[0]);
    }

    /**
     * Test: Invalid UUID format fails.
     */
    public function testInvalidUuidFormatFails(): void
    {
        $streamId = 'invalid-uuid-test-stream';
        
        // Insert event with invalid UUID format
        $this->connection->exec(\sprintf(
            "INSERT INTO event_store (event_id, tenant_id, stream_id, stream_version, event_type, event_class_name, payload, metadata, occurred_at)
             VALUES ('not-a-uuid', '%s', '%s', 0, 'TestAggregateCreated', 'Spiral\Kernel\Tests\Fixture\Event\TestAggregateCreated', '{\"aggregateId\": \"test-1\", \"tenantId\": \"%s\", \"name\": \"Test\", \"createdBy\": \"%s\", \"correlationId\": \"%s\", \"causationId\": \"%s\"}', '{\"eventId\": \"not-a-uuid\", \"tenantId\": \"%s\", \"correlationId\": \"%s\", \"causationId\": \"%s\", \"occurredAt\": \"2024-01-01T00:00:00+00:00\", \"schemaVersion\": \"1.0\"}', NOW())",
            $this->tenantA->toString(),
            $streamId,
            $this->tenantA->toString(),
            ActorId::generate()->toString(),
            CorrelationId::generate()->toString(),
            CausationId::generate()->toString(),
            $this->tenantA->toString(),
            CorrelationId::generate()->toString(),
            CausationId::generate()->toString()
        ));

        $storedEvents = $this->eventStore->load($this->tenantA, $streamId);
        
        // Should fail when trying to parse invalid UUID
        $this->expectException(\Exception::class);
        $this->hydrator->hydrate($storedEvents[0]);
    }

    /**
     * Convert stored events to domain events for testing.
     *
     * @param list<StoredEvent> $storedEvents
     * @return list<DomainEvent>
     */
    private function storedEventsToDomainEvents(array $storedEvents): array
    {
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
