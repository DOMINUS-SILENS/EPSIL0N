<?php

declare(strict_types=1);

namespace Spiral\Kernel\Domain\Sync;

use Spiral\Kernel\Domain\Identity\DeviceId;
use Spiral\Kernel\Domain\Shared\ValueObject\ValueObject;

/**
 * Vector clock for distributed event ordering in offline mobile sync.
 *
 * SyncVersion implements a vector clock where each device maintains its own
 * logical counter. This enables:
 * - Causal ordering detection (happensBefore)
 * - Concurrent edit detection (isConcurrent)
 * - Conflict-free merging (merge)
 *
 * Vector Clock Rules:
 * 1. Each device increments only its own counter
 * 2. On sync, clocks are merged by taking max of each component
 * 3. Comparison is done component-wise
 *
 * Example:
 *   Device A: {A: 2, B: 1} - Device A has seen 2 of its own events and 1 from B
 *   Device B: {A: 1, B: 3} - Device B has seen 1 from A and 3 of its own
 *
 * @package Spiral\Kernel\Domain\Sync
 */
final class SyncVersion extends ValueObject
{
    /**
     * @param array<non-empty-string, int<0, max>> $clocks Map of deviceId -> counter
     */
    private function __construct(
        private readonly array $clocks
    ) {
    }

    /**
     * Create an empty vector clock (for new devices).
     */
    public static function empty(): self
    {
        return new self([]);
    }

    /**
     * Create a vector clock from an array representation.
     *
     * @param array<non-empty-string, int<0, max>> $clocks
     */
    public static function fromArray(array $clocks): self
    {
        // Validate all values are non-negative integers
        foreach ($clocks as $deviceId => $counter) {
            if (!is_int($counter) || $counter < 0) {
                throw new \InvalidArgumentException(
                    sprintf('Invalid clock value for device "%s": must be non-negative integer', $deviceId)
                );
            }
        }
        return new self($clocks);
    }

    /**
     * Create a vector clock initialized for a specific device.
     */
    public static function forDevice(DeviceId $deviceId, int $initialCounter = 0): self
    {
        if ($initialCounter < 0) {
            throw new \InvalidArgumentException('Initial counter must be non-negative');
        }
        return new self([$deviceId->toString() => $initialCounter]);
    }

    /**
     * Increment the counter for a device (called when device creates an event).
     */
    public function increment(DeviceId $deviceId): self
    {
        $deviceKey = $deviceId->toString();
        $newClocks = $this->clocks;
        $newClocks[$deviceKey] = ($this->clocks[$deviceKey] ?? 0) + 1;
        return new self($newClocks);
    }

    /**
     * Merge two vector clocks (take maximum of each component).
     * Called during sync to reconcile distributed state.
     */
    public function merge(self $other): self
    {
        $merged = $this->clocks;

        foreach ($other->clocks as $deviceId => $counter) {
            $merged[$deviceId] = max($merged[$deviceId] ?? 0, $counter);
        }

        return new self($merged);
    }

    /**
     * Check if this version happens-before another version.
     *
     * A happens-before B if:
     * - All components of A are <= corresponding components of B
     * - At least one component of A is < corresponding component of B
     *
     * This indicates A is causally before B (no conflict).
     */
    public function happensBefore(self $other): bool
    {
        $allLessOrEqual = true;
        $atLeastOneLess = false;

        $allDevices = array_unique(array_merge(
            array_keys($this->clocks),
            array_keys($other->clocks)
        ));

        foreach ($allDevices as $deviceId) {
            $thisCounter = $this->clocks[$deviceId] ?? 0;
            $otherCounter = $other->clocks[$deviceId] ?? 0;

            if ($thisCounter > $otherCounter) {
                $allLessOrEqual = false;
                break;
            }
            if ($thisCounter < $otherCounter) {
                $atLeastOneLess = true;
            }
        }

        return $allLessOrEqual && $atLeastOneLess;
    }

    /**
     * Check if two versions are concurrent (potential conflict).
     *
     * A and B are concurrent if:
     * - Neither A happens-before B
     * - Neither B happens-before A
     *
     * This indicates concurrent edits that may conflict.
     */
    public function isConcurrent(self $other): bool
    {
        return !$this->happensBefore($other) && !$other->happensBefore($this);
    }

    /**
     * Check if this version is identical to another.
     */
    public function isEqual(self $other): bool
    {
        $allDevices = array_unique(array_merge(
            array_keys($this->clocks),
            array_keys($other->clocks)
        ));

        foreach ($allDevices as $deviceId) {
            $thisCounter = $this->clocks[$deviceId] ?? 0;
            $otherCounter = $other->clocks[$deviceId] ?? 0;

            if ($thisCounter !== $otherCounter) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the counter for a specific device.
     */
    public function getCounter(DeviceId $deviceId): int
    {
        return $this->clocks[$deviceId->toString()] ?? 0;
    }

    /**
     * Get all device IDs in this clock.
     *
     * @return list<non-empty-string>
     */
    public function getDeviceIds(): array
    {
        return array_keys($this->clocks);
    }

    /**
     * Convert to array for serialization.
     *
     * @return array<non-empty-string, int<0, max>>
     */
    public function toArray(): array
    {
        return $this->clocks;
    }

    /**
     * Create a compact string representation for logging/hashing.
     * Format: "deviceA:1,deviceB:3"
     */
    public function toString(): string
    {
        $parts = [];
        foreach ($this->clocks as $deviceId => $counter) {
            $parts[] = sprintf('%s:%d', $deviceId, $counter);
        }
        sort($parts);
        return implode(',', $parts);
    }

    protected function valueEquals(ValueObject $other): bool
    {
        \assert($other instanceof self);
        return $this->isEqual($other);
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
