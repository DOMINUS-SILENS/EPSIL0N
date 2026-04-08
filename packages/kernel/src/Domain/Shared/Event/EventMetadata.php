<?php

declare(strict_types=1);

namespace Spiral\Kernel\Domain\Shared\Event;

use Spiral\Kernel\Domain\Identity\CorrelationId;
use Spiral\Kernel\Domain\Identity\CausationId;
use Spiral\Kernel\Domain\Identity\EventId;
use Spiral\Kernel\Domain\Identity\TenantId;

/**
 * Metadata envelope for domain events.
 *
 * Contains all non-domain data needed for:
 * - Tracing (correlation, causation)
 * - Isolation (tenant)
 * - Ordering (timestamp)
 * - Evolution (schemaVersion)
 */
final class EventMetadata
{
    public function __construct(
        public readonly EventId $eventId,
        public readonly TenantId $tenantId,
        public readonly CorrelationId $correlationId,
        public readonly CausationId $causationId,
        public readonly \DateTimeImmutable $occurredAt,
        public readonly string $schemaVersion,
    ) {}

    public static function create(
        EventId $eventId,
        TenantId $tenantId,
        CorrelationId $correlationId,
        CausationId $causationId,
        \DateTimeImmutable $occurredAt,
        string $schemaVersion = '1.0',
    ): self {
        return new self(
            eventId: $eventId,
            tenantId: $tenantId,
            correlationId: $correlationId,
            causationId: $causationId,
            occurredAt: $occurredAt,
            schemaVersion: $schemaVersion,
        );
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'eventId' => $this->eventId->toString(),
            'tenantId' => $this->tenantId->toString(),
            'correlationId' => $this->correlationId->toString(),
            'causationId' => $this->causationId->toString(),
            'occurredAt' => $this->occurredAt->format(\DateTimeInterface::ATOM),
            'schemaVersion' => $this->schemaVersion,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        /** @var non-empty-string $eventIdStr */
        $eventIdStr = $data['eventId'];
        /** @var non-empty-string $tenantIdStr */
        $tenantIdStr = $data['tenantId'];
        /** @var non-empty-string $correlationIdStr */
        $correlationIdStr = $data['correlationId'];
        /** @var non-empty-string $causationIdStr */
        $causationIdStr = $data['causationId'];
        /** @var string $occurredAtStr */
        $occurredAtStr = $data['occurredAt'];
        /** @var string $schemaVersionStr */
        $schemaVersionStr = $data['schemaVersion'];

        return new self(
            eventId: EventId::fromString($eventIdStr),
            tenantId: TenantId::fromString($tenantIdStr),
            correlationId: CorrelationId::fromString($correlationIdStr),
            causationId: CausationId::fromString($causationIdStr),
            occurredAt: new \DateTimeImmutable($occurredAtStr),
            schemaVersion: $schemaVersionStr,
        );
    }
}
