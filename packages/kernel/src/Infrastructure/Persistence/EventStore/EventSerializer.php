<?php

declare(strict_types=1);

namespace Spiral\Kernel\Infrastructure\Persistence\EventStore;

use Spiral\Kernel\Domain\Identity\EventId;
use Spiral\Kernel\Domain\Identity\TenantId;
use Spiral\Kernel\Domain\Shared\Event\DomainEvent;
use Spiral\Kernel\Domain\Shared\Event\EventMetadata;
use Spiral\Kernel\Domain\Shared\Event\StoredEvent;

/**
 * Serializes DomainEvent to StoredEvent for persistence.
 */
final class EventSerializer
{
    /**
     * Serialize a DomainEvent to a StoredEvent.
     *
     * @throws \InvalidArgumentException If event is invalid
     */
    public function serialize(DomainEvent $event, string $streamId, int $streamVersion): StoredEvent
    {
        $this->validateEvent($event);

        return new StoredEvent(
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
     * Serialize multiple events.
     *
     * @param list<DomainEvent> $events
     * @return list<StoredEvent>
     */
    public function serializeAll(array $events, string $streamId, int $startVersion): array
    {
        $storedEvents = [];
        $version = $startVersion;

        foreach ($events as $event) {
            $storedEvents[] = $this->serialize($event, $streamId, $version);
            $version++;
        }

        return $storedEvents;
    }

    /**
     * Convert StoredEvent to database row format.
     *
     * @return array<string, mixed>
     */
    public function toDatabaseRow(StoredEvent $event): array
    {
        return [
            'event_id' => $event->eventId->toString(),
            'tenant_id' => $event->tenantId->toString(),
            'stream_id' => $event->streamId,
            'stream_version' => $event->streamVersion,
            'event_type' => $event->eventType,
            'event_class_name' => $event->eventClassName,
            'payload' => $this->encodePayload($event->payload),
            'metadata' => $this->encodeMetadata($event->metadata),
            'occurred_at' => $event->metadata->occurredAt->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * Decode database row to StoredEvent.
     *
     * @param array<string, mixed> $row
     */
    public function fromDatabaseRow(array $row): StoredEvent
    {
        /** @var non-empty-string $eventIdStr */
        $eventIdStr = $row['event_id'];
        /** @var non-empty-string $tenantIdStr */
        $tenantIdStr = $row['tenant_id'];
        /** @var string $streamIdStr */
        $streamIdStr = $row['stream_id'];
        /** @var int|string $streamVersionVal */
        $streamVersionVal = $row['stream_version'];
        /** @var string $eventTypeStr */
        $eventTypeStr = $row['event_type'];
        /** @var string $eventClassNameStr */
        $eventClassNameStr = $row['event_class_name'] ?? '';
        /** @var string $payloadStr */
        $payloadStr = $row['payload'];
        /** @var string $metadataStr */
        $metadataStr = $row['metadata'];
        /** @var int|string|null $globalPositionVal */
        $globalPositionVal = $row['global_position'] ?? null;

        return new StoredEvent(
            eventId: EventId::fromString($eventIdStr),
            tenantId: TenantId::fromString($tenantIdStr),
            streamId: $streamIdStr,
            streamVersion: (int) $streamVersionVal,
            eventType: $eventTypeStr,
            eventClassName: $eventClassNameStr,
            payload: $this->decodePayload($payloadStr),
            metadata: EventMetadata::fromArray($this->decodeMetadata($metadataStr)),
            globalPosition: $globalPositionVal !== null ? (int) $globalPositionVal : null,
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function encodePayload(array $payload): string
    {
        return \json_encode($payload, JSON_THROW_ON_ERROR);
    }

    private function encodeMetadata(EventMetadata $metadata): string
    {
        return \json_encode($metadata->toArray(), JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodePayload(string $payload): array
    {
        /** @var array<string, mixed> $decoded */
        $decoded = \json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        return $decoded;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeMetadata(string $metadata): array
    {
        /** @var array<string, mixed> $decoded */
        $decoded = \json_decode($metadata, true, 512, JSON_THROW_ON_ERROR);
        return $decoded;
    }

    private function validateEvent(DomainEvent $event): void
    {
        // EventId and TenantId toString() always return non-empty-string
        // Additional validation for other fields
        if ($event->getEventType() === '') {
            throw new \InvalidArgumentException('Event must have a non-empty event type');
        }

        if ($event->getSchemaVersion() === '') {
            throw new \InvalidArgumentException('Event must have a non-empty schema version');
        }
    }
}
