<?php

declare(strict_types=1);

namespace Spiral\Kernel\Tests\Unit\Domain\Shared\ValueObject\Temporal;

use PHPUnit\Framework\TestCase;
use Spiral\Kernel\Domain\Shared\ValueObject\Temporal\TimezoneId;

final class TimezoneIdTest extends TestCase
{
    public function testFromStringValidTimezone(): void
    {
        $tz = TimezoneId::fromString('America/New_York');

        $this->assertSame('America/New_York', $tz->timezone());
    }

    public function testFromStringRejectsInvalidTimezone(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        TimezoneId::fromString('Invalid/Timezone');
    }

    public function testFromStringRejectsEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        TimezoneId::fromString('');
    }

    public function testUtc(): void
    {
        $tz = TimezoneId::utc();

        $this->assertSame('UTC', $tz->timezone());
    }

    public function testFromOffset(): void
    {
        $tz = TimezoneId::fromOffset('+05:30');

        $this->assertNotNull($tz);
    }

    public function testFromOffsetRejectsInvalidFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        TimezoneId::fromOffset('invalid');
    }

    public function testNow(): void
    {
        $tz = TimezoneId::fromString('America/New_York');
        $now = $tz->now();

        $this->assertInstanceOf(\DateTimeImmutable::class, $now);
    }

    public function testGetOffset(): void
    {
        $tz = TimezoneId::utc();
        $timestamp = new \DateTimeImmutable('2024-01-01T00:00:00');

        $offset = $tz->getOffset($timestamp);

        $this->assertSame(0, $offset);
    }

    public function testObservesDst(): void
    {
        $tz = TimezoneId::fromString('America/New_York');
        $timestamp = new \DateTimeImmutable('2024-07-01');

        $result = $tz->observesDst($timestamp);

        $this->assertIsBool($result);
    }

    public function testSerializeAndDeserialize(): void
    {
        $original = TimezoneId::fromString('Europe/London');
        $serialized = $original->serialize();
        $deserialized = TimezoneId::deserialize($serialized);

        $this->assertTrue($original->equals($deserialized));
    }

    public function testEquals(): void
    {
        $tz1 = TimezoneId::fromString('America/New_York');
        $tz2 = TimezoneId::fromString('America/New_York');
        $tz3 = TimezoneId::fromString('Europe/London');

        $this->assertTrue($tz1->equals($tz2));
        $this->assertFalse($tz1->equals($tz3));
    }
}
