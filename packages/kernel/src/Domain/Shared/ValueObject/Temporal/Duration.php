<?php

declare(strict_types=1);

namespace Spiral\Kernel\Domain\Shared\ValueObject\Temporal;

use Spiral\Kernel\Domain\Shared\ValueObject\ValueObject;

/**
 * Immutable value object representing a duration of time.
 *
 * Durations represent a span of time without a fixed start/end (unlike Period).
 * They are used for arithmetic operations on Timestamps.
 *
 * @package Spiral\Kernel\Domain\Shared\ValueObject\Temporal
 */
final class Duration extends ValueObject
{
    private function __construct(
        private readonly int $seconds,
        private readonly int $nanoseconds
    ) {
    }

    /**
     * Create a duration from seconds.
     */
    public static function fromSeconds(int $seconds): self
    {
        return new self($seconds, 0);
    }

    /**
     * Create a duration from milliseconds.
     */
    public static function fromMilliseconds(int $milliseconds): self
    {
        $seconds = (int) floor($milliseconds / 1000);
        $remainingNanoseconds = ($milliseconds % 1000) * 1_000_000;

        return new self($seconds, $remainingNanoseconds);
    }

    /**
     * Create a duration from microseconds.
     */
    public static function fromMicroseconds(int $microseconds): self
    {
        $seconds = (int) floor($microseconds / 1_000_000);
        $remainingNanoseconds = ($microseconds % 1_000_000) * 1000;

        return new self($seconds, $remainingNanoseconds);
    }

    /**
     * Create a duration from nanoseconds.
     */
    public static function fromNanoseconds(int $nanoseconds): self
    {
        $seconds = (int) floor($nanoseconds / 1_000_000_000);
        $remainingNanoseconds = $nanoseconds % 1_000_000_000;

        return new self($seconds, $remainingNanoseconds);
    }

    /**
     * Create a duration from a string like "1h 30m 15s".
     */
    public static function fromString(string $value): self
    {
        $value = trim($value);
        $seconds = 0;
        $nanoseconds = 0;

        if (preg_match('/(\d+)\s*h/', $value, $m)) {
            $seconds += (int) $m[1] * 3600;
        }
        if (preg_match('/(\d+)\s*m(?!s)/', $value, $m)) {
            $seconds += (int) $m[1] * 60;
        }
        if (preg_match('/(\d+)\s*s/', $value, $m)) {
            $seconds += (int) $m[1];
        }
        if (preg_match('/(\d+)\s*ms/', $value, $m)) {
            $nanoseconds += (int) $m[1] * 1_000_000;
        }
        if (preg_match('/(\d+)\s*us/', $value, $m)) {
            $nanoseconds += (int) $m[1] * 1000;
        }
        if (preg_match('/(\d+)\s*ns/', $value, $m)) {
            $nanoseconds += (int) $m[1];
        }

        return new self($seconds, $nanoseconds);
    }

    /**
     * Get total seconds (rounded down).
     */
    public function seconds(): int
    {
        return $this->seconds;
    }

    /**
     * Get nanoseconds component (0-999999999).
     */
    public function nanoseconds(): int
    {
        return $this->nanoseconds;
    }

    /**
     * Get total microseconds.
     */
    public function toMicroseconds(): int
    {
        return $this->seconds * 1_000_000 + (int) floor($this->nanoseconds / 1000);
    }

    /**
     * Get total milliseconds.
     */
    public function toMilliseconds(): int
    {
        return $this->seconds * 1000 + (int) floor($this->nanoseconds / 1_000_000);
    }

    /**
     * Get total nanoseconds.
     */
    public function toNanoseconds(): int
    {
        return $this->seconds * 1_000_000_000 + $this->nanoseconds;
    }

    /**
     * Add another duration to this one.
     */
    public function add(self $other): self
    {
        $totalNanoseconds = $this->toNanoseconds() + $other->toNanoseconds();

        return self::fromNanoseconds($totalNanoseconds);
    }

    /**
     * Subtract another duration from this one.
     */
    public function subtract(self $other): self
    {
        $totalNanoseconds = $this->toNanoseconds() - $other->toNanoseconds();

        if ($totalNanoseconds < 0) {
            $totalNanoseconds = 0;
        }

        return self::fromNanoseconds($totalNanoseconds);
    }

    /**
     * Multiply duration by a factor.
     */
    public function multiply(int $factor): self
    {
        return self::fromNanoseconds($this->toNanoseconds() * $factor);
    }

    /**
     * Check if this duration is zero.
     */
    public function isZero(): bool
    {
        return $this->seconds === 0 && $this->nanoseconds === 0;
    }

    /**
     * Check if this duration is negative.
     */
    public function isNegative(): bool
    {
        return $this->seconds < 0;
    }

    public function serialize(): string
    {
        return \sprintf('%d.%09d', $this->seconds, $this->nanoseconds);
    }

    public static function deserialize(string $value): self
    {
        $parts = explode('.', $value, 2);
        $seconds = (int) $parts[0];
        $nanoseconds = isset($parts[1]) ? (int) str_pad($parts[1], 9, '0') : 0;

        return new self($seconds, $nanoseconds);
    }

    protected function valueEquals(ValueObject $other): bool
    {
        if (!$other instanceof self) {
            return false;
        }
        return $this->toNanoseconds() === $other->toNanoseconds();
    }

    public function __toString(): string
    {
        if ($this->nanoseconds === 0) {
            return \sprintf('%ds', $this->seconds);
        }

        $ms = (int) floor($this->nanoseconds / 1_000_000);
        if ($ms > 0) {
            return \sprintf('%ds %dms', $this->seconds, $ms);
        }

        $us = (int) floor($this->nanoseconds / 1000);
        if ($us > 0) {
            return \sprintf('%ds %dus', $this->seconds, $us);
        }

        return \sprintf('%ds %dns', $this->seconds, $this->nanoseconds);
    }
}
