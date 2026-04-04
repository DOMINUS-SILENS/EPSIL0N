<?php

declare(strict_types=1);

namespace Spiral\Kernel\Domain\Identity;

use Spiral\Kernel\Domain\Shared\ValueObject\ValueObject;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Value object representing a causation identifier.
 *
 * CausationId establishes the causal chain between events - WHY an event
 * was produced. It links events to their triggering cause.
 *
 * Causation patterns:
 * - Command triggers Event: Event.causationId = Command.id
 * - Event triggers Saga: Saga.causationId = Event.id
 * - Saga triggers Command: Command.causationId = Saga.id
 *
 * This enables:
 * - Event replay with proper causation chains
 * - Debugging the origin of side effects
 * - Compliance audits for traceability
 *
 * @package Spiral\Kernel\Domain\Identity
 */
final class CausationId extends ValueObject
{
    private function __construct(
        private readonly UuidInterface $uuid
    ) {
    }

    /**
     * Generate a new unique causation ID.
     */
    public static function generate(): self
    {
        return new self(Uuid::uuid4());
    }

    /**
     * Create a causation ID from an existing UUID string.
     *
     * @param non-empty-string $uuidString Valid UUID string
     * @throws \InvalidArgumentException If the string is not a valid UUID
     */
    public static function fromString(string $uuidString): self
    {
        if ($uuidString === '') {
            throw new \InvalidArgumentException('CausationId cannot be empty');
        }

        if (!Uuid::isValid($uuidString)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid CausationId format: "%s"', $uuidString)
            );
        }

        return new self(Uuid::fromString($uuidString));
    }

    /**
     * Create a CausationId from an EventId (for event-triggered operations).
     */
    public static function fromEventId(EventId $eventId): self
    {
        return self::fromString($eventId->toString());
    }

    /**
     * Get the UUID string representation.
     *
     * @return non-empty-string
     */
    public function toString(): string
    {
        return $this->uuid->toString();
    }

    /**
     * Get the UUID object for database storage.
     */
    public function toUuid(): UuidInterface
    {
        return $this->uuid;
    }

    protected function valueEquals(ValueObject $other): bool
    {
        \assert($other instanceof self);
        return $this->uuid->equals($other->uuid);
    }

    public function __toString(): string
    {
        return $this->uuid->toString();
    }
}