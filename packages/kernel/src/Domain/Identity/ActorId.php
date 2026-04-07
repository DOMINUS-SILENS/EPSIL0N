<?php

declare(strict_types=1);

namespace Spiral\Kernel\Domain\Identity;

use Spiral\Kernel\Domain\Shared\ValueObject\ValueObject;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Value object representing an actor identifier.
 *
 * ActorId represents the execution context - WHO performed an action.
 * This can be:
 * - A human user (authenticated via UserId)
 * - A service account (system-to-system)
 * - A scheduled job (cron/batch processing)
 * - An internal system process
 *
 * ActorId is used in audit trails, event metadata, and authorization
 * contexts to attribute actions to their source.
 *
 * @package Spiral\Kernel\Domain\Identity
 */
final class ActorId extends ValueObject
{
    private function __construct(
        private readonly UuidInterface $uuid
    ) {
    }

    /**
     * Generate a new unique actor ID.
     */
    public static function generate(): self
    {
        return new self(Uuid::uuid4());
    }

    /**
     * Create an actor ID from an existing UUID string.
     *
     * @param non-empty-string $uuidString Valid UUID string
     * @throws \InvalidArgumentException If the string is not a valid UUID
     */
    public static function fromString(string $uuidString): self
    {
        if (trim($uuidString) === '') {
            throw new \InvalidArgumentException('ActorId cannot be empty');
        }

        if (!Uuid::isValid($uuidString)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid ActorId format: "%s"', $uuidString)
            );
        }

        return new self(Uuid::fromString($uuidString));
    }

    /**
     * Create an ActorId from a UserId (for human user actions).
     */
    public static function fromUserId(UserId $userId): self
    {
        return self::fromString($userId->toString());
    }

    /**
     * Create a system actor ID for internal processes.
     */
    public static function system(): self
    {
        // System actor uses a well-known UUID namespace
        return new self(Uuid::fromString('00000000-0000-0000-0000-000000000000'));
    }

    /**
     * Check if this actor is the system actor.
     */
    public function isSystem(): bool
    {
        return $this->uuid->toString() === '00000000-0000-0000-0000-000000000000';
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