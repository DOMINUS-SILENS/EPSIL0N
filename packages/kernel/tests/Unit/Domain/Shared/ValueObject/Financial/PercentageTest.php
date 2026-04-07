<?php

declare(strict_types=1);

namespace Spiral\Kernel\Tests\Unit\Domain\Shared\ValueObject\Financial;

use PHPUnit\Framework\TestCase;
use Spiral\Kernel\Domain\Shared\ValueObject\Financial\Percentage;

final class PercentageTest extends TestCase
{
    public function testFromDecimal(): void
    {
        $percentage = Percentage::fromDecimal(0.15);

        $this->assertSame(0.15, $percentage->value());
    }

    public function testFromWholeNumber(): void
    {
        $percentage = Percentage::fromWholeNumber(15);

        $this->assertSame(0.15, $percentage->value());
    }

    public function testFromBasisPoints(): void
    {
        $percentage = Percentage::fromBasisPoints(1500);

        $this->assertSame(0.15, $percentage->value());
    }

    public function testZero(): void
    {
        $percentage = Percentage::zero();

        $this->assertTrue($percentage->isZero());
    }

    public function testHundredPercent(): void
    {
        $percentage = Percentage::hundredPercent();

        $this->assertTrue($percentage->isHundredPercent());
    }

    public function testFromStringWithPercentSign(): void
    {
        $percentage = Percentage::fromString('15%');

        $this->assertSame(0.15, $percentage->value());
    }

    public function testFromStringWithDecimal(): void
    {
        $percentage = Percentage::fromString('0.15');

        $this->assertSame(0.15, $percentage->value());
    }

    public function testFromStringRejectsInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Percentage::fromString('invalid');
    }

    public function testToWholeNumber(): void
    {
        $percentage = Percentage::fromDecimal(0.155);

        $this->assertSame(16, $percentage->toWholeNumber());
    }

    public function testToBasisPoints(): void
    {
        $percentage = Percentage::fromDecimal(0.15);

        $this->assertSame(1500, $percentage->toBasisPoints());
    }

    public function testIsZero(): void
    {
        $zero = Percentage::zero();
        $nonZero = Percentage::fromWholeNumber(10);

        $this->assertTrue($zero->isZero());
        $this->assertFalse($nonZero->isZero());
    }

    public function testIsHundredPercent(): void
    {
        $hundred = Percentage::hundredPercent();
        $notHundred = Percentage::fromWholeNumber(50);

        $this->assertTrue($hundred->isHundredPercent());
        $this->assertFalse($notHundred->isHundredPercent());
    }

    public function testIsGreaterThan(): void
    {
        $p1 = Percentage::fromWholeNumber(20);
        $p2 = Percentage::fromWholeNumber(10);

        $this->assertTrue($p1->isGreaterThan($p2));
        $this->assertFalse($p2->isGreaterThan($p1));
    }

    public function testIsLessThan(): void
    {
        $p1 = Percentage::fromWholeNumber(10);
        $p2 = Percentage::fromWholeNumber(20);

        $this->assertTrue($p1->isLessThan($p2));
        $this->assertFalse($p2->isLessThan($p1));
    }

    public function testAdd(): void
    {
        $p1 = Percentage::fromWholeNumber(10);
        $p2 = Percentage::fromWholeNumber(20);

        $result = $p1->add($p2);

        $this->assertSame(0.30, $result->value());
    }

    public function testSubtract(): void
    {
        $p1 = Percentage::fromWholeNumber(30);
        $p2 = Percentage::fromWholeNumber(10);

        $result = $p1->subtract($p2);

        $this->assertSame(0.20, $result->value());
    }

    public function testMultiply(): void
    {
        $percentage = Percentage::fromWholeNumber(10);

        $result = $percentage->multiply(2);

        $this->assertSame(0.20, $result->value());
    }

    public function testNegate(): void
    {
        $percentage = Percentage::fromWholeNumber(10);

        $result = $percentage->negate();

        $this->assertSame(-0.10, $result->value());
    }

    public function testAbsolute(): void
    {
        $percentage = Percentage::fromWholeNumber(10)->negate();

        $result = $percentage->absolute();

        $this->assertSame(0.10, $result->value());
    }

    public function testFormat(): void
    {
        $percentage = Percentage::fromDecimal(0.1234);

        $this->assertSame('12.34%', $percentage->format(2));
    }

    public function testSerializeAndDeserialize(): void
    {
        $original = Percentage::fromWholeNumber(15);
        $serialized = $original->serialize();
        $deserialized = Percentage::deserialize($serialized);

        $this->assertTrue($original->equals($deserialized));
    }

    public function testEquals(): void
    {
        $p1 = Percentage::fromWholeNumber(15);
        $p2 = Percentage::fromWholeNumber(15);
        $p3 = Percentage::fromWholeNumber(20);

        $this->assertTrue($p1->equals($p2));
        $this->assertFalse($p1->equals($p3));
    }
}
