<?php

declare(strict_types=1);

namespace Spiral\Kernel\Tests\Unit\Domain\Shared\ValueObject\Temporal;

use PHPUnit\Framework\TestCase;
use Spiral\Kernel\Domain\Shared\ValueObject\Temporal\BusinessDate;
use Spiral\Kernel\Domain\Shared\ValueObject\Temporal\TimezoneId;

final class BusinessDateTest extends TestCase
{
    private TimezoneId $utc;

    protected function setUp(): void
    {
        $this->utc = TimezoneId::utc();
    }

    public function testCreate(): void
    {
        $date = BusinessDate::create(2024, 1, 15, $this->utc);

        $this->assertSame(2024, $date->year());
        $this->assertSame(1, $date->month());
        $this->assertSame(15, $date->day());
    }

    public function testCreateRejectsInvalidMonth(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        BusinessDate::create(2024, 13, 1, $this->utc);
    }

    public function testCreateRejectsInvalidDay(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        BusinessDate::create(2024, 2, 30, $this->utc);
    }

    public function testFromString(): void
    {
        $date = BusinessDate::fromString('2024-01-15', $this->utc);

        $this->assertSame(2024, $date->year());
        $this->assertSame(1, $date->month());
        $this->assertSame(15, $date->day());
    }

    public function testFromStringRejectsInvalidFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        BusinessDate::fromString('not-a-date', $this->utc);
    }

    public function testToday(): void
    {
        $date = BusinessDate::today($this->utc);

        $this->assertInstanceOf(BusinessDate::class, $date);
    }

    public function testDayOfWeek(): void
    {
        $date = BusinessDate::create(2024, 1, 1, $this->utc);

        $this->assertGreaterThanOrEqual(1, $date->dayOfWeek());
        $this->assertLessThanOrEqual(7, $date->dayOfWeek());
    }

    public function testIsWeekend(): void
    {
        $monday = BusinessDate::create(2024, 1, 1, $this->utc);
        $saturday = BusinessDate::create(2024, 1, 6, $this->utc);

        $this->assertFalse($monday->isWeekend());
        $this->assertTrue($saturday->isWeekend());
    }

    public function testQuarter(): void
    {
        $q1 = BusinessDate::create(2024, 2, 15, $this->utc);
        $q2 = BusinessDate::create(2024, 5, 15, $this->utc);

        $this->assertSame(1, $q1->quarter());
        $this->assertSame(2, $q2->quarter());
    }

    public function testAddDays(): void
    {
        $date = BusinessDate::create(2024, 1, 15, $this->utc);
        $result = $date->addDays(10);

        $this->assertSame(25, $result->day());
    }

    public function testAddMonths(): void
    {
        $date = BusinessDate::create(2024, 1, 15, $this->utc);
        $result = $date->addMonths(2);

        $this->assertSame(3, $result->month());
    }

    public function testAddYears(): void
    {
        $date = BusinessDate::create(2024, 1, 15, $this->utc);
        $result = $date->addYears(1);

        $this->assertSame(2025, $result->year());
    }

    public function testStartOfMonth(): void
    {
        $date = BusinessDate::create(2024, 1, 15, $this->utc);
        $start = $date->startOfMonth();

        $this->assertSame(1, $start->day());
    }

    public function testEndOfMonth(): void
    {
        $date = BusinessDate::create(2024, 1, 15, $this->utc);
        $end = $date->endOfMonth();

        $this->assertSame(31, $end->day());
    }

    public function testIsBefore(): void
    {
        $earlier = BusinessDate::create(2024, 1, 1, $this->utc);
        $later = BusinessDate::create(2024, 1, 15, $this->utc);

        $this->assertTrue($earlier->isBefore($later));
        $this->assertFalse($later->isBefore($earlier));
    }

    public function testIsAfter(): void
    {
        $earlier = BusinessDate::create(2024, 1, 1, $this->utc);
        $later = BusinessDate::create(2024, 1, 15, $this->utc);

        $this->assertTrue($later->isAfter($earlier));
        $this->assertFalse($earlier->isAfter($later));
    }

    public function testSerializeAndDeserialize(): void
    {
        $original = BusinessDate::create(2024, 1, 15, $this->utc);
        $serialized = $original->serialize();
        $deserialized = BusinessDate::deserialize($serialized, $this->utc);

        $this->assertTrue($original->equals($deserialized));
    }

    public function testEquals(): void
    {
        $d1 = BusinessDate::create(2024, 1, 15, $this->utc);
        $d2 = BusinessDate::create(2024, 1, 15, $this->utc);
        $d3 = BusinessDate::create(2024, 1, 16, $this->utc);

        $this->assertTrue($d1->equals($d2));
        $this->assertFalse($d1->equals($d3));
    }
}
