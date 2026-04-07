<?php

declare(strict_types=1);

namespace Spiral\Kernel\Tests\Fixture\Aggregate;

use Spiral\Kernel\Domain\Shared\Event\DomainEventContract;
use Spiral\Kernel\Domain\Identity\EventId;
use Spiral\Kernel\Domain\Identity\TenantId;
use Spiral\Kernel\Domain\Identity\CorrelationId;
use Spiral\Kernel\Domain\Identity\CausationId;
use DateTimeImmutable;

/**
 * Test event for aggregate creation.
 */
final class TestAggregateCreated extends DomainEventContract
{
    public function __construct(
        private readonly EventId $eventId,
        private readonly TenantId $tenantId,
        private readonly CorrelationId $correlationId,
        private readonly CausationId $causationId,
        private readonly DateTimeImmutable $occurredAt,
        public readonly string $aggregateId,
        public readonly string $name,
        public readonly DateTimeImmutable $createdAt,
        public readonly string $createdBy
    ) {}

    public function getEventId(): EventId { return $this->eventId; }
    public function getTenantId(): TenantId { return $this->tenantId; }
    public function getCorrelationId(): CorrelationId { return $this->correlationId; }
    public function getCausationId(): CausationId { return $this->causationId; }
    public function getOccurredAt(): DateTimeImmutable { return $this->occurredAt; }
    public function getSchemaVersion(): string { return '1.0'; }
    public function toArray(): array {
        return ['aggregateId' => $this->aggregateId, 'name' => $this->name];
    }

    public static function forTest(string $aggregateId, string $name, DateTimeImmutable $createdAt, string $createdBy): self {
        return new self(
            EventId::fromString('10000000-0000-0000-0000-000000000001'),
            TenantId::fromString('20000000-0000-0000-0000-000000000001'),
            CorrelationId::fromString('30000000-0000-0000-0000-000000000001'),
            CausationId::fromString('40000000-0000-0000-0000-000000000001'),
            new DateTimeImmutable('2026-01-01T00:00:00Z'),
            $aggregateId,
            $name,
            $createdAt,
            $createdBy
        );
    }
}

/**
 * Test event for name changes.
 */
final class TestAggregateNameChanged extends DomainEventContract
{
    public function __construct(
        private readonly EventId $eventId,
        private readonly TenantId $tenantId,
        private readonly CorrelationId $correlationId,
        private readonly CausationId $causationId,
        private readonly DateTimeImmutable $occurredAt,
        public readonly string $aggregateId,
        public readonly string $newName,
        public readonly DateTimeImmutable $changedAt,
        public readonly string $changedBy
    ) {}

    public function getEventId(): EventId { return $this->eventId; }
    public function getTenantId(): TenantId { return $this->tenantId; }
    public function getCorrelationId(): CorrelationId { return $this->correlationId; }
    public function getCausationId(): CausationId { return $this->causationId; }
    public function getOccurredAt(): DateTimeImmutable { return $this->occurredAt; }
    public function getSchemaVersion(): string { return '1.0'; }
    public function toArray(): array {
        return ['aggregateId' => $this->aggregateId, 'newName' => $this->newName];
    }

    public static function forTest(string $aggregateId, string $newName, DateTimeImmutable $changedAt, string $changedBy): self {
        return new self(
            EventId::fromString('10000000-0000-0000-0000-000000000002'),
            TenantId::fromString('20000000-0000-0000-0000-000000000001'),
            CorrelationId::fromString('30000000-0000-0000-0000-000000000001'),
            CausationId::fromString('40000000-0000-0000-0000-000000000001'),
            new DateTimeImmutable('2026-01-01T00:00:00Z'),
            $aggregateId,
            $newName,
            $changedAt,
            $changedBy
        );
    }
}

/**
 * Test event for activation.
 */
final class TestAggregateActivated extends DomainEventContract
{
    public function __construct(
        private readonly EventId $eventId,
        private readonly TenantId $tenantId,
        private readonly CorrelationId $correlationId,
        private readonly CausationId $causationId,
        private readonly DateTimeImmutable $occurredAt,
        public readonly string $aggregateId,
        public readonly DateTimeImmutable $activatedAt,
        public readonly string $activatedBy
    ) {}

    public function getEventId(): EventId { return $this->eventId; }
    public function getTenantId(): TenantId { return $this->tenantId; }
    public function getCorrelationId(): CorrelationId { return $this->correlationId; }
    public function getCausationId(): CausationId { return $this->causationId; }
    public function getOccurredAt(): DateTimeImmutable { return $this->occurredAt; }
    public function getSchemaVersion(): string { return '1.0'; }
    public function toArray(): array {
        return ['aggregateId' => $this->aggregateId];
    }

    public static function forTest(string $aggregateId, DateTimeImmutable $activatedAt, string $activatedBy): self {
        return new self(
            EventId::fromString('10000000-0000-0000-0000-000000000003'),
            TenantId::fromString('20000000-0000-0000-0000-000000000001'),
            CorrelationId::fromString('30000000-0000-0000-0000-000000000001'),
            CausationId::fromString('40000000-0000-0000-0000-000000000001'),
            new DateTimeImmutable('2026-01-01T00:00:00Z'),
            $aggregateId,
            $activatedAt,
            $activatedBy
        );
    }
}

/**
 * Test event for deactivation.
 */
final class TestAggregateDeactivated extends DomainEventContract
{
    public function __construct(
        private readonly EventId $eventId,
        private readonly TenantId $tenantId,
        private readonly CorrelationId $correlationId,
        private readonly CausationId $causationId,
        private readonly DateTimeImmutable $occurredAt,
        public readonly string $aggregateId,
        public readonly string $reason,
        public readonly DateTimeImmutable $deactivatedAt,
        public readonly string $deactivatedBy
    ) {}

    public function getEventId(): EventId { return $this->eventId; }
    public function getTenantId(): TenantId { return $this->tenantId; }
    public function getCorrelationId(): CorrelationId { return $this->correlationId; }
    public function getCausationId(): CausationId { return $this->causationId; }
    public function getOccurredAt(): DateTimeImmutable { return $this->occurredAt; }
    public function getSchemaVersion(): string { return '1.0'; }
    public function toArray(): array {
        return ['aggregateId' => $this->aggregateId, 'reason' => $this->reason];
    }

    public static function forTest(string $aggregateId, string $reason, DateTimeImmutable $deactivatedAt, string $deactivatedBy): self {
        return new self(
            EventId::fromString('10000000-0000-0000-0000-000000000004'),
            TenantId::fromString('20000000-0000-0000-0000-000000000001'),
            CorrelationId::fromString('30000000-0000-0000-0000-000000000001'),
            CausationId::fromString('40000000-0000-0000-0000-000000000001'),
            new DateTimeImmutable('2026-01-01T00:00:00Z'),
            $aggregateId,
            $reason,
            $deactivatedAt,
            $deactivatedBy
        );
    }
}
