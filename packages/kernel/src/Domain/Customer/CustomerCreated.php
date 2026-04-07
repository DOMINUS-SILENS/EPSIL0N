<?php

declare(strict_types=1);

namespace Spiral\Kernel\Domain\Customer;

use Spiral\Kernel\Domain\Shared\Event\DomainEventContract;
use Spiral\Kernel\Domain\Identity\EventId;
use Spiral\Kernel\Domain\Identity\TenantId;
use Spiral\Kernel\Domain\Identity\CorrelationId;
use Spiral\Kernel\Domain\Identity\CausationId;
use DateTimeImmutable;

final class CustomerCreated extends DomainEventContract
{
    public function __construct(
        private readonly EventId $eventId,
        private readonly TenantId $tenantId,
        private readonly CorrelationId $correlationId,
        private readonly CausationId $causationId,
        private readonly DateTimeImmutable $occurredAt,
        public readonly string $aggregate_id,
        public readonly string $name,
    ) {}

    public function getEventId(): EventId
    {
        return $this->eventId;
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

    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function getSchemaVersion(): string
    {
        return '1.0';
    }

    public function toArray(): array
    {
        return [
            'aggregate_id' => $this->aggregate_id,
            'name' => $this->name,
        ];
    }

    /**
     * Factory for deterministic test events.
     */
    public static function forTest(string $aggregateId, string $name): self
    {
        return new self(
            EventId::fromString('00000000-0000-0000-0000-000000000001'),
            TenantId::fromString('00000000-0000-0000-0000-000000000001'),
            CorrelationId::fromString('00000000-0000-0000-0000-000000000001'),
            CausationId::fromString('00000000-0000-0000-0000-000000000001'),
            new DateTimeImmutable('2026-01-01T00:00:00Z'),
            $aggregateId,
            $name,
        );
    }
}
