<?php

declare(strict_types=1);

namespace Spiral\Kernel\Domain\Identity;

use Spiral\Kernel\Domain\Shared\ValueObject\ValueObject;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Value object representing a device identifier.
 *
 * DeviceId is a first-class identity primitive for offline mobile sync.
 * Every offline operation must be attributed to a DeviceId for:
 * - Conflict resolution (device priority strategies)
 * - Audit trails (which device performed an action)
 * - Vector clock composition (device as clock component)
 * - Sync checkpointing (per-device sync state)
 *
 * DeviceId is immutable and can be created from:
 * - A valid UUID string
 * - A new generated UUID
 *
 * @package Spiral\Kernel\Domain\Identity
 */
final class DeviceId extends ValueObject
{
    private function __construct(
        private readonly UuidInterface $uuid
    ) {
    }

    /**
     * Generate a new unique device ID.
     */
    public static function generate(): self
    {
        return new self(Uuid::uuid4());
    }

    /**
     * Create a device ID from an existing UUID string.
     *
     * @param non-empty-string $uuidString Valid UUID string
     * @throws \InvalidArgumentException If the string is not a valid UUID
     */
    public static function fromString(string $uuidString): self
    {
        if (trim($uuidString) === '') {
            throw new \InvalidArgumentException('DeviceId cannot be empty');
        }

        if (!Uuid::isValid($uuidString)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid DeviceId format: "%s"', $uuidString)
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
