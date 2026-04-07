<?php

declare(strict_types=1);

namespace Spiral\Kernel\Tests\Fixture\Event;

use Spiral\Kernel\Domain\Identity\ActorId;
use Spiral\Kernel\Domain\Identity\CausationId;
use Spiral\Kernel\Domain\Identity\CorrelationId;
use Spiral\Kernel\Domain\Identity\EventId;
use Spiral\Kernel\Domain\Identity\TenantId;
use Spiral\Kernel\Domain\Shared\Event\DomainEvent;

/**
 * Test domain event implementing DomainEvent interface.
 */
final class TestAggregateCreated implements DomainEvent
{
    public function __construct(
        public readonly string $aggregateId,
        public readonly TenantId $tenantId,
        public readonly string $name,
        public readonly ActorId $createdBy,
        public readonly CorrelationId $correlationId,
        public readonly CausationId $causationId,
    ) {}

    public function getEventId(): EventId
    {
        return EventId::generate();
    }

    public function getTenantId(): TenantId
    {
        return $this->tenantId;
    }

    public function getCorrelationId(): CorrelationId
    {
        return $this->correlationId;
    }

    public function getCausationId(): CausationId
    {
        return $this->causationId;
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }

    public function getSchemaVersion(): string
    {
        return '1.0';
    }

    public function getEventType(): string
    {
        return 'TestAggregateCreated';
    }

    public function getClassName(): string
    {
        return self::class;
    }

    public function toArray(): array
    {
        return [
            'aggregateId' => $this->aggregateId,
            'tenantId' => $this->tenantId->toString(),
            'name' => $this->name,
            'createdBy' => $this->createdBy->toString(),
            'correlationId' => $this->correlationId->toString(),
            'causationId' => $this->causationId->toString(),
        ];
    }
}

/**
 * Test domain event implementing DomainEvent interface.
 */
final class TestAggregateRenamed implements DomainEvent
{
    public function __construct(
        public readonly string $aggregateId,
        public readonly TenantId $tenantId,
        public readonly string $newName,
        public readonly ActorId $renamedBy,
        public readonly CorrelationId $correlationId,
        public readonly CausationId $causationId,
    ) {}

    public function getEventId(): EventId
    {
        return EventId::generate();
    }

    public function getTenantId(): TenantId
    {
        return $this->tenantId;
    }

    public function getCorrelationId(): CorrelationId
    {
        return $this->correlationId;
    }

    public function getCausationId(): CausationId
    {
        return $this->causationId;
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }

    public function getSchemaVersion(): string
    {
        return '1.0';
    }

    public function getEventType(): string
    {
        return 'TestAggregateRenamed';
    }

    public function getClassName(): string
    {
        return self::class;
    }

    public function toArray(): array
    {
        return [
            'aggregateId' => $this->aggregateId,
            'tenantId' => $this->tenantId->toString(),
            'newName' => $this->newName,
            'renamedBy' => $this->renamedBy->toString(),
            'correlationId' => $this->correlationId->toString(),
            'causationId' => $this->causationId->toString(),
        ];
    }
}
