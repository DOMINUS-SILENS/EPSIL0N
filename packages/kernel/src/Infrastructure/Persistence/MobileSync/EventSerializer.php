<?php

declare(strict_types=1);

namespace Spiral\Kernel\Infrastructure\Persistence\MobileSync;

use Spiral\Kernel\Domain\Shared\Event\DomainEvent;

/**
 * Serializer for offline queue events.
 *
 * Handles serialization/deserialization of domain events
 * for storage in the offline queue.
 *
 * @package Spiral\Kernel\Infrastructure\Persistence\MobileSync
 */
final class EventSerializer
{
    /**
     * @var array<string, class-string<DomainEvent>>
     */
    private array $eventTypeMap = [];

    /**
     * Register an event type mapping.
     *
     * @param string $eventType Event type name
     * @param class-string<DomainEvent> $className Event class name
     */
    public function registerEventType(string $eventType, string $className): void
    {
        $this->eventTypeMap[$eventType] = $className;
    }

    /**
     * Serialize an event to array.
     *
     * @return array<string, mixed>
     */
    public function serialize(DomainEvent $event): array
    {
        return $event->toArray();
    }

    /**
     * Deserialize an event from array.
     *
     * @param array<string, mixed> $data Event data
     * @param string $eventType Event type name
     */
    public function deserialize(array $data, string $eventType): DomainEvent
    {
        if (!isset($this->eventTypeMap[$eventType])) {
            throw new \RuntimeException(sprintf('Unknown event type: %s', $eventType));
        }

        $className = $this->eventTypeMap[$eventType];

        if (!method_exists($className, 'fromArray')) {
            throw new \RuntimeException(
                sprintf('Event class %s must implement a static fromArray() method', $className)
            );
        }

        return $className::fromArray($data);
    }
}
