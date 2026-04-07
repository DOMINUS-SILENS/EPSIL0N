<?php

declare(strict_types=1);

namespace Spiral\Kernel\Domain\Shared\ValueObject\Temporal;

use Spiral\Kernel\Domain\Shared\ValueObject\ValueObject;

/**
 * Immutable value object representing a business period with start and end dates.
 *
 * BusinessPeriod represents a span of time with defined boundaries, distinct from
 * Duration which represents an amount of time without fixed endpoints.
 *
 * Key invariants:
 * - Start must be before or equal to end
 * - Immutable after construction
 * - Supports containment, overlap, and gap calculations
 * - Can be inclusive or exclusive on boundaries
 *
 * Use cases:
 * - Payroll periods
 * - Accounting periods
 * - Reporting windows
 * - Subscriptions/contracts
 * - Inventory valuation periods
 *
 * @package Spiral\Kernel\Domain\Shared\ValueObject\Temporal
 */
final class BusinessPeriod extends ValueObject
{
    private function __construct(
        private readonly BusinessDate $start,
        private readonly BusinessDate $end,
        private readonly bool $startInclusive,
        private readonly bool $endInclusive
    ) {
        if ($start->isAfter($end)) {
            throw new \InvalidArgumentException(
                \sprintf(
                    'Start date %s must be before or equal to end date %s',
                    $start->serialize(),
                    $end->serialize()
                )
            );
        }
    }

    /**
     * Create an inclusive period (both start and end are included).
     */
    public static function inclusive(BusinessDate $start, BusinessDate $end): self
    {
        return new self($start, $end, true, true);
    }

    /**
     * Create an exclusive period (neither start nor end is included).
     */
    public static function exclusive(BusinessDate $start, BusinessDate $end): self
    {
        return new self($start, $end, false, false);
    }

    /**
     * Create a half-open period [start, end) - start inclusive, end exclusive.
     */
    public static function halfOpen(BusinessDate $start, BusinessDate $end): self
    {
        return new self($start, $end, true, false);
    }

    /**
     * Create a half-open period (start exclusive, end inclusive).
     */
    public static function halfClosed(BusinessDate $start, BusinessDate $end): self
    {
        return new self($start, $end, false, true);
    }

    /**
     * Create a period from a single day.
     */
    public static function forDay(BusinessDate $day): self
    {
        return self::inclusive($day, $day);
    }

    /**
     * Create a period spanning a whole month.
     */
    public static function forMonth(int $year, int $month, TimezoneId $timezone): self
    {
        $start = BusinessDate::create($year, $month, 1, $timezone);
        $end = $start->endOfMonth();

        return self::inclusive($start, $end);
    }

    /**
     * Create a period spanning a whole quarter.
     */
    public static function forQuarter(int $year, int $quarter, TimezoneId $timezone): self
    {
        if ($quarter < 1 || $quarter > 4) {
            throw new \InvalidArgumentException(
                \sprintf('Invalid quarter: %d. Must be between 1 and 4', $quarter)
            );
        }

        $startMonth = ($quarter - 1) * 3 + 1;
        $start = BusinessDate::create($year, $startMonth, 1, $timezone);
        $end = $start->addMonths(2)->endOfMonth();

        return self::inclusive($start, $end);
    }

    /**
     * Create a period spanning a whole year.
     */
    public static function forYear(int $year, TimezoneId $timezone): self
    {
        $start = BusinessDate::create($year, 1, 1, $timezone);
        $end = BusinessDate::create($year, 12, 31, $timezone);

        return self::inclusive($start, $end);
    }

    /**
     * Get the start date.
     */
    public function start(): BusinessDate
    {
        return $this->start;
    }

    /**
     * Get the end date.
     */
    public function end(): BusinessDate
    {
        return $this->end;
    }

    /**
     * Check if start is inclusive.
     */
    public function isStartInclusive(): bool
    {
        return $this->startInclusive;
    }

    /**
     * Check if end is inclusive.
     */
    public function isEndInclusive(): bool
    {
        return $this->endInclusive;
    }

    /**
     * Get the number of days in this period.
     */
    public function days(): int
    {
        $startTs = $this->start->toTimestamp()->toUnixTimestamp();
        $endTs = $this->end->toTimestamp()->toUnixTimestamp();

        $days = (int) floor(($endTs - $startTs) / 86400);

        if ($this->startInclusive && $this->endInclusive) {
            $days += 1;
        } elseif (!$this->startInclusive && !$this->endInclusive) {
            $days -= 1;
        }

        return max(0, $days);
    }

    /**
     * Check if a date falls within this period.
     */
    public function contains(BusinessDate $date): bool
    {
        $startCompare = $this->startInclusive
            ? !$date->isBefore($this->start)
            : $date->isAfter($this->start);

        $endCompare = $this->endInclusive
            ? !$date->isAfter($this->end)
            : $date->isBefore($this->end);

        return $startCompare && $endCompare;
    }

    /**
     * Check if this period contains another period entirely.
     */
    public function containsPeriod(self $other): bool
    {
        $startCompare = $this->startInclusive
            ? !$other->start->isBefore($this->start)
            : $other->start->isAfter($this->start);

        $endCompare = $this->endInclusive
            ? !$other->end->isAfter($this->end)
            : $other->end->isBefore($this->end);

        return $startCompare && $endCompare;
    }

    /**
     * Check if this period overlaps with another.
     */
    public function overlaps(self $other): bool
    {
        $thisStart = $this->startInclusive ? $this->start : $this->start->addDays(1);
        $thisEnd = $this->endInclusive ? $this->end : $this->end->addDays(-1);
        $otherStart = $other->startInclusive ? $other->start : $other->start->addDays(1);
        $otherEnd = $other->endInclusive ? $other->end : $other->end->addDays(-1);

        return !$thisEnd->isBefore($otherStart) && !$otherEnd->isBefore($thisStart);
    }

    /**
     * Check if there is a gap between this period and another.
     */
    public function hasGap(self $other): bool
    {
        return !$this->overlaps($other) && !$this->touches($other);
    }

    /**
     * Check if this period touches another (end of one is start of another).
     */
    public function touches(self $other): bool
    {
        $thisEnd = $this->endInclusive ? $this->end : $this->end->addDays(-1);
        $otherStart = $other->startInclusive ? $other->start : $other->start->addDays(1);

        return $thisEnd->addDays(1)->isSameCalendarDay($otherStart);
    }

    /**
     * Get the intersection of this period with another.
     * Returns null if they don't overlap.
     */
    public function intersection(self $other): ?self
    {
        if (!$this->overlaps($other)) {
            return null;
        }

        $start = $this->start->isAfter($other->start) ? $this->start : $other->start;
        $end = $this->end->isBefore($other->end) ? $this->end : $other->end;

        return new self($start, $end, true, true);
    }

    /**
     * Get the union of this period with another.
     * Returns null if they don't overlap and don't touch.
     */
    public function union(self $other): ?self
    {
        if (!$this->overlaps($other) && !$this->touches($other)) {
            return null;
        }

        $start = $this->start->isBefore($other->start) ? $this->start : $other->start;
        $end = $this->end->isAfter($other->end) ? $this->end : $other->end;

        return new self($start, $end, true, true);
    }

    /**
     * Extend the period by adding days to the end.
     */
    public function extend(int $days): self
    {
        return new self(
            $this->start,
            $this->end->addDays($days),
            $this->startInclusive,
            $this->endInclusive
        );
    }

    /**
     * Shift the period by a number of days.
     */
    public function shift(int $days): self
    {
        return new self(
            $this->start->addDays($days),
            $this->end->addDays($days),
            $this->startInclusive,
            $this->endInclusive
        );
    }

    /**
     * Get all dates in this period as an iterator.
     *
     * @return \Generator<BusinessDate>
     */
    public function dates(): \Generator
    {
        $current = $this->startInclusive ? $this->start : $this->start->addDays(1);
        $end = $this->endInclusive ? $this->end : $this->end->addDays(-1);

        while (!$current->isAfter($end)) {
            yield $current;
            $current = $current->addDays(1);
        }
    }

    public function serialize(): string
    {
        $startFlag = $this->startInclusive ? '[' : '(';
        $endFlag = $this->endInclusive ? ']' : ')';

        return \sprintf(
            '%s%s,%s%s',
            $startFlag,
            $this->start->serialize(),
            $this->end->serialize(),
            $endFlag
        );
    }

    public static function deserialize(string $value, TimezoneId $timezone): self
    {
        if (preg_match('/^(\[|\()(.+),(.+)(\]|\))$/', $value, $matches)) {
            $startInclusive = $matches[1] === '[';
            $endInclusive = $matches[4] === ']';

            $start = BusinessDate::fromString($matches[2], $timezone);
            $end = BusinessDate::fromString($matches[3], $timezone);

            return new self($start, $end, $startInclusive, $endInclusive);
        }

        throw new \InvalidArgumentException(
            \sprintf('Cannot deserialize BusinessPeriod from: %s', $value)
        );
    }

    protected function valueEquals(ValueObject $other): bool
    {
        if (!$other instanceof self) {
            return false;
        }
        return $this->start->equals($other->start)
            && $this->end->equals($other->end)
            && $this->startInclusive === $other->startInclusive
            && $this->endInclusive === $other->endInclusive;
    }

    public function __toString(): string
    {
        return $this->serialize();
    }
}
