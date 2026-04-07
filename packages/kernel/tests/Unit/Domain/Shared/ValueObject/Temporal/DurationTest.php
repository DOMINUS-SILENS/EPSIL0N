<?php

declare(strict_types=1);

namespace Spiral\Kernel\Tests\Unit\Domain\Shared\ValueObject\Temporal;

use PHPUnit\Framework\TestCase;
use Spiral\Kernel\Domain\Shared\ValueObject\Temporal\Duration;

final class DurationTest extends TestCase
{
    public function testFromSeconds(): void
    {
        $duration = Duration::fromSeconds(60);

        $this->assertSame(60, $duration->seconds());
        $this->assertSame(0, $duration->nanoseconds());
    }

    public function testFromMilliseconds(): void
    {
        $duration = Duration::fromMilliseconds(1500);

        $this->assertSame(1, $duration->seconds());
        $this->assertSame(500_000_000, $duration->nanoseconds());
    }

    public function testFromMicroseconds(): void
    {
        $duration = Duration::fromMicroseconds(1_500_000);

        $this->assertSame(1, $duration->seconds());
        $this->assertSame(500_000_000, $duration->nanoseconds());
    }

    public function testFromNanoseconds(): void
    {
        $duration = Duration::fromNanoseconds(1_500_000_000);

        $this->assertSame(1, $duration->seconds());
        $this->assertSame(500_000_000, $duration->nanoseconds());
    }

    public function testFromStringParsesHoursMinutesSeconds(): void
    {
        $duration = Duration::fromString('1h 30m 15s');

        $this->assertSame(5415, $duration->seconds());
    }

    public function testFromStringParsesMilliseconds(): void
    {
        $duration = Duration::fromString('500ms');

        $this->assertSame(0, $duration->seconds());
        $this->assertSame(500_000_000, $duration->nanoseconds());
    }

    public function testToMicroseconds(): void
    {
        $duration = Duration::fromSeconds(2);

        $this->assertSame(2_000_000, $duration->toMicroseconds());
    }

    public function testToMilliseconds(): void
    {
        $duration = Duration::fromMilliseconds(2500);

        $this->assertSame(2500, $duration->toMilliseconds());
    }

    public function testAdd(): void
    {
        $d1 = Duration::fromSeconds(60);
        $d2 = Duration::fromSeconds(30);

        $result = $d1->add($d2);

        $this->assertSame(90, $result->seconds());
    }

    public function testSubtract(): void
    {
        $d1 = Duration::fromSeconds(60);
        $d2 = Duration::fromSeconds(30);

        $result = $d1->subtract($d2);

        $this->assertSame(30, $result->seconds());
    }

    public function testSubtractReturnsZeroForNegative(): void
    {
        $d1 = Duration::fromSeconds(30);
        $d2 = Duration::fromSeconds(60);

        $result = $d1->subtract($d2);

        $this->assertTrue($result->isZero());
    }

    public function testMultiply(): void
    {
        $duration = Duration::fromSeconds(60);

        $result = $duration->multiply(2);

        $this->assertSame(120, $result->seconds());
    }

    public function testIsZero(): void
    {
        $zero = Duration::fromSeconds(0);
        $nonZero = Duration::fromSeconds(1);

        $this->assertTrue($zero->isZero());
        $this->assertFalse($nonZero->isZero());
    }

    public function testIsNegative(): void
    {
        $negative = Duration::fromNanoseconds(-1_000_000_000);
        $positive = Duration::fromSeconds(1);

        $this->assertTrue($negative->isNegative());
        $this->assertFalse($positive->isNegative());
    }

    public function testSerializeAndDeserialize(): void
    {
        $original = Duration::fromSeconds(90);
        $serialized = $original->serialize();
        $deserialized = Duration::deserialize($serialized);

        $this->assertTrue($original->equals($deserialized));
    }

    public function testEquals(): void
    {
        $d1 = Duration::fromSeconds(60);
        $d2 = Duration::fromSeconds(60);
        $d3 = Duration::fromSeconds(30);

        $this->assertTrue($d1->equals($d2));
        $this->assertFalse($d1->equals($d3));
    }
}
