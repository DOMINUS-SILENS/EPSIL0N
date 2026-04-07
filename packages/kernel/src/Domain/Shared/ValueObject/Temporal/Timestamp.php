<?php

declare(strict_types=1);

namespace Spiral\Kernel\Domain\Shared\ValueObject\Temporal;

use DateTimeImmutable;
use DateTimeZone;
use Spiral\Kernel\Domain\Shared\ValueObject\ValueObject;

/**
 * Immutable value object representing a UTC timestamp.
 *
 * Timestamps represent exact moments in time with nanosecond precision.
 * All timestamps are normalized to UTC internally - this is a non-negotiable
 * invariant for event sourcing and temporal ordering.
 *
 * Key invariants:
 * - Always stored as UTC
 * - Immutable after construction
 * - Supports nanosecond precision via fractional seconds
 * - Serialization-compatible with ISO 8601
 *
 * @package Spiral\Kernel\Domain\Shared\ValueObject\Temporal
 */
final class Timestamp extends ValueObject
{
    private const ISO8601_FORMAT = 'Y-m-d\TH:i:s.uP';
    private const SERIALIZATION_FORMAT = 'Y-m-d\TH:i:s.u';

    private function __construct(
        private readonly DateTimeImmutable $dateTime
    ) {
        if ($this->dateTime->getTimezone()->getName() !== 'UTC') {
            throw new \InvalidArgumentException(
                'Timestamp must be UTC. Got: ' . $this->dateTime->getTimezone()->getName()
            );
        }
    }

    /**
     * Create a timestamp from the current time.
     */
    public static function now(): self
    {
        return new self(new DateTimeImmutable('now', new DateTimeZone('UTC')));
    }

    /**
     * Create a timestamp from a DateTimeImmutable (must be UTC).
     */
    public static function fromDateTime(DateTimeImmutable $dateTime): self
    {
        if ($dateTime->getTimezone()->getName() !== 'UTC') {
            $dateTime = $dateTime->setTimezone(new DateTimeZone('UTC'));
        }

        return new self($dateTime);
    }

    /**
     * Create a timestamp from a Unix timestamp (seconds since epoch).
     */
    public static function fromUnixTimestamp(int $timestamp): self
    {
        return new self(
            (new DateTimeImmutable('@' . $timestamp))->setTimezone(new DateTimeZone('UTC'))
        );
    }

    /**
     * Create a timestamp from a string (ISO 8601 format).
     */
    public static function fromString(string $value): self
    {
        $value = trim($value);

        if ($value === '') {
            throw new \InvalidArgumentException('Cannot parse timestamp from empty string');
        }

        $formats = [
            'Y-m-d\TH:i:s.uP',
            'Y-m-d\TH:i:sP',
            'Y-m-d\TH:i:s.u',
            'Y-m-d\TH:i:s',
            'Y-m-d',
        ];

        foreach ($formats as $format) {
            $parsed = DateTimeImmutable::createFromFormat($format, $value);
            if ($parsed !== false) {
                if ($parsed->getTimezone()->getName() !== 'UTC') {
                    $parsed = $parsed->setTimezone(new DateTimeZone('UTC'));
                }
                return new self($parsed);
            }
        }

        throw new \InvalidArgumentException(
            \sprintf('Cannot parse timestamp from string: %s', $value)
        );
    }

    /**
     * Create a timestamp from a microsecond integer.
     */
    public static function fromMicroseconds(int $microseconds): self
    {
        $seconds = (int) floor($microseconds / 1_000_000);
        $remainingMicroseconds = $microseconds % 1_000_000;

        $dateTime = (new DateTimeImmutable('@' . $seconds))
            ->setTimezone(new DateTimeZone('UTC'));

        if ($remainingMicroseconds > 0) {
            $dateTime = $dateTime->modify(sprintf('+%d microseconds', $remainingMicroseconds));
        }

        return new self($dateTime);
    }

    /**
     * Get the Unix timestamp (seconds since epoch).
     */
    public function toUnixTimestamp(): int
    {
        return (int) $this->dateTime->format('U');
    }

    /**
     * Get microseconds since epoch.
     */
    public function toMicroseconds(): int
    {
        $seconds = (int) $this->dateTime->format('U');
        $microseconds = (int) $this->dateTime->format('u');

        return $seconds * 1_000_000 + $microseconds;
    }

    /**
     * Get the native DateTimeImmutable (UTC).
     */
    public function toDateTime(): DateTimeImmutable
    {
        return $this->dateTime;
    }

    /**
     * Get the year component.
     */
    public function year(): int
    {
        return (int) $this->dateTime->format('Y');
    }

    /**
     * Get the month component (1-12).
     */
    public function month(): int
    {
        return (int) $this->dateTime->format('n');
    }

    /**
     * Get the day component (1-31).
     */
    public function day(): int
    {
        return (int) $this->dateTime->format('j');
    }

    /**
     * Get the hour component (0-23).
     */
    public function hour(): int
    {
        return (int) $this->dateTime->format('G');
    }

    /**
     * Get the minute component (0-59).
     */
    public function minute(): int
    {
        return (int) $this->dateTime->format('i');
    }

    /**
     * Get the second component (0-59).
     */
    public function second(): int
    {
        return (int) $this->dateTime->format('s');
    }

    /**
     * Get the microsecond component (0-999999).
     */
    public function microsecond(): int
    {
        return (int) $this->dateTime->format('u');
    }

    /**
     * Serialize to ISO 8601 string.
     */
    public function toIso8601(): string
    {
        return $this->dateTime->format(self::ISO8601_FORMAT);
    }

    /**
     * Serialize for storage (without timezone suffix for compatibility).
     */
    public function serialize(): string
    {
        return $this->dateTime->format(self::SERIALIZATION_FORMAT);
    }

    /**
     * Deserialize from storage format.
     */
    public static function deserialize(string $value): self
    {
        return self::fromString($value);
    }

    /**
     * Add a duration to this timestamp.
     */
    public function add(Duration $duration): self
    {
        $newDateTime = $this->dateTime
            ->modify(sprintf('+%d seconds', $duration->seconds()))
            ->modify(sprintf('+%d microseconds', $duration->nanoseconds() / 1000));

        return new self($newDateTime);
    }

    /**
     * Subtract a duration from this timestamp.
     */
    public function subtract(Duration $duration): self
    {
        $newDateTime = $this->dateTime
            ->modify(sprintf('-%d seconds', $duration->seconds()))
            ->modify(sprintf('-%d microseconds', $duration->nanoseconds() / 1000));

        return new self($newDateTime);
    }

    /**
     * Calculate the duration between this timestamp and another.
     */
    public function diff(self $other): Duration
    {
        $diffMicroseconds = $this->toMicroseconds() - $other->toMicroseconds();

        return Duration::fromMicroseconds(abs($diffMicroseconds));
    }

    /**
     * Check if this timestamp is before another.
     */
    public function isBefore(self $other): bool
    {
        return $this->toMicroseconds() < $other->toMicroseconds();
    }

    /**
     * Check if this timestamp is after another.
     */
    public function isAfter(self $other): bool
    {
        return $this->toMicroseconds() > $other->toMicroseconds();
    }

    /**
     * Check if this timestamp is at or before another.
     */
    public function isBeforeOrEqual(self $other): bool
    {
        return $this->toMicroseconds() <= $other->toMicroseconds();
    }

    /**
     * Check if this timestamp is at or after another.
     */
    public function isAfterOrEqual(self $other): bool
    {
        return $this->toMicroseconds() >= $other->toMicroseconds();
    }

    protected function valueEquals(ValueObject $other): bool
    {
        if (!$other instanceof self) {
            return false;
        }
        return $this->toMicroseconds() === $other->toMicroseconds();
    }

    public function __toString(): string
    {
        return $this->toIso8601();
    }
}
