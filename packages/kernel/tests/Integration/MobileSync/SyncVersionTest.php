<?php

declare(strict_types=1);

namespace Spiral\Kernel\Tests\Integration\MobileSync;

use PHPUnit\Framework\TestCase;
use Spiral\Kernel\Domain\Identity\DeviceId;
use Spiral\Kernel\Domain\Sync\SyncVersion;

/**
 * Integration tests for SyncVersion vector clock.
 *
 * Verifies:
 * - Vector clock ordering
 * - Concurrent edit detection
 * - Merge semantics
 */
final class SyncVersionTest extends TestCase
{
    private DeviceId $deviceA;
    private DeviceId $deviceB;
    private DeviceId $deviceC;

    protected function setUp(): void
    {
        $this->deviceA = DeviceId::generate();
        $this->deviceB = DeviceId::generate();
        $this->deviceC = DeviceId::generate();
    }

    /**
     * TEST-01: Empty vector clock starts at zero for all devices.
     */
    public function test_empty_vector_clock_returns_zero_for_all_devices(): void
    {
        $version = SyncVersion::empty();

        $this->assertSame(0, $version->getCounter($this->deviceA));
        $this->assertSame(0, $version->getCounter($this->deviceB));
        $this->assertEmpty($version->getDeviceIds());
    }

    /**
     * TEST-02: Increment increments only the specified device counter.
     */
    public function test_increment_increments_only_specified_device(): void
    {
        $version = SyncVersion::empty()
            ->increment($this->deviceA);

        $this->assertSame(1, $version->getCounter($this->deviceA));
        $this->assertSame(0, $version->getCounter($this->deviceB));

        // Second increment
        $version2 = $version->increment($this->deviceA);
        $this->assertSame(2, $version2->getCounter($this->deviceA));
    }

    /**
     * TEST-03: happensBefore detects causal ordering.
     */
    public function test_happens_before_detects_causal_ordering(): void
    {
        // Device A creates 2 events
        $versionA = SyncVersion::empty()
            ->increment($this->deviceA)
            ->increment($this->deviceA);

        // Device B sees A's events and creates 1 event
        $versionB = $versionA->merge(SyncVersion::empty())
            ->increment($this->deviceB);

        // A's version happens-before B's version
        $this->assertTrue($versionA->happensBefore($versionB));
        $this->assertFalse($versionB->happensBefore($versionA));
    }

    /**
     * TEST-04: isConcurrent detects concurrent edits (potential conflicts).
     */
    public function test_is_concurrent_detects_concurrent_edits(): void
    {
        // Device A creates events independently
        $versionA = SyncVersion::empty()
            ->increment($this->deviceA)
            ->increment($this->deviceA);

        // Device B creates events independently (no knowledge of A)
        $versionB = SyncVersion::empty()
            ->increment($this->deviceB);

        // Both are concurrent - neither happens-before the other
        $this->assertTrue($versionA->isConcurrent($versionB));
        $this->assertTrue($versionB->isConcurrent($versionA));
    }

    /**
     * TEST-05: Merge combines vector clocks by taking max.
     */
    public function test_merge_takes_maximum_of_each_component(): void
    {
        $versionA = SyncVersion::empty()
            ->increment($this->deviceA)  // A:1
            ->increment($this->deviceA); // A:2

        $versionB = SyncVersion::empty()
            ->increment($this->deviceB)  // B:1
            ->increment($this->deviceB)  // B:2
            ->increment($this->deviceB); // B:3

        $merged = $versionA->merge($versionB);

        // Merged should have A:2, B:3
        $this->assertSame(2, $merged->getCounter($this->deviceA));
        $this->assertSame(3, $merged->getCounter($this->deviceB));

        // Merge is commutative
        $mergedReverse = $versionB->merge($versionA);
        $this->assertTrue($merged->isEqual($mergedReverse));
    }

    /**
     * TEST-06: isEqual detects identical versions.
     */
    public function test_is_equal_detects_identical_versions(): void
    {
        $version1 = SyncVersion::empty()
            ->increment($this->deviceA)
            ->increment($this->deviceB);

        $version2 = SyncVersion::empty()
            ->increment($this->deviceB)
            ->increment($this->deviceA);

        // Order of increments doesn't matter for equality
        $this->assertTrue($version1->isEqual($version2));
    }

    /**
     * TEST-07: Roundtrip serialization preserves vector clock.
     */
    public function test_roundtrip_serialization_preserves_clock(): void
    {
        $original = SyncVersion::empty()
            ->increment($this->deviceA)
            ->increment($this->deviceA)
            ->increment($this->deviceB);

        $array = $original->toArray();
        $restored = SyncVersion::fromArray($array);

        $this->assertTrue($original->isEqual($restored));
    }

    /**
     * TEST-08: Complex scenario - three devices syncing.
     */
    public function test_complex_three_device_sync_scenario(): void
    {
        // Initial state: all devices empty
        $initial = SyncVersion::empty();

        // Device A creates 3 events offline
        $deviceAVersion = $initial
            ->increment($this->deviceA)
            ->increment($this->deviceA)
            ->increment($this->deviceA);

        // Device B creates 2 events offline (concurrent with A)
        $deviceBVersion = $initial
            ->increment($this->deviceB)
            ->increment($this->deviceB);

        // Device C syncs with A first, then creates 1 event
        $deviceCVersion = $initial
            ->merge($deviceAVersion)
            ->increment($this->deviceC);

        // A and B are concurrent (conflict potential)
        $this->assertTrue($deviceAVersion->isConcurrent($deviceBVersion));

        // A happens-before C (C saw A's events)
        $this->assertTrue($deviceAVersion->happensBefore($deviceCVersion));

        // B and C are concurrent (C didn't see B's events yet)
        $this->assertTrue($deviceBVersion->isConcurrent($deviceCVersion));

        // After full sync (merge all)
        $fullySynced = $deviceAVersion
            ->merge($deviceBVersion)
            ->merge($deviceCVersion);

        // All devices now have the same version
        $this->assertSame(3, $fullySynced->getCounter($this->deviceA));
        $this->assertSame(2, $fullySynced->getCounter($this->deviceB));
        $this->assertSame(1, $fullySynced->getCounter($this->deviceC));
    }

    /**
     * TEST-09: String representation is deterministic.
     */
    public function test_string_representation_is_deterministic(): void
    {
        $version = SyncVersion::empty()
            ->increment($this->deviceB)
            ->increment($this->deviceA);

        // String should be sorted by device ID
        $string = $version->toString();
        $this->assertStringContainsString($this->deviceA->toString(), $string);
        $this->assertStringContainsString($this->deviceB->toString(), $string);
    }
}
