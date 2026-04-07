<?php

declare(strict_types=1);

namespace Spiral\Kernel\Domain\Shared\Event;

use Spiral\Kernel\Domain\Identity\EventId;
use Spiral\Kernel\Domain\Identity\TenantId;

/**
 * Represents a stored/persisted domain event.
 *
 * This is the canonical form stored in the event store database.
 * Contains both the event data (payload) and metadata.
 */
final class StoredEvent
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public readonly EventId $eventId,
        public readonly TenantId $tenantId,
        public readonly string $streamId,
        public readonly int $streamVersion,
        public readonly string $eventType,
        public readonly string $eventClassName,
        public readonly array $payload,
        public readonly EventMetadata $metadata,
    ) {}

    /**
     * Create a StoredEvent from a DomainEvent and stream context.
     */
    public static function fromDomainEvent(
        DomainEvent $event,
        string $streamId,
        int $streamVersion,
    ): self {
        return new self(
            eventId: $event->getEventId(),
            tenantId: $event->getTenantId(),
            streamId: $streamId,
            streamVersion: $streamVersion,
            eventType: $event->getEventType(),
            eventClassName: $event->getClassName(),
            payload: $event->toArray(),
            metadata: new EventMetadata(
                eventId: $event->getEventId(),
                tenantId: $event->getTenantId(),
                correlationId: $event->getCorrelationId(),
                causationId: $event->getCausationId(),
                occurredAt: $event->getOccurredAt(),
                schemaVersion: $event->getSchemaVersion(),
            ),
        );
    }

    /**
     * Convert to database row format.
     *
     * @return array<string, mixed>
     */
    public function toDatabaseRow(): array
    {
        return [
            'event_id' => $this->eventId->toString(),
            'tenant_id' => $this->tenantId->toString(),
            'stream_id' => $this->streamId,
            'stream_version' => $this->streamVersion,
            'event_type' => $this->eventType,
            'event_class_name' => $this->eventClassName,
            'payload' => \json_encode($this->payload, JSON_THROW_ON_ERROR),
            'metadata' => \json_encode($this->metadata->toArray(), JSON_THROW_ON_ERROR),
            'occurred_at' => $this->metadata->occurredAt->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * Create from database row.
     *
     * @param array<string, mixed> $row
     */
    public static function fromDatabaseRow(array $row): self
    {
        /** @var string $payloadJson */
        $payloadJson = $row['payload'];
        /** @var string $metadataJson */
        $metadataJson = $row['metadata'];

        $payload = \json_decode($payloadJson, true, 512, JSON_THROW_ON_ERROR);
        /** @var array<string, mixed> $metadataArray */
        $metadataArray = \json_decode($metadataJson, true, 512, JSON_THROW_ON_ERROR);

        /** @var non-empty-string $eventIdStr */
        $eventIdStr = $row['event_id'];
        /** @var non-empty-string $tenantIdStr */
        $tenantIdStr = $row['tenant_id'];
        /** @var string $streamIdStr */
        $streamIdStr = $row['stream_id'];
        /** @var int $streamVersionInt */
        $streamVersionInt = $row['stream_version'];
        /** @var string $eventTypeStr */
        $eventTypeStr = $row['event_type'];
        /** @var string $eventClassNameStr */
        $eventClassNameStr = $row['event_class_name'];

        $eventId = EventId::fromString($eventIdStr);
        $tenantId = TenantId::fromString($tenantIdStr);
        $streamId = $streamIdStr;
        $streamVersion = $streamVersionInt;
        $eventType = $eventTypeStr;
        $eventClassName = $eventClassNameStr;

        /** @var array<string, mixed> $payloadArray */
        $payloadArray = $payload;

        return new self(
            eventId: $eventId,
            tenantId: $tenantId,
            streamId: $streamId,
            streamVersion: $streamVersion,
            eventType: $eventType,
            eventClassName: $eventClassName,
            payload: $payloadArray,
            metadata: EventMetadata::fromArray($metadataArray),
        );
    }
}
