<?php

declare(strict_types=1);

namespace Spiral\Kernel\Infrastructure\Persistence\EventStore;

use Spiral\Kernel\Domain\Identity\EventId;
use Spiral\Kernel\Domain\Identity\TenantId;
use Spiral\Kernel\Domain\Shared\Event\DomainEvent;
use Spiral\Kernel\Domain\Shared\Event\EventMetadata;
use Spiral\Kernel\Domain\Shared\Event\StoredEvent;

/**
 * Hydrates StoredEvent back to DomainEvent.
 *
 * Provides strict deserialization with explicit failure on malformed data.
 */
final class EventHydrator
{
    /**
     * @var array<string, class-string<DomainEvent>>
     */
    private array $classMap = [];

    /**
     * @param array<string, class-string<DomainEvent>> $classMap Map of event type to class name
     */
    public function __construct(array $classMap = [])
    {
        $this->classMap = $classMap;
    }

    /**
     * Register an event type mapping.
     */
    public function registerEventType(string $eventType, string $className): void
    {
        if (!\is_a($className, DomainEvent::class, true)) {
            throw new \InvalidArgumentException(
                \sprintf('%s must implement DomainEvent', $className)
            );
        }

        $this->classMap[$eventType] = $className;
    }

    /**
     * Hydrate a StoredEvent to a DomainEvent.
     *
     * @throws \InvalidArgumentException On malformed event data
     * @throws \RuntimeException On unknown event type
     */
    public function hydrate(StoredEvent $storedEvent): DomainEvent
    {
        $this->validateStoredEvent($storedEvent);

        /** @var class-string<DomainEvent> $className */
        $className = $this->resolveEventClass($storedEvent->eventClassName, $storedEvent->eventType);

        return $this->reconstructEvent($className, $storedEvent->payload, $storedEvent->metadata);
    }

    /**
     * Hydrate multiple stored events.
     *
     * @param list<StoredEvent> $storedEvents
     * @return list<DomainEvent>
     */
    public function hydrateAll(array $storedEvents): array
    {
        return \array_map(fn(StoredEvent $e) => $this->hydrate($e), $storedEvents);
    }

    /**
     * Reconstruct a DomainEvent from stored payload and metadata.
     *
     * @param class-string<DomainEvent> $className
     * @param array<string, mixed> $payload
     */
    private function reconstructEvent(string $className, array $payload, EventMetadata $metadata): DomainEvent
    {
        $reflection = new \ReflectionClass($className);

        $constructor = $reflection->getConstructor();
        if ($constructor === null) {
            throw new \RuntimeException(\sprintf('Event %s has no constructor', $className));
        }

        $parameters = $constructor->getParameters();
        $args = [];

        foreach ($parameters as $param) {
            $name = $param->getName();
            $args[$name] = $this->resolveConstructorArg($param, $name, $payload, $metadata);
        }

        return $reflection->newInstanceArgs($args);
    }

    /**
     * @param \ReflectionParameter $param
     * @param array<string, mixed> $payload
     */
    private function resolveConstructorArg(
        \ReflectionParameter $param,
        string $name,
        array $payload,
        EventMetadata $metadata
    ): mixed {
        $type = $param->getType();

        if ($type instanceof \ReflectionNamedType) {
            $typeName = $type->getName();

            return match ($typeName) {
                TenantId::class => $this->hydrateTenantId($payload, $name),
                EventId::class => $this->hydrateEventId($payload, $name),
                \Spiral\Kernel\Domain\Identity\CorrelationId::class => $this->hydrateCorrelationId($metadata),
                \Spiral\Kernel\Domain\Identity\CausationId::class => $this->hydrateCausationId($metadata),
                \Spiral\Kernel\Domain\Identity\ActorId::class => $this->hydrateActorId($payload, $name),
                \DateTimeImmutable::class => $metadata->occurredAt,
                default => $this->hydrateFromPayload($payload, $name, $typeName),
            };
        }

        if (\array_key_exists($name, $payload)) {
            return $payload[$name];
        }

        throw new \InvalidArgumentException(
            \sprintf('Cannot resolve constructor parameter %s for event', $name)
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function hydrateTenantId(array $payload, string $fieldName): TenantId
    {
        if (!\array_key_exists($fieldName, $payload)) {
            throw new \InvalidArgumentException(\sprintf('Missing required field: %s', $fieldName));
        }

        $value = $payload[$fieldName];

        if (!\is_string($value) || $value === '') {
            throw new \InvalidArgumentException(\sprintf('Invalid TenantId: must be non-empty string, got %s', \gettype($value)));
        }

        return TenantId::fromString($value);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function hydrateEventId(array $payload, string $fieldName): EventId
    {
        if (!\array_key_exists($fieldName, $payload)) {
            throw new \InvalidArgumentException(\sprintf('Missing required field: %s', $fieldName));
        }

        $value = $payload[$fieldName];

        if (!\is_string($value) || $value === '') {
            throw new \InvalidArgumentException(\sprintf('Invalid EventId: must be non-empty string'));
        }

        return EventId::fromString($value);
    }

    private function hydrateCorrelationId(EventMetadata $metadata): \Spiral\Kernel\Domain\Identity\CorrelationId
    {
        return $metadata->correlationId;
    }

    private function hydrateCausationId(EventMetadata $metadata): \Spiral\Kernel\Domain\Identity\CausationId
    {
        return $metadata->causationId;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function hydrateActorId(array $payload, string $fieldName): \Spiral\Kernel\Domain\Identity\ActorId
    {
        if (!\array_key_exists($fieldName, $payload)) {
            throw new \InvalidArgumentException(\sprintf('Missing required field: %s', $fieldName));
        }

        $value = $payload[$fieldName];

        if (!\is_string($value) || $value === '') {
            throw new \InvalidArgumentException(\sprintf('Invalid ActorId: must be non-empty string'));
        }

        return \Spiral\Kernel\Domain\Identity\ActorId::fromString($value);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function hydrateFromPayload(array $payload, string $name, string $typeName): mixed
    {
        if (!\array_key_exists($name, $payload)) {
            throw new \InvalidArgumentException(\sprintf('Missing required field: %s', $name));
        }

        $value = $payload[$name];

        if ($typeName === 'string' && !\is_string($value)) {
            throw new \InvalidArgumentException(\sprintf('Field %s must be string, got %s', $name, \gettype($value)));
        }

        if ($typeName === 'int' && !\is_int($value)) {
            throw new \InvalidArgumentException(\sprintf('Field %s must be int, got %s', $name, \gettype($value)));
        }

        return $value;
    }

    private function resolveEventClass(string $className, string $eventType): string
    {
        if (\class_exists($className)) {
            return $className;
        }

        if (isset($this->classMap[$eventType])) {
            return $this->classMap[$eventType];
        }

        throw new \RuntimeException(
            \sprintf(
                'Cannot resolve event class for type "%s". Class "%s" does not exist and no mapping found.',
                $eventType,
                $className
            )
        );
    }

    private function validateStoredEvent(StoredEvent $event): void
    {
        // Validation removed - types enforced by constructor
        if ($event->streamId === '') {
            throw new \InvalidArgumentException('StoredEvent has empty stream_id');
        }

        if ($event->eventType === '') {
            throw new \InvalidArgumentException('StoredEvent has empty event_type');
        }

        if ($event->eventClassName === '') {
            throw new \InvalidArgumentException('StoredEvent has empty event_class_name');
        }

        if (!\is_array($event->payload)) {
            throw new \InvalidArgumentException(\sprintf('StoredEvent payload must be array, got %s', \gettype($event->payload)));
        }
    }
}
