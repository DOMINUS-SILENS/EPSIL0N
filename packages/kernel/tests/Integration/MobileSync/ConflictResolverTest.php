<?php

declare(strict_types=1);

namespace Spiral\Kernel\Tests\Integration\MobileSync;

use PHPUnit\Framework\TestCase;
use Spiral\Kernel\Domain\Identity\DeviceId;
use Spiral\Kernel\Domain\Identity\TenantId;
use Spiral\Kernel\Domain\Sync\ConflictStrategy;
use Spiral\Kernel\Domain\Sync\SyncVersion;
use Spiral\Kernel\Domain\Sync\SyncStatus;
use Spiral\Kernel\Domain\Shared\Event\DomainEvent;
use Spiral\Kernel\Infrastructure\Contract\MobileSync\QueuedEvent;
use Spiral\Kernel\Infrastructure\Persistence\MobileSync\ConflictResolver;

/**
 * Integration tests for ConflictResolver.
 *
 * Verifies all 6 conflict resolution strategies.
 */
final class ConflictResolverTest extends TestCase
{
    private TenantId $tenantId;
    private DeviceId $deviceA;
    private DeviceId $deviceB;
    private DeviceId $deviceManager;

    protected function setUp(): void
    {
        $this->tenantId = TenantId::generate();
        $this->deviceA = DeviceId::generate();
        $this->deviceB = DeviceId::generate();
        $this->deviceManager = DeviceId::generate();
    }

    /**
     * TEST-01: LastWriterWins - newer event wins.
     */
    public function test_last_writer_wins_newer_event_wins(): void
    {
        $resolver = new ConflictResolver(ConflictStrategy::LastWriterWins);

        $clientEvent = $this->createQueuedEvent(
            $this->deviceA,
            new \DateTimeImmutable('2026-04-07 12:00:00')
        );

        $serverEvents = [
            $this->createStoredEvent(
                $this->deviceB,
                new \DateTimeImmutable('2026-04-07 11:00:00') // Earlier
            ),
        ];

        $result = $resolver->resolve(
            $clientEvent,
            SyncVersion::empty(),
            $serverEvents
        );

        $this->assertTrue($result->shouldAccept);
        $this->assertStringContainsString('newer', $result->resolutionNote);
    }

    /**
     * TEST-02: LastWriterWins - older event loses.
     */
    public function test_last_writer_wins_older_event_loses(): void
    {
        $resolver = new ConflictResolver(ConflictStrategy::LastWriterWins);

        $clientEvent = $this->createQueuedEvent(
            $this->deviceA,
            new \DateTimeImmutable('2026-04-07 10:00:00') // Earlier
        );

        $serverEvents = [
            $this->createStoredEvent(
                $this->deviceB,
                new \DateTimeImmutable('2026-04-07 11:00:00') // Later
            ),
        ];

        $result = $resolver->resolve(
            $clientEvent,
            SyncVersion::empty(),
            $serverEvents
        );

        $this->assertFalse($result->shouldAccept);
        $this->assertNotNull($result->conflictData);
    }

    /**
     * TEST-03: FirstWriterWins - older event wins.
     */
    public function test_first_writer_wins_older_event_wins(): void
    {
        $resolver = new ConflictResolver(ConflictStrategy::FirstWriterWins);

        $clientEvent = $this->createQueuedEvent(
            $this->deviceA,
            new \DateTimeImmutable('2026-04-07 10:00:00') // Earlier
        );

        $serverEvents = [
            $this->createStoredEvent(
                $this->deviceB,
                new \DateTimeImmutable('2026-04-07 11:00:00') // Later
            ),
        ];

        $result = $resolver->resolve(
            $clientEvent,
            SyncVersion::empty(),
            $serverEvents
        );

        $this->assertTrue($result->shouldAccept);
        $this->assertStringContainsString('older', $result->resolutionNote);
    }

    /**
     * TEST-04: DevicePriority - higher priority device wins.
     */
    public function test_device_priority_higher_priority_wins(): void
    {
        $resolver = new ConflictResolver(
            ConflictStrategy::DevicePriority,
            [
                $this->deviceManager->toString() => 100, // Manager has high priority
                $this->deviceA->toString() => 10,        // Regular device
            ]
        );

        // Client is manager device
        $clientEvent = $this->createQueuedEvent(
            $this->deviceManager,
            new \DateTimeImmutable('2026-04-07 12:00:00')
        );

        $serverEvents = [
            $this->createStoredEvent(
                $this->deviceA,
                new \DateTimeImmutable('2026-04-07 12:00:00')
            ),
        ];

        $result = $resolver->resolve(
            $clientEvent,
            SyncVersion::empty(),
            $serverEvents
        );

        $this->assertTrue($result->shouldAccept);
        $this->assertStringContainsString('higher priority', $result->resolutionNote);
    }

    /**
     * TEST-05: DevicePriority - lower priority device loses.
     */
    public function test_device_priority_lower_priority_loses(): void
    {
        $resolver = new ConflictResolver(
            ConflictStrategy::DevicePriority,
            [
                $this->deviceManager->toString() => 100,
                $this->deviceA->toString() => 10,
            ]
        );

        // Client is regular device with lower priority
        $clientEvent = $this->createQueuedEvent(
            $this->deviceA,
            new \DateTimeImmutable('2026-04-07 12:00:00')
        );

        $serverEvents = [
            $this->createStoredEvent(
                $this->deviceManager,
                new \DateTimeImmutable('2026-04-07 12:00:00')
            ),
        ];

        $result = $resolver->resolve(
            $clientEvent,
            SyncVersion::empty(),
            $serverEvents
        );

        $this->assertFalse($result->shouldAccept);
    }

    /**
     * TEST-06: ServerWins - server always wins.
     */
    public function test_server_wins_always_rejects_client(): void
    {
        $resolver = new ConflictResolver(ConflictStrategy::ServerWins);

        $clientEvent = $this->createQueuedEvent(
            $this->deviceA,
            new \DateTimeImmutable('2026-04-07 12:00:00')
        );

        $serverEvents = [
            $this->createStoredEvent(
                $this->deviceB,
                new \DateTimeImmutable('2026-04-07 11:00:00') // Even earlier
            ),
        ];

        $result = $resolver->resolve(
            $clientEvent,
            SyncVersion::empty(),
            $serverEvents
        );

        $this->assertFalse($result->shouldAccept);
        $this->assertStringContainsString('Server wins', $result->resolutionNote);
    }

    /**
     * TEST-07: ClientWins - client always wins.
     */
    public function test_client_wins_always_accepts_client(): void
    {
        $resolver = new ConflictResolver(ConflictStrategy::ClientWins);

        $clientEvent = $this->createQueuedEvent(
            $this->deviceA,
            new \DateTimeImmutable('2026-04-07 10:00:00') // Even earlier
        );

        $serverEvents = [
            $this->createStoredEvent(
                $this->deviceB,
                new \DateTimeImmutable('2026-04-07 12:00:00') // Later
            ),
        ];

        $serverVersion = SyncVersion::empty()->increment($this->deviceB);

        $result = $resolver->resolve(
            $clientEvent,
            $serverVersion,
            $serverEvents
        );

        $this->assertTrue($result->shouldAccept);
        $this->assertStringContainsString('Client wins', $result->resolutionNote);
    }

    /**
     * TEST-08: Equal priority falls back to timestamp.
     */
    public function test_equal_priority_falls_back_to_timestamp(): void
    {
        $resolver = new ConflictResolver(
            ConflictStrategy::DevicePriority,
            [
                $this->deviceA->toString() => 10,
                $this->deviceB->toString() => 10, // Same priority
            ]
        );

        $clientEvent = $this->createQueuedEvent(
            $this->deviceA,
            new \DateTimeImmutable('2026-04-07 12:00:00') // Later
        );

        $serverEvents = [
            $this->createStoredEvent(
                $this->deviceB,
                new \DateTimeImmutable('2026-04-07 11:00:00') // Earlier
            ),
        ];

        $result = $resolver->resolve(
            $clientEvent,
            SyncVersion::empty(),
            $serverEvents
        );

        // Falls back to LastWriterWins, client wins
        $this->assertTrue($result->shouldAccept);
    }

    /**
     * Helper: Create a queued event for testing.
     */
    private function createQueuedEvent(
        DeviceId $deviceId,
        \DateTimeImmutable $occurredAt
    ): QueuedEvent {
        $event = $this->createMock(DomainEvent::class);
        $event->method('getOccurredAt')->willReturn($occurredAt);
        $event->method('getEventId')->willReturn(
            \Spiral\Kernel\Domain\Identity\EventId::generate()
        );
        $event->method('getEventType')->willReturn('TestEvent');
        $event->method('toArray')->willReturn(['test' => 'data']);
        $event->method('getSyncMetadata')->willReturn(null);

        return new QueuedEvent(
            queueItemId: \Ramsey\Uuid\Uuid::uuid4()->toString(),
            tenantId: $this->tenantId,
            deviceId: $deviceId,
            event: $event,
            syncVersion: SyncVersion::empty()->increment($deviceId),
            status: SyncStatus::Pending,
            queuedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Helper: Create a stored event for testing.
     */
    private function createStoredEvent(
        DeviceId $deviceId,
        \DateTimeImmutable $occurredAt
    ): \Spiral\Kernel\Domain\Shared\Event\StoredEvent {
        $eventId = \Spiral\Kernel\Domain\Identity\EventId::generate();
        $tenantId = $this->tenantId;

        return new \Spiral\Kernel\Domain\Shared\Event\StoredEvent(
            eventId: $eventId,
            tenantId: $tenantId,
            streamId: 'test-stream',
            streamVersion: 1,
            eventType: 'TestEvent',
            eventClassName: 'TestEvent',
            payload: ['test' => 'server-data', 'occurredAt' => $occurredAt->format(\DateTimeInterface::ATOM), 'deviceId' => $deviceId->toString()],
            metadata: new \Spiral\Kernel\Domain\Shared\Event\EventMetadata(
                eventId: $eventId,
                tenantId: $tenantId,
                correlationId: \Spiral\Kernel\Domain\Identity\CorrelationId::generate(),
                causationId: \Spiral\Kernel\Domain\Identity\CausationId::generate(),
                occurredAt: $occurredAt,
                schemaVersion: '1.0',
            ),
        );
    }
}
