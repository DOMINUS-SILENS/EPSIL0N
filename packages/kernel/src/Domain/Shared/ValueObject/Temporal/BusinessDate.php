<?php

declare(strict_types=1);

namespace Spiral\Kernel\Domain\Shared\ValueObject\Temporal;

use Spiral\Kernel\Domain\Shared\ValueObject\ValueObject;

/**
 * Immutable value object representing a business date.
 *
 * BusinessDate is distinct from Timestamp because they represent different
 * domain concepts:
 * - Timestamp: exact moment of event occurrence
 * - BusinessDate: effective date for accounting, reporting, posting
 *
 * A business date is always a calendar date (no time component) and is
 * conceptually in a specific timezone context (usually the organization's
 * primary timezone).
 *
 * Key invariants:
 * - Always represents a full calendar day (no time component)
 * - Immutable after construction
 * - Supports fiscal period calculations
 * - Distinct from Timestamp - never conflate the two
 *
 * @package Spiral\Kernel\Domain\Shared\ValueObject\Temporal
 */
final class BusinessDate extends ValueObject
{
    private function __construct(
        private readonly int $year,
        private readonly int $month,
        private readonly int $day,
        private readonly TimezoneId $timezone
    ) {
        if ($month < 1 || $month > 12) {
            throw new \InvalidArgumentException(
                \sprintf('Invalid month: %d. Must be between 1 and 12', $month)
            );
        }

        if ($day < 1 || $day > 31) {
            throw new \InvalidArgumentException(
                \sprintf('Invalid day: %d. Must be between 1 and 31', $day)
            );
        }

        if (!checkdate($month, $day, $year)) {
            throw new \InvalidArgumentException(
                \sprintf('Invalid date: %04d-%02d-%02d', $year, $month, $day)
            );
        }
    }

    /**
     * Create a business date for today in the given timezone.
     */
    public static function today(TimezoneId $timezone): self
    {
        $now = $timezone->now();

        return new self(
            (int) $now->format('Y'),
            (int) $now->format('n'),
            (int) $now->format('j'),
            $timezone
        );
    }

    /**
     * Create a business date from year, month, day components.
     */
    public static function create(int $year, int $month, int $day, TimezoneId $timezone): self
    {
        return new self($year, $month, $day, $timezone);
    }

    /**
     * Create a business date from a string (Y-m-d format).
     */
    public static function fromString(string $value, TimezoneId $timezone): self
    {
        $value = trim($value);

        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $value);
        if ($parsed === false) {
            $parsed = \DateTimeImmutable::createFromFormat('Y/m/d', $value);
        }

        if ($parsed === false) {
            throw new \InvalidArgumentException(
                \sprintf('Cannot parse business date from string: %s', $value)
            );
        }

        return new self(
            (int) $parsed->format('Y'),
            (int) $parsed->format('n'),
            (int) $parsed->format('j'),
            $timezone
        );
    }

    /**
     * Create a business date from a Timestamp (date component only).
     */
    public static function fromTimestamp(Timestamp $timestamp, TimezoneId $timezone): self
    {
        $dateTime = $timezone->convertFromUtc($timestamp->toDateTime());

        return new self(
            (int) $dateTime->format('Y'),
            (int) $dateTime->format('n'),
            (int) $dateTime->format('j'),
            $timezone
        );
    }

    /**
     * Get the year component.
     */
    public function year(): int
    {
        return $this->year;
    }

    /**
     * Get the month component (1-12).
     */
    public function month(): int
    {
        return $this->month;
    }

    /**
     * Get the day component (1-31).
     */
    public function day(): int
    {
        return $this->day;
    }

    /**
     * Get the timezone.
     */
    public function timezone(): TimezoneId
    {
        return $this->timezone;
    }

    /**
     * Get the day of week (1 = Monday, 7 = Sunday).
     */
    public function dayOfWeek(): int
    {
        return (int) $this->toDateTime()->format('N');
    }

    /**
     * Check if this is a weekend day.
     */
    public function isWeekend(): bool
    {
        return $this->dayOfWeek() >= 6;
    }

    /**
     * Check if this is the last day of the month.
     */
    public function isEndOfMonth(): bool
    {
        return $this->day === (int) $this->toDateTime()->format('t');
    }

    /**
     * Get the week of year (1-53).
     */
    public function weekOfYear(): int
    {
        return (int) $this->toDateTime()->format('W');
    }

    /**
     * Get the quarter (1-4).
     */
    public function quarter(): int
    {
        return (int) ceil($this->month / 3);
    }

    /**
     * Add days to this business date.
     */
    public function addDays(int $days): self
    {
        $newDate = $this->toDateTime()->modify(sprintf('%+d days', $days));

        return new self(
            (int) $newDate->format('Y'),
            (int) $newDate->format('n'),
            (int) $newDate->format('j'),
            $this->timezone
        );
    }

    /**
     * Add months to this business date.
     */
    public function addMonths(int $months): self
    {
        $newDate = $this->toDateTime()->modify(sprintf('%+d months', $months));

        return new self(
            (int) $newDate->format('Y'),
            (int) $newDate->format('n'),
            (int) $newDate->format('j'),
            $this->timezone
        );
    }

    /**
     * Add years to this business date.
     */
    public function addYears(int $years): self
    {
        $newDate = $this->toDateTime()->modify(sprintf('%+d years', $years));

        return new self(
            (int) $newDate->format('Y'),
            (int) $newDate->format('n'),
            (int) $newDate->format('j'),
            $this->timezone
        );
    }

    /**
     * Get the start of the week (Monday) containing this date.
     */
    public function startOfWeek(): self
    {
        $daysToSubtract = $this->dayOfWeek() - 1;

        return $this->addDays(-$daysToSubtract);
    }

    /**
     * Get the end of the week (Sunday) containing this date.
     */
    public function endOfWeek(): self
    {
        $daysToAdd = 7 - $this->dayOfWeek();

        return $this->addDays($daysToAdd);
    }

    /**
     * Get the start of the month containing this date.
     */
    public function startOfMonth(): self
    {
        return new self($this->year, $this->month, 1, $this->timezone);
    }

    /**
     * Get the end of the month containing this date.
     */
    public function endOfMonth(): self
    {
        $daysInMonth = (int) $this->toDateTime()->format('t');

        return new self($this->year, $this->month, $daysInMonth, $this->timezone);
    }

    /**
     * Get the start of the year containing this date.
     */
    public function startOfYear(): self
    {
        return new self($this->year, 1, 1, $this->timezone);
    }

    /**
     * Get the end of the year containing this date.
     */
    public function endOfYear(): self
    {
        return new self($this->year, 12, 31, $this->timezone);
    }

    /**
     * Convert to a Timestamp at midnight (00:00:00) in the timezone.
     */
    public function toTimestamp(): Timestamp
    {
        return Timestamp::fromString($this->format('Y-m-d') . 'T00:00:00+00:00');
    }

    /**
     * Convert to native DateTimeImmutable.
     */
    public function toDateTime(): \DateTimeImmutable
    {
        return new \DateTimeImmutable(
            sprintf('%04d-%02d-%02d', $this->year, $this->month, $this->day),
            new \DateTimeZone($this->timezone->timezone())
        );
    }

    /**
     * Format the date as a string.
     */
    public function format(string $format): string
    {
        return $this->toDateTime()->format($format);
    }

    /**
     * Check if this date is before another.
     */
    public function isBefore(self $other): bool
    {
        return $this->toTimestamp()->isBefore($other->toTimestamp());
    }

    /**
     * Check if this date is after another.
     */
    public function isAfter(self $other): bool
    {
        return $this->toTimestamp()->isAfter($other->toTimestamp());
    }

    /**
     * Check if this date equals another (ignores timezone differences for same calendar day).
     */
    public function isSameCalendarDay(self $other): bool
    {
        return $this->year === $other->year
            && $this->month === $other->month
            && $this->day === $other->day;
    }

    public function serialize(): string
    {
        return sprintf('%04d-%02d-%02d', $this->year, $this->month, $this->day);
    }

    public static function deserialize(string $value, TimezoneId $timezone): self
    {
        return self::fromString($value, $timezone);
    }

    protected function valueEquals(ValueObject $other): bool
    {
        if (!$other instanceof self) {
            return false;
        }
        return $this->year === $other->year
            && $this->month === $other->month
            && $this->day === $other->day;
    }

    public function __toString(): string
    {
        return $this->serialize();
    }
}
