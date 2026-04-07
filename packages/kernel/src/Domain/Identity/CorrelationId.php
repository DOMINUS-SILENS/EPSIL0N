<?php

declare(strict_types=1);

namespace Spiral\Kernel\Domain\Identity;

use Spiral\Kernel\Domain\Shared\ValueObject\ValueObject;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Value object representing a correlation identifier.
 *
 * CorrelationId groups related events, commands, and operations
 * across the entire request/response cycle and beyond.
 *
 * Every command handler SHOULD generate or propagate a CorrelationId
 * to enable distributed tracing and log aggregation.
 *
 * CorrelationId follows the correlation pattern:
 * - Generated at the entry point (API, CLI, consumer)
 * - Propagated through all subsequent operations
 * - Used to trace the full flow of a request
 *
 * @package Spiral\Kernel\Domain\Identity
 */
final class CorrelationId extends ValueObject
{
    private function __construct(
        private readonly UuidInterface $uuid
    ) {
    }

    /**
     * Generate a new unique correlation ID.
     */
    public static function generate(): self
    {
        return new self(Uuid::uuid4());
    }

    /**
     * Create a correlation ID from an existing UUID string.
     *
     * @param non-empty-string $uuidString Valid UUID string
     * @throws \InvalidArgumentException If the string is not a valid UUID
     */
    public static function fromString(string $uuidString): self
    {
        if (trim($uuidString) === '') {
            throw new \InvalidArgumentException('CorrelationId cannot be empty');
        }

        if (!Uuid::isValid($uuidString)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid CorrelationId format: "%s"', $uuidString)
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