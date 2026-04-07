<?php

declare(strict_types=1);

namespace Spiral\Kernel\Domain\Shared\ValueObject\Temporal;

use Spiral\Kernel\Domain\Shared\ValueObject\ValueObject;

/**
 * Immutable value object representing a timezone identifier.
 *
 * TimezoneId encapsulates IANA timezone identifiers (e.g., "Europe/London")
 * with validation. It provides a controlled way to handle timezone-aware
 * operations while keeping the core Timestamp as UTC-only.
 *
 * Key invariants:
 * - Must be a valid IANA timezone identifier
 * - Immutable after construction
 * - Provides timezone conversion capabilities
 *
 * @package Spiral\Kernel\Domain\Shared\ValueObject\Temporal
 */
final class TimezoneId extends ValueObject
{
    private function __construct(
        private readonly string $timezone
    ) {
    }

    /**
     * Create a timezone from an IANA identifier.
     *
     * @param string $timezone IANA timezone identifier (e.g., "America/New_York")
     */
    public static function fromString(string $timezone): self
    {
        $timezone = trim($timezone);

        if ($timezone === '') {
            throw new \InvalidArgumentException('Timezone identifier cannot be empty');
        }

        if (!self::isValidTimezone($timezone)) {
            throw new \InvalidArgumentException(
                \sprintf('Invalid IANA timezone identifier: %s', $timezone)
            );
        }

        return new self($timezone);
    }

    /**
     * Create UTC timezone.
     */
    public static function utc(): self
    {
        return new self('UTC');
    }

    /**
     * Create a timezone from a fixed offset (e.g., "+05:30").
     *
     * @param string $offset Offset in format "+HH:MM" or "-HH:MM"
     */
    public static function fromOffset(string $offset): self
    {
        $offset = trim($offset);

        if (!preg_match('/^[+-]\d{2}:\d{2}$/', $offset)) {
            throw new \InvalidArgumentException(
                \sprintf('Invalid offset format: %s. Expected format: +HH:MM or -HH:MM', $offset)
            );
        }

        $sign = $offset[0] === '+' ? 1 : -1;
        [$hours, $minutes] = explode(':', substr($offset, 1));
        $hours = (int) $hours;
        $minutes = (int) $minutes;

        $totalMinutes = $sign * ($hours * 60 + $minutes);

        $tz = match ($totalMinutes) {
            0 => 'UTC',
            default => "Etc/GMT{$offset}",
        };

        return new self($tz);
    }

    /**
     * Check if a timezone identifier is valid.
     */
    private static function isValidTimezone(string $timezone): bool
    {
        return \in_array($timezone, \DateTimeZone::listIdentifiers(), true);
    }

    /**
     * Get the timezone identifier.
     */
    public function timezone(): string
    {
        return $this->timezone;
    }

    /**
     * Get the current time in this timezone.
     */
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', new \DateTimeZone($this->timezone));
    }

    /**
     * Convert a UTC timestamp to this timezone.
     */
    public function convertFromUtc(\DateTimeImmutable $utcTimestamp): \DateTimeImmutable
    {
        return $utcTimestamp->setTimezone(new \DateTimeZone($this->timezone));
    }

    /**
     * Get the UTC offset for a given timestamp.
     */
    public function getOffset(\DateTimeImmutable $timestamp): int
    {
        $tz = new \DateTimeZone($this->timezone);
        return $tz->getOffset($timestamp);
    }

    /**
     * Get the UTC offset as a string (e.g., "+05:30").
     */
    public function getOffsetString(\DateTimeImmutable $timestamp): string
    {
        $offset = $this->getOffset($timestamp);
        $hours = (int) floor($offset / 3600);
        $minutes = (int) floor(($offset % 3600) / 60);

        return sprintf('%+03d:%02d', $hours, abs($minutes));
    }

    /**
     * Check if this timezone observes daylight saving time.
     */
    public function observesDst(?\DateTimeImmutable $timestamp = null): bool
    {
        $timestamp = $timestamp ?? new \DateTimeImmutable('now');
        $tz = new \DateTimeZone($this->timezone);
        $transitions = $tz->getTransitions($timestamp->getTimestamp(), $timestamp->getTimestamp());

        return count($transitions) > 1 || ($transitions[0]['isdst'] ?? false);
    }

    public function serialize(): string
    {
        return $this->timezone;
    }

    public static function deserialize(string $value): self
    {
        return self::fromString($value);
    }

    protected function valueEquals(ValueObject $other): bool
    {
        if (!$other instanceof self) {
            return false;
        }
        return $this->timezone === $other->timezone;
    }

    public function __toString(): string
    {
        return $this->timezone;
    }
}
