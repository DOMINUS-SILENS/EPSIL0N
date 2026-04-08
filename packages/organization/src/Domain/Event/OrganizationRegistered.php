<?php

declare(strict_types=1);

namespace Spiral\Organization\Domain\Event;

use DateTimeImmutable;
use Spiral\Kernel\Domain\Identity\CausationId;
use Spiral\Kernel\Domain\Identity\CorrelationId;
use Spiral\Kernel\Domain\Identity\EventId;
use Spiral\Kernel\Domain\Identity\TenantId;
use Spiral\Kernel\Domain\Shared\Event\DomainEvent;
use Spiral\Kernel\Domain\Sync\SyncMetadata;
use Spiral\Kernel\Domain\Tenancy\EmailAddress;
use Spiral\Kernel\Domain\Tenancy\TenantSlug;
use Spiral\Organization\Domain\ValueObject\OrganizationId;

/**
 * Event raised when a new organization is registered.
 *
 * This is the creation event for the Organization aggregate.
 * All organization properties are established at registration time.
 */
final class OrganizationRegistered implements DomainEvent
{
    public const SCHEMA_VERSION = '1.0';

    public function __construct(
        private readonly EventId $eventId,
        private readonly TenantId $tenantId,
        private readonly CorrelationId $correlationId,
        private readonly CausationId $causationId,
        private readonly DateTimeImmutable $occurredAt,
        private readonly OrganizationId $organizationId,
        private readonly string $name,
        private readonly TenantSlug $slug,
        private readonly EmailAddress $contactEmail,
        private readonly \Spiral\Kernel\Domain\Shared\ValueObject\Temporal\TimezoneId $timezone
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
        return 'Organization.Registered';
    }

    public function getOrganizationId(): OrganizationId
    {
        return $this->organizationId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): TenantSlug
    {
        return $this->slug;
    }

    public function getContactEmail(): EmailAddress
    {
        return $this->contactEmail;
    }

    public function getTimezone(): \Spiral\Kernel\Domain\Shared\ValueObject\Temporal\TimezoneId
    {
        return $this->timezone;
    }

    public function getClassName(): string
    {
        return self::class;
    }

    public function __toString(): string
    {
        return $this->getEventType();
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
            'name' => $this->name,
            'slug' => $this->slug->toString(),
            'contactEmail' => $this->contactEmail->toString(),
            'timezone' => (string) $this->timezone,
        ];
    }

    public function getSyncMetadata(): ?SyncMetadata
    {
        return null;
    }

    /**
     * Factory method for creating the event from command context.
     */
    public static function create(
        TenantId $tenantId,
        CorrelationId $correlationId,
        CausationId $causationId,
        OrganizationId $organizationId,
        string $name,
        TenantSlug $slug,
        EmailAddress $contactEmail,
        \Spiral\Kernel\Domain\Shared\ValueObject\Temporal\TimezoneId $timezone
    ): self {
        return new self(
            eventId: EventId::generate(),
            tenantId: $tenantId,
            correlationId: $correlationId,
            causationId: $causationId,
            occurredAt: new DateTimeImmutable(),
            organizationId: $organizationId,
            name: $name,
            slug: $slug,
            contactEmail: $contactEmail,
            timezone: $timezone
        );
    }

    /**
     * Reconstruct event from storage payload and metadata.
     *
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
            name: (string) $payload['name'],
            slug: TenantSlug::fromString((string) $payload['slug']),
            contactEmail: EmailAddress::fromString((string) $payload['contactEmail']),
            timezone: \Spiral\Kernel\Domain\Shared\ValueObject\Temporal\TimezoneId::fromString((string) $payload['timezone'])
        );
    }
}
