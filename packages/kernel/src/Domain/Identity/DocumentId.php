<?php

declare(strict_types=1);

namespace Spiral\Kernel\Domain\Identity;

use Spiral\Kernel\Domain\Shared\ValueObject\ValueObject;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Value object representing a document identifier.
 *
 * DocumentId identifies domain documents such as:
 * - Invoices
 * - Purchase Orders
 * - Sales Orders
 * - Shipping Documents
 * - Financial Reports
 *
 * DocumentId is distinct from aggregate ID - a document may be
 * represented by multiple aggregates (header, lines, audit trail)
 * but shares a common DocumentId.
 *
 * @package Spiral\Kernel\Domain\Identity
 */
final class DocumentId extends ValueObject
{
    private function __construct(
        private readonly UuidInterface $uuid
    ) {
    }

    /**
     * Generate a new unique document ID.
     */
    public static function generate(): self
    {
        return new self(Uuid::uuid4());
    }

    /**
     * Create a document ID from an existing UUID string.
     *
     * @param non-empty-string $uuidString Valid UUID string
     * @throws \InvalidArgumentException If the string is not a valid UUID
     */
    public static function fromString(string $uuidString): self
    {
        if (trim($uuidString) === '') {
            throw new \InvalidArgumentException('DocumentId cannot be empty');
        }

        if (!Uuid::isValid($uuidString)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid DocumentId format: "%s"', $uuidString)
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