<?php

declare(strict_types=1);

namespace Spiral\Kernel\Tests\Fixture\Event;

use Spiral\Kernel\Domain\Identity\EventId;
use Spiral\Kernel\Domain\Identity\TenantId;
use DateTimeInterface;

/**
 * Test fixture event for unit testing.
 * This is a placeholder that will be replaced when DomainEvent is implemented (Phase 3+).
 *
 * @package Spiral\Kernel\Tests\Fixture\Event
 */
final class TestEvent
{
    private EventId $eventId;
    private TenantId $tenantId;
    private string $aggregateId;
    private int $version;
    private string $eventType;
    /** @var array<string, mixed> */
    private array $payload;
    private DateTimeInterface $occurredAt;
    private int $schemaVersion = 1;

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        EventId $eventId,
        TenantId $tenantId,
        string $aggregateId,
        int $version,
        string $eventType,
        array $payload,
        DateTimeInterface $occurredAt
    ) {
        $this->eventId = $eventId;
        $this->tenantId = $tenantId;
        $this->aggregateId = $aggregateId;
        $this->version = $version;
        $this->eventType = $eventType;
        $this->payload = $payload;
        $this->occurredAt = $occurredAt;
    }

    public function eventId(): EventId
    {
        return $this->eventId;
    }

    public function tenantId(): TenantId
    {
        return $this->tenantId;
    }

    public function aggregateId(): string
    {
        return $this->aggregateId;
    }

    public function version(): int
    {
        return $this->version;
    }

    public function eventType(): string
    {
        return $this->eventType;
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->payload;
    }

    public function occurredAt(): DateTimeInterface
    {
        return $this->occurredAt;
    }

    public function schemaVersion(): int
    {
        return $this->schemaVersion;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'eventId' => $this->eventId->toString(),
            'tenantId' => $this->tenantId->toString(),
            'aggregateId' => $this->aggregateId,
            'version' => $this->version,
            'eventType' => $this->eventType,
            'payload' => $this->payload,
            'occurredAt' => $this->occurredAt->format('c'),
            'schemaVersion' => $this->schemaVersion,
        ];
    }
}
