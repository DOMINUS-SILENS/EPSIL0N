<?php

declare(strict_types=1);

namespace Spiral\Kernel\Tests\Unit\Domain\Shared\ValueObject\Temporal;

use PHPUnit\Framework\TestCase;
use Spiral\Kernel\Domain\Shared\ValueObject\Temporal\BusinessPeriod;
use Spiral\Kernel\Domain\Shared\ValueObject\Temporal\BusinessDate;
use Spiral\Kernel\Domain\Shared\ValueObject\Temporal\TimezoneId;

final class BusinessPeriodTest extends TestCase
{
    private TimezoneId $utc;

    protected function setUp(): void
    {
        $this->utc = TimezoneId::utc();
    }

    public function testInclusive(): void
    {
        $start = BusinessDate::create(2024, 1, 1, $this->utc);
        $end = BusinessDate::create(2024, 1, 31, $this->utc);
        $period = BusinessPeriod::inclusive($start, $end);

        $this->assertTrue($period->isStartInclusive());
        $this->assertTrue($period->isEndInclusive());
    }

    public function testExclusive(): void
    {
        $start = BusinessDate::create(2024, 1, 1, $this->utc);
        $end = BusinessDate::create(2024, 1, 31, $this->utc);
        $period = BusinessPeriod::exclusive($start, $end);

        $this->assertFalse($period->isStartInclusive());
        $this->assertFalse($period->isEndInclusive());
    }

    public function testHalfOpen(): void
    {
        $start = BusinessDate::create(2024, 1, 1, $this->utc);
        $end = BusinessDate::create(2024, 1, 31, $this->utc);
        $period = BusinessPeriod::halfOpen($start, $end);

        $this->assertTrue($period->isStartInclusive());
        $this->assertFalse($period->isEndInclusive());
    }

    public function testRejectsStartAfterEnd(): void
    {
        $start = BusinessDate::create(2024, 1, 31, $this->utc);
        $end = BusinessDate::create(2024, 1, 1, $this->utc);

        $this->expectException(\InvalidArgumentException::class);
        BusinessPeriod::inclusive($start, $end);
    }

    public function testForDay(): void
    {
        $day = BusinessDate::create(2024, 1, 15, $this->utc);
        $period = BusinessPeriod::forDay($day);

        $this->assertTrue($day->equals($period->start()));
        $this->assertTrue($day->equals($period->end()));
    }

    public function testForMonth(): void
    {
        $period = BusinessPeriod::forMonth(2024, 1, $this->utc);

        $this->assertSame(1, $period->start()->month());
        $this->assertSame(1, $period->start()->day());
        $this->assertSame(1, $period->end()->month());
        $this->assertSame(31, $period->end()->day());
    }

    public function testForQuarter(): void
    {
        $period = BusinessPeriod::forQuarter(2024, 1, $this->utc);

        $this->assertSame(1, $period->start()->month());
        $this->assertSame(3, $period->end()->month());
    }

    public function testForYear(): void
    {
        $period = BusinessPeriod::forYear(2024, $this->utc);

        $this->assertSame(2024, $period->start()->year());
        $this->assertSame(2024, $period->end()->year());
    }

    public function testDays(): void
    {
        $start = BusinessDate::create(2024, 1, 1, $this->utc);
        $end = BusinessDate::create(2024, 1, 10, $this->utc);
        $period = BusinessPeriod::inclusive($start, $end);

        $this->assertSame(10, $period->days());
    }

    public function testContains(): void
    {
        $period = BusinessPeriod::inclusive(
            BusinessDate::create(2024, 1, 1, $this->utc),
            BusinessDate::create(2024, 1, 31, $this->utc)
        );

        $inside = BusinessDate::create(2024, 1, 15, $this->utc);
        $outside = BusinessDate::create(2024, 2, 1, $this->utc);

        $this->assertTrue($period->contains($inside));
        $this->assertFalse($period->contains($outside));
    }

    public function testOverlaps(): void
    {
        $p1 = BusinessPeriod::inclusive(
            BusinessDate::create(2024, 1, 1, $this->utc),
            BusinessDate::create(2024, 1, 15, $this->utc)
        );
        $p2 = BusinessPeriod::inclusive(
            BusinessDate::create(2024, 1, 10, $this->utc),
            BusinessDate::create(2024, 1, 31, $this->utc)
        );
        $p3 = BusinessPeriod::inclusive(
            BusinessDate::create(2024, 2, 1, $this->utc),
            BusinessDate::create(2024, 2, 28, $this->utc)
        );

        $this->assertTrue($p1->overlaps($p2));
        $this->assertFalse($p1->overlaps($p3));
    }

    public function testTouches(): void
    {
        $p1 = BusinessPeriod::inclusive(
            BusinessDate::create(2024, 1, 1, $this->utc),
            BusinessDate::create(2024, 1, 15, $this->utc)
        );
        $p2 = BusinessPeriod::inclusive(
            BusinessDate::create(2024, 1, 16, $this->utc),
            BusinessDate::create(2024, 1, 31, $this->utc)
        );

        $this->assertTrue($p1->touches($p2));
    }

    public function testIntersection(): void
    {
        $p1 = BusinessPeriod::inclusive(
            BusinessDate::create(2024, 1, 1, $this->utc),
            BusinessDate::create(2024, 1, 15, $this->utc)
        );
        $p2 = BusinessPeriod::inclusive(
            BusinessDate::create(2024, 1, 10, $this->utc),
            BusinessDate::create(2024, 1, 31, $this->utc)
        );

        $intersection = $p1->intersection($p2);

        $this->assertNotNull($intersection);
        $this->assertTrue($intersection->start()->equals(BusinessDate::create(2024, 1, 10, $this->utc)));
        $this->assertTrue($intersection->end()->equals(BusinessDate::create(2024, 1, 15, $this->utc)));
    }

    public function testUnion(): void
    {
        $p1 = BusinessPeriod::inclusive(
            BusinessDate::create(2024, 1, 1, $this->utc),
            BusinessDate::create(2024, 1, 10, $this->utc)
        );
        $p2 = BusinessPeriod::inclusive(
            BusinessDate::create(2024, 1, 10, $this->utc),
            BusinessDate::create(2024, 1, 20, $this->utc)
        );

        $union = $p1->union($p2);

        $this->assertNotNull($union);
        $this->assertTrue($union->start()->equals(BusinessDate::create(2024, 1, 1, $this->utc)));
        $this->assertTrue($union->end()->equals(BusinessDate::create(2024, 1, 20, $this->utc)));
    }

    public function testShift(): void
    {
        $period = BusinessPeriod::inclusive(
            BusinessDate::create(2024, 1, 1, $this->utc),
            BusinessDate::create(2024, 1, 10, $this->utc)
        );

        $shifted = $period->shift(5);

        $this->assertTrue($shifted->start()->equals(BusinessDate::create(2024, 1, 6, $this->utc)));
        $this->assertTrue($shifted->end()->equals(BusinessDate::create(2024, 1, 15, $this->utc)));
    }

    public function testSerializeAndDeserialize(): void
    {
        $original = BusinessPeriod::inclusive(
            BusinessDate::create(2024, 1, 1, $this->utc),
            BusinessDate::create(2024, 1, 31, $this->utc)
        );
        $serialized = $original->serialize();
        $deserialized = BusinessPeriod::deserialize($serialized, $this->utc);

        $this->assertTrue($original->equals($deserialized));
    }

    public function testEquals(): void
    {
        $p1 = BusinessPeriod::inclusive(
            BusinessDate::create(2024, 1, 1, $this->utc),
            BusinessDate::create(2024, 1, 31, $this->utc)
        );
        $p2 = BusinessPeriod::inclusive(
            BusinessDate::create(2024, 1, 1, $this->utc),
            BusinessDate::create(2024, 1, 31, $this->utc)
        );
        $p3 = BusinessPeriod::inclusive(
            BusinessDate::create(2024, 2, 1, $this->utc),
            BusinessDate::create(2024, 2, 28, $this->utc)
        );

        $this->assertTrue($p1->equals($p2));
        $this->assertFalse($p1->equals($p3));
    }
}
