<?php

declare(strict_types=1);

namespace Spiral\Kernel\Domain\Identity;

use Spiral\Kernel\Domain\Shared\ValueObject\ValueObject;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Value object representing an event identifier.
 *
 * EventId uniquely identifies domain events in the event store.
 * Each event persisted to the event store MUST have a unique EventId.
 *
 * EventId is immutable and uses UUID v7 for time-ordered identifiers
 * that remain globally unique and sortable by creation time.
 *
 * @package Spiral\Kernel\Domain\Identity
 */
final class EventId extends ValueObject
{
    private function __construct(
        private readonly UuidInterface $uuid
    ) {
    }

    /**
     * Generate a new unique event ID.
     *
     * Uses UUID v7 for time-ordered identifiers.
     */
    public static function generate(): self
    {
        return new self(Uuid::uuid7());
    }

    /**
     * Create an event ID from an existing UUID string.
     *
     * @param non-empty-string $uuidString Valid UUID string
     * @throws \InvalidArgumentException If the string is not a valid UUID
     */
    public static function fromString(string $uuidString): self
    {
        if ($uuidString === '') {
            throw new \InvalidArgumentException('EventId cannot be empty');
        }

        if (!Uuid::isValid($uuidString)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid EventId format: "%s"', $uuidString)
            );
        }

        return new self(Uuid::fromString($uuidString));
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

    /**
     * Get the timestamp when this event ID was created.
     *
     * Only available for UUID v7. Returns null for other UUID versions.
     */
    public function getTimestamp(): ?\DateTimeInterface
    {
        try {
            return $this->uuid->getDateTime();
        } catch (\Throwable) {
            return null;
        }
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