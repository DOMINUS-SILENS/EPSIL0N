<?php

declare(strict_types=1);

namespace Spiral\Organization\Domain\ValueObject;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Spiral\Kernel\Domain\Shared\ValueObject\ValueObject;

/**
 * Value object representing an organization identifier.
 *
 * OrganizationId is the unique identity for the Organization aggregate.
 * It is scoped to a TenantId and uses UUID v4 for uniqueness.
 *
 * @package Spiral\Organization\Domain\ValueObject
 */
final class OrganizationId extends ValueObject
{
    private function __construct(
        private readonly UuidInterface $uuid
    ) {
    }

    /**
     * Generate a new unique organization ID.
     */
    public static function generate(): self
    {
        return new self(Uuid::uuid4());
    }

    /**
     * Create an organization ID from an existing UUID string.
     *
     * @param non-empty-string $uuidString Valid UUID string
     * @throws \InvalidArgumentException If the string is not a valid UUID
     */
    public static function fromString(string $uuidString): self
    {
        if ($uuidString === '') {
            throw new \InvalidArgumentException('OrganizationId cannot be empty');
        }

        if (!Uuid::isValid($uuidString)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid OrganizationId format: "%s"', $uuidString)
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
