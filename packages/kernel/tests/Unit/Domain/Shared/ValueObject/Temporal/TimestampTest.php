<?php

declare(strict_types=1);

namespace Spiral\Kernel\Tests\Unit\Domain\Shared\ValueObject\Temporal;

use PHPUnit\Framework\TestCase;
use Spiral\Kernel\Domain\Shared\ValueObject\Temporal\Timestamp;
use Spiral\Kernel\Domain\Shared\ValueObject\Temporal\Duration;

final class TimestampTest extends TestCase
{
    public function testNowReturnsUtcTimestamp(): void
    {
        $timestamp = Timestamp::now();

        $this->assertInstanceOf(Timestamp::class, $timestamp);
    }

    public function testFromUnixTimestamp(): void
    {
        $timestamp = Timestamp::fromUnixTimestamp(1704067200);

        $this->assertSame(1704067200, $timestamp->toUnixTimestamp());
    }

    public function testFromStringParsesIso8601(): void
    {
        $timestamp = Timestamp::fromString('2024-01-01T00:00:00+00:00');

        $this->assertSame(2024, $timestamp->year());
        $this->assertSame(1, $timestamp->month());
        $this->assertSame(1, $timestamp->day());
    }

    public function testFromStringRejectsEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Timestamp::fromString('');
    }

    public function testFromStringRejectsInvalidFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Timestamp::fromString('not-a-timestamp');
    }

    public function testToMicroseconds(): void
    {
        $timestamp = Timestamp::fromUnixTimestamp(1704067200);
        $microseconds = $timestamp->toMicroseconds();

        $this->assertGreaterThan(0, $microseconds);
        $this->assertIsInt($microseconds);
    }

    public function testIsBefore(): void
    {
        $earlier = Timestamp::fromUnixTimestamp(1704067200);
        $later = Timestamp::fromUnixTimestamp(1704153600);

        $this->assertTrue($earlier->isBefore($later));
        $this->assertFalse($later->isBefore($earlier));
    }

    public function testIsAfter(): void
    {
        $earlier = Timestamp::fromUnixTimestamp(1704067200);
        $later = Timestamp::fromUnixTimestamp(1704153600);

        $this->assertTrue($later->isAfter($earlier));
        $this->assertFalse($earlier->isAfter($later));
    }

    public function testAddDuration(): void
    {
        $timestamp = Timestamp::fromUnixTimestamp(1704067200);
        $duration = Duration::fromSeconds(3600);

        $result = $timestamp->add($duration);

        $this->assertSame(1704070800, $result->toUnixTimestamp());
    }

    public function testSubtractDuration(): void
    {
        $timestamp = Timestamp::fromUnixTimestamp(1704070800);
        $duration = Duration::fromSeconds(3600);

        $result = $timestamp->subtract($duration);

        $this->assertSame(1704067200, $result->toUnixTimestamp());
    }

    public function testSerializeAndDeserialize(): void
    {
        $original = Timestamp::fromString('2024-01-01T12:30:45.123456+00:00');
        $serialized = $original->serialize();
        $deserialized = Timestamp::deserialize($serialized);

        $this->assertTrue($original->equals($deserialized));
    }

    public function testToIso8601(): void
    {
        $timestamp = Timestamp::fromString('2024-01-01T12:30:45+00:00');
        $iso8601 = $timestamp->toIso8601();

        $this->assertStringContainsString('2024-01-01', $iso8601);
        $this->assertStringContainsString('+00:00', $iso8601);
    }

    public function testEquals(): void
    {
        $ts1 = Timestamp::fromUnixTimestamp(1704067200);
        $ts2 = Timestamp::fromUnixTimestamp(1704067200);
        $ts3 = Timestamp::fromUnixTimestamp(1704153600);

        $this->assertTrue($ts1->equals($ts2));
        $this->assertFalse($ts1->equals($ts3));
    }

    public function testIsBeforeOrEqual(): void
    {
        $ts1 = Timestamp::fromUnixTimestamp(1704067200);
        $ts2 = Timestamp::fromUnixTimestamp(1704067200);
        $ts3 = Timestamp::fromUnixTimestamp(1704153600);

        $this->assertTrue($ts1->isBeforeOrEqual($ts2));
        $this->assertTrue($ts1->isBeforeOrEqual($ts3));
        $this->assertFalse($ts3->isBeforeOrEqual($ts1));
    }

    public function testIsAfterOrEqual(): void
    {
        $ts1 = Timestamp::fromUnixTimestamp(1704067200);
        $ts2 = Timestamp::fromUnixTimestamp(1704067200);
        $ts3 = Timestamp::fromUnixTimestamp(1704153600);

        $this->assertTrue($ts1->isAfterOrEqual($ts2));
        $this->assertTrue($ts3->isAfterOrEqual($ts1));
        $this->assertFalse($ts1->isAfterOrEqual($ts3));
    }

    public function testDiff(): void
    {
        $ts1 = Timestamp::fromUnixTimestamp(1704067200);
        $ts2 = Timestamp::fromUnixTimestamp(1704153600);

        $diff = $ts1->diff($ts2);

        $this->assertSame(86400, $diff->seconds());
    }
}
