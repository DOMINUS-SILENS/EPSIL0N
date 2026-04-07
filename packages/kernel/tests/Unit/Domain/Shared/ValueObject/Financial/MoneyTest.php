<?php

declare(strict_types=1);

namespace Spiral\Kernel\Tests\Unit\Domain\Shared\ValueObject\Financial;

use PHPUnit\Framework\TestCase;
use Spiral\Kernel\Domain\Shared\ValueObject\Financial\Money;
use Spiral\Kernel\Domain\Shared\ValueObject\Financial\Currency;
use Spiral\Kernel\Domain\Shared\ValueObject\Financial\Percentage;

final class MoneyTest extends TestCase
{
    private Currency $usd;
    private Currency $eur;

    protected function setUp(): void
    {
        $this->usd = Currency::fromString('USD');
        $this->eur = Currency::fromString('EUR');
    }

    public function testFromMinorUnits(): void
    {
        $money = Money::fromMinorUnits(1234, $this->usd);

        $this->assertSame(1234, $money->minorUnits());
    }

    public function testFromMajorUnits(): void
    {
        $money = Money::fromMajorUnits(12.34, $this->usd);

        $this->assertSame(1234, $money->minorUnits());
    }

    public function testZero(): void
    {
        $money = Money::zero($this->usd);

        $this->assertTrue($money->isZero());
    }

    public function testFromString(): void
    {
        $money = Money::fromString('100.50 USD');

        $this->assertSame('USD', $money->currency()->code());
        $this->assertSame(10050, $money->minorUnits());
    }

    public function testFromStringRejectsInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Money::fromString('invalid');
    }

    public function testIsZero(): void
    {
        $zero = Money::zero($this->usd);
        $nonZero = Money::fromMinorUnits(100, $this->usd);

        $this->assertTrue($zero->isZero());
        $this->assertFalse($nonZero->isZero());
    }

    public function testIsPositive(): void
    {
        $positive = Money::fromMinorUnits(100, $this->usd);
        $negative = Money::fromMinorUnits(-100, $this->usd);

        $this->assertTrue($positive->isPositive());
        $this->assertFalse($negative->isPositive());
    }

    public function testIsNegative(): void
    {
        $negative = Money::fromMinorUnits(-100, $this->usd);
        $positive = Money::fromMinorUnits(100, $this->usd);

        $this->assertTrue($negative->isNegative());
        $this->assertFalse($positive->isNegative());
    }

    public function testAddRejectsDifferentCurrency(): void
    {
        $money1 = Money::fromMinorUnits(100, $this->usd);
        $money2 = Money::fromMinorUnits(100, $this->eur);

        $this->expectException(\InvalidArgumentException::class);
        $money1->add($money2);
    }

    public function testAdd(): void
    {
        $m1 = Money::fromMinorUnits(100, $this->usd);
        $m2 = Money::fromMinorUnits(200, $this->usd);

        $result = $m1->add($m2);

        $this->assertSame(300, $result->minorUnits());
    }

    public function testSubtractRejectsDifferentCurrency(): void
    {
        $money1 = Money::fromMinorUnits(100, $this->usd);
        $money2 = Money::fromMinorUnits(100, $this->eur);

        $this->expectException(\InvalidArgumentException::class);
        $money1->subtract($money2);
    }

    public function testSubtract(): void
    {
        $m1 = Money::fromMinorUnits(300, $this->usd);
        $m2 = Money::fromMinorUnits(100, $this->usd);

        $result = $m1->subtract($m2);

        $this->assertSame(200, $result->minorUnits());
    }

    public function testMultiply(): void
    {
        $money = Money::fromMinorUnits(100, $this->usd);

        $result = $money->multiply(1.5);

        $this->assertSame(150, $result->minorUnits());
    }

    public function testDivide(): void
    {
        $money = Money::fromMinorUnits(100, $this->usd);

        $result = $money->divide(2);

        $this->assertSame(50, $result->minorUnits());
    }

    public function testDivideByZeroThrows(): void
    {
        $money = Money::fromMinorUnits(100, $this->usd);

        $this->expectException(\InvalidArgumentException::class);
        $money->divide(0);
    }

    public function testNegate(): void
    {
        $money = Money::fromMinorUnits(100, $this->usd);

        $result = $money->negate();

        $this->assertSame(-100, $result->minorUnits());
    }

    public function testAbsolute(): void
    {
        $money = Money::fromMinorUnits(-100, $this->usd);

        $result = $money->absolute();

        $this->assertSame(100, $result->minorUnits());
    }

    public function testMin(): void
    {
        $m1 = Money::fromMinorUnits(100, $this->usd);
        $m2 = Money::fromMinorUnits(200, $this->usd);

        $result = $m1->min($m2);

        $this->assertSame(100, $result->minorUnits());
    }

    public function testMax(): void
    {
        $m1 = Money::fromMinorUnits(100, $this->usd);
        $m2 = Money::fromMinorUnits(200, $this->usd);

        $result = $m1->max($m2);

        $this->assertSame(200, $result->minorUnits());
    }

    public function testAllocate(): void
    {
        $money = Money::fromMinorUnits(100, $this->usd);

        $result = $money->allocate([1, 1, 1]);

        $this->assertCount(3, $result);
        $this->assertSame(100, \array_sum(\array_map(fn($m) => $m->minorUnits(), $result)));
    }

    public function testAllocateEqually(): void
    {
        $money = Money::fromMinorUnits(100, $this->usd);

        $result = $money->allocateEqually(3);

        $this->assertCount(3, $result);
    }

    public function testExtractPercentage(): void
    {
        $money = Money::fromMinorUnits(10000, $this->usd);
        $percentage = Percentage::fromWholeNumber(10);

        $result = $money->extractPercentage($percentage);

        $this->assertSame(1000, $result->minorUnits());
    }

    public function testFormat(): void
    {
        $money = Money::fromMinorUnits(123456, $this->usd);

        $formatted = $money->format();

        $this->assertStringContainsString('$', $formatted);
        $this->assertStringContainsString('1,234.56', $formatted);
    }

    public function testSerializeAndDeserialize(): void
    {
        $original = Money::fromMinorUnits(1234, $this->usd);
        $serialized = $original->serialize();
        $deserialized = Money::deserialize($serialized);

        $this->assertTrue($original->equals($deserialized));
    }

    public function testEquals(): void
    {
        $m1 = Money::fromMinorUnits(100, $this->usd);
        $m2 = Money::fromMinorUnits(100, $this->usd);
        $m3 = Money::fromMinorUnits(200, $this->usd);

        $this->assertTrue($m1->equals($m2));
        $this->assertFalse($m1->equals($m3));
    }

    public function testCompareTo(): void
    {
        $m1 = Money::fromMinorUnits(100, $this->usd);
        $m2 = Money::fromMinorUnits(200, $this->usd);

        $this->assertLessThan(0, $m1->compareTo($m2));
        $this->assertGreaterThan(0, $m2->compareTo($m1));
        $this->assertSame(0, $m1->compareTo($m1));
    }
}
