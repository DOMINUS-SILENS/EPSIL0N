<?php

declare(strict_types=1);

namespace Spiral\Kernel\Tests\Unit\Domain\Shared\ValueObject\Financial;

use PHPUnit\Framework\TestCase;
use Spiral\Kernel\Domain\Shared\ValueObject\Financial\Currency;

final class CurrencyTest extends TestCase
{
    public function testFromStringValidCurrency(): void
    {
        $currency = Currency::fromString('USD');

        $this->assertSame('USD', $currency->code());
    }

    public function testFromStringCaseInsensitive(): void
    {
        $currency = Currency::fromString('usd');

        $this->assertSame('USD', $currency->code());
    }

    public function testFromStringRejectsInvalidCurrency(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Currency::fromString('XXX');
    }

    public function testFromStringRejectsEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Currency::fromString('');
    }

    public function testName(): void
    {
        $currency = Currency::fromString('USD');

        $this->assertSame('US Dollar', $currency->name());
    }

    public function testSymbol(): void
    {
        $currency = Currency::fromString('USD');

        $this->assertSame('$', $currency->symbol());
    }

    public function testDecimals(): void
    {
        $usd = Currency::fromString('USD');
        $jpy = Currency::fromString('JPY');

        $this->assertSame(2, $usd->decimals());
        $this->assertSame(0, $jpy->decimals());
    }

    public function testSmallestUnit(): void
    {
        $usd = Currency::fromString('USD');
        $jpy = Currency::fromString('JPY');

        $this->assertSame(100, $usd->smallestUnit());
        $this->assertSame(1, $jpy->smallestUnit());
    }

    public function testFormat(): void
    {
        $currency = Currency::fromString('USD');

        $this->assertSame('$1,234.56', $currency->format(123456));
    }

    public function testIsZeroDecimals(): void
    {
        $usd = Currency::fromString('USD');
        $jpy = Currency::fromString('JPY');

        $this->assertFalse($usd->isZeroDecimals());
        $this->assertTrue($jpy->isZeroDecimals());
    }

    public function testEquals(): void
    {
        $c1 = Currency::fromString('USD');
        $c2 = Currency::fromString('USD');
        $c3 = Currency::fromString('EUR');

        $this->assertTrue($c1->equals($c2));
        $this->assertFalse($c1->equals($c3));
    }

    public function testSerializeAndDeserialize(): void
    {
        $original = Currency::fromString('EUR');
        $serialized = $original->serialize();
        $deserialized = Currency::deserialize($serialized);

        $this->assertTrue($original->equals($deserialized));
    }
}
