<?php

declare(strict_types=1);

namespace Spiral\Kernel\Domain\Shared\Event;

use Spiral\Kernel\Domain\Identity\EventId;
use Spiral\Kernel\Domain\Identity\TenantId;
use Spiral\Kernel\Domain\Identity\CorrelationId;
use Spiral\Kernel\Domain\Identity\CausationId;
use Spiral\Kernel\Domain\Sync\SyncMetadata;
use DateTimeImmutable;

/**
 * Base class for all domain events.
 *
 * Provides a common foundation for both:
 * - Simple domain events (CustomerCreated) - used in tests/functional flows
 * - Event-sourced events (CustomerRegistered) - used in persistence/replay
 *
 * The inheritance hierarchy:
 *   DomainEventContract (new base)
 *       ├── CustomerCreated, CustomerRenamed (simple events)
 *       └── implements DomainEvent (for full-featured events)
 *
 * Why base class vs interface:
 * - instanceof checks work reliably
 * - Can provide default implementations
 * - Backward compatible with existing DomainEvent implementations
 */
abstract class DomainEventContract implements DomainEvent
{
    /**
     * Get the fully qualified class name.
     *
     * Default: Returns the class of the event itself.
     * Can be overridden for serialization aliasing.
     */
    public function getClassName(): string
    {
        return static::class;
    }

    /**
     * Get the event type name (human-readable).
     *
     * Default: Returns the short class name.
     * Example: CustomerRegistered → "CustomerRegistered"
     */
    public function getEventType(): string
    {
        $class = static::class;
        $parts = \explode('\\', $class);
        return (string)\end($parts);
    }

    /**
     * Optional sync metadata for offline mobile events.
     *
     * Default: Returns null (events don't participate in sync by default).
     * Override in subclasses if offline sync is needed.
     */
    public function getSyncMetadata(): ?SyncMetadata
    {
        return null;
    }

    /**
     * Serialize event to array.
     *
     * Must be implemented by subclasses to define their payload structure.
     *
     * @return array<string, mixed>
     */
    abstract public function toArray(): array;

    // Concrete implementations must provide these via constructor/properties:
    abstract public function getEventId(): EventId;

    abstract public function getTenantId(): TenantId;

    abstract public function getCorrelationId(): CorrelationId;

    abstract public function getCausationId(): CausationId;

    abstract public function getOccurredAt(): DateTimeImmutable;

    abstract public function getSchemaVersion(): string;
}
