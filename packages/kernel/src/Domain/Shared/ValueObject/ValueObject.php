<?php

declare(strict_types=1);

namespace Spiral\Kernel\Domain\Shared\ValueObject;

/**
 * Base class for all value objects in the Kernel.
 *
 * Value objects are immutable, self-validating primitives that represent
 * domain concepts with inherent constraints. They encapsulate both the
 * value and its validation rules, ensuring that invalid states cannot
 * exist within the system.
 *
 * Key characteristics:
 * - Immutable: all properties are readonly
 * - Self-validating: constructor enforces invariants
 * - Comparable: equality is based on value, not identity
 * - Primitive-safe: prevents primitive obsession
 *
 * All value objects in the Kernel MUST extend this class and enforce
 * their invariants in the constructor.
 *
 * @package Spiral\Kernel\Domain\Shared\ValueObject
 */
abstract class ValueObject
{
    /**
     * All value objects are comparable by value equality.
     *
     * @param self $other
     */
    public function equals(self $other): bool
    {
        // Value objects of different types are never equal
        if (get_class($this) !== get_class($other)) {
            return false;
        }

        return $this->valueEquals($other);
    }

    /**
     * Compare the internal values of two value objects of the same type.
     *
     * Override this method to implement custom equality logic.
     * Default implementation compares all readonly properties.
     *
     * @param self $other Same type as $this
     */
    abstract protected function valueEquals(self $other): bool;

    /**
     * Get a string representation of this value object.
     *
     * Override this method to provide a custom string representation.
     */
    abstract public function __toString(): string;

    /**
     * Get a hash representation for use in arrays/sets.
     */
    public function hash(): string
    {
        return md5((string) $this);
    }
}