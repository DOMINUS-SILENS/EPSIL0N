<?php

declare(strict_types=1);

namespace Spiral\Organization\Domain\Event;

use DateTimeImmutable;
use Spiral\Kernel\Domain\Identity\CausationId;
use Spiral\Kernel\Domain\Identity\CorrelationId;
use Spiral\Kernel\Domain\Identity\EventId;
use Spiral\Kernel\Domain\Identity\TenantId;
use Spiral\Kernel\Domain\Shared\Event\DomainEvent;
use Spiral\Organization\Domain\ValueObject\OrganizationId;

/**
 * Event raised when an organization is deactivated.
 */
final class OrganizationDeactivated implements DomainEvent
{
    public const SCHEMA_VERSION = '1.0';

    public function __construct(
        private readonly EventId $eventId,
        private readonly TenantId $tenantId,
        private readonly CorrelationId $correlationId,
        private readonly CausationId $causationId,
        private readonly DateTimeImmutable $occurredAt,
        private readonly OrganizationId $organizationId,
        private readonly ?string $reason = null
    ) {
    }

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
        return self::SCHEMA_VERSION;
    }

    public function getEventType(): string
    {
        return 'Organization.Deactivated';
    }

    public function getOrganizationId(): OrganizationId
    {
        return $this->organizationId;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function getClassName(): string
    {
        return self::class;
    }

    public function toArray(): array
    {
        return [
            'eventId' => $this->eventId->toString(),
            'tenantId' => $this->tenantId->toString(),
            'correlationId' => $this->correlationId->toString(),
            'causationId' => $this->causationId->toString(),
            'occurredAt' => $this->occurredAt->format('c'),
            'schemaVersion' => self::SCHEMA_VERSION,
            'eventType' => $this->getEventType(),
            'organizationId' => $this->organizationId->toString(),
            'reason' => $this->reason,
        ];
    }

    /**
     * Factory method for creating the event.
     */
    public static function create(
        TenantId $tenantId,
        CorrelationId $correlationId,
        CausationId $causationId,
        OrganizationId $organizationId,
        ?string $reason = null
    ): self {
        return new self(
            eventId: EventId::generate(),
            tenantId: $tenantId,
            correlationId: $correlationId,
            causationId: $causationId,
            occurredAt: new DateTimeImmutable(),
            organizationId: $organizationId,
            reason: $reason
        );
    }

    /**
     * Reconstruct event from storage.
     * @param array<string, mixed> $payload
     */
    public static function fromStorage(array $payload, \Spiral\Kernel\Domain\Shared\Event\EventMetadata $metadata): self
    {
        return new self(
            eventId: $metadata->eventId,
            tenantId: $metadata->tenantId,
            correlationId: $metadata->correlationId,
            causationId: $metadata->causationId,
            occurredAt: $metadata->occurredAt,
            organizationId: OrganizationId::fromString((string) $payload['organizationId']),
            reason: isset($payload['reason']) ? (string) $payload['reason'] : null
        );
    }
}
