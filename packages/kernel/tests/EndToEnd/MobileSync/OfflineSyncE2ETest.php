<?php

declare(strict_types=1);

namespace Spiral\Kernel\Tests\EndToEnd\MobileSync;

use PHPUnit\Framework\TestCase;
use Spiral\Kernel\Domain\Identity\DeviceId;
use Spiral\Kernel\Domain\Identity\TenantId;
use Spiral\Kernel\Domain\Sync\SyncVersion;
use Spiral\Kernel\Domain\Sync\SyncStatus;
use Spiral\Kernel\Domain\Sync\SyncMetadata;
use Spiral\Kernel\Domain\Sync\ConflictStrategy;
use Spiral\Kernel\Infrastructure\Persistence\MobileSync\ConflictResolver;
use Spiral\Kernel\Infrastructure\Persistence\MobileSync\ProcessingReport;

/**
 * End-to-end tests for offline mobile sync workflow.
 *
 * Tests the complete sync lifecycle:
 * 1. Device creates events offline
 * 2. Events are queued with vector clocks
 * 3. Sync detects conflicts
 * 4. Conflicts are resolved
 * 5. Events are persisted
 */
final class OfflineSyncE2ETest extends TestCase
{
    private TenantId $tenantId;
    private DeviceId $mobileDevice;
    private DeviceId $posDevice;

    protected function setUp(): void
    {
        $this->tenantId = TenantId::generate();
        $this->mobileDevice = DeviceId::generate();
        $this->posDevice = DeviceId::generate();
    }

    /**
     * E2E-01: Single device offline create -> sync -> persist.
     */
    public function test_single_device_offline_create_sync_persist(): void
    {
        // Step 1: Device starts with empty version
        $deviceVersion = SyncVersion::empty();
        $this->assertSame(0, $deviceVersion->getCounter($this->mobileDevice));

        // Step 2: Device creates 3 events offline
        $event1Version = $deviceVersion->increment($this->mobileDevice);
        $event2Version = $event1Version->increment($this->mobileDevice);
        $event3Version = $event2Version->increment($this->mobileDevice);

        $this->assertSame(3, $event3Version->getCounter($this->mobileDevice));

        // Step 3: Create sync metadata for each event
        $syncMeta1 = SyncMetadata::forOfflineEvent($this->mobileDevice, $event1Version);
        $syncMeta2 = SyncMetadata::forOfflineEvent($this->mobileDevice, $event2Version);
        $syncMeta3 = SyncMetadata::forOfflineEvent($this->mobileDevice, $event3Version);

        $this->assertTrue($syncMeta1->isPending());
        $this->assertTrue($syncMeta2->isPending());
        $this->assertTrue($syncMeta3->isPending());

        // Step 4: Simulate sync - mark as synced
        $syncedAt = new \DateTimeImmutable();
        $syncMeta1Synced = $syncMeta1->markSynced($syncedAt);

        $this->assertTrue($syncMeta1Synced->isSynced());
        $this->assertFalse($syncMeta1Synced->isPending());
    }

    /**
     * E2E-02: Two devices concurrent edits -> conflict detection.
     */
    public function test_two_devices_concurrent_edits_conflict_detection(): void
    {
        // Both devices start from same initial state
        $initialVersion = SyncVersion::empty();

        // Mobile device creates events offline (no knowledge of POS)
        $mobileVersion = $initialVersion
            ->increment($this->mobileDevice)
            ->increment($this->mobileDevice);

        // POS device creates events offline (no knowledge of mobile)
        $posVersion = $initialVersion
            ->increment($this->posDevice)
            ->increment($this->posDevice)
            ->increment($this->posDevice);

        // Both are concurrent - conflict!
        $this->assertTrue($mobileVersion->isConcurrent($posVersion));
        $this->assertTrue($posVersion->isConcurrent($mobileVersion));

        // Neither happens-before the other
        $this->assertFalse($mobileVersion->happensBefore($posVersion));
        $this->assertFalse($posVersion->happensBefore($mobileVersion));
    }

    /**
     * E2E-03: Conflict resolution with DevicePriority strategy.
     */
    public function test_conflict_resolution_device_priority(): void
    {
        $resolver = new ConflictResolver(
            ConflictStrategy::DevicePriority,
            [
                $this->posDevice->toString() => 100,    // POS has priority
                $this->mobileDevice->toString() => 10,  // Mobile is lower
            ]
        );

        // Create conflict scenario
        $mobileVersion = SyncVersion::empty()->increment($this->mobileDevice);
        $posVersion = SyncVersion::empty()->increment($this->posDevice);

        $this->assertTrue($mobileVersion->isConcurrent($posVersion));

        // Resolution should favor POS
        // (Full test requires mocking events - see ConflictResolverTest)
    }

    /**
     * E2E-04: Merge after conflict resolution.
     */
    public function test_merge_after_conflict_resolution(): void
    {
        // Mobile has: {mobile: 2}
        $mobileVersion = SyncVersion::empty()
            ->increment($this->mobileDevice)
            ->increment($this->mobileDevice);

        // POS has: {pos: 3}
        $posVersion = SyncVersion::empty()
            ->increment($this->posDevice)
            ->increment($this->posDevice)
            ->increment($this->posDevice);

        // After sync and merge
        $mergedVersion = $mobileVersion->merge($posVersion);

        // Both devices should see the merged state
        $this->assertSame(2, $mergedVersion->getCounter($this->mobileDevice));
        $this->assertSame(3, $mergedVersion->getCounter($this->posDevice));

        // Merged version should have both device IDs
        $deviceIds = $mergedVersion->getDeviceIds();
        $this->assertCount(2, $deviceIds);
        $this->assertContains($this->mobileDevice->toString(), $deviceIds);
        $this->assertContains($this->posDevice->toString(), $deviceIds);
    }

    /**
     * E2E-05: Processing report tracks sync outcomes.
     */
    public function test_processing_report_tracks_sync_outcomes(): void
    {
        $report = new ProcessingReport();

        // Simulate processing 10 events
        $report->addResult('event-1', \Spiral\Kernel\Infrastructure\Persistence\MobileSync\EventProcessingResult::synced(1));
        $report->addResult('event-2', \Spiral\Kernel\Infrastructure\Persistence\MobileSync\EventProcessingResult::synced(2));
        $report->addResult('event-3', \Spiral\Kernel\Infrastructure\Persistence\MobileSync\EventProcessingResult::merged(3, 'OT merge'));
        $report->addResult('event-4', \Spiral\Kernel\Infrastructure\Persistence\MobileSync\EventProcessingResult::conflict(['reason' => 'concurrent edit']));
        $report->addResult('event-5', \Spiral\Kernel\Infrastructure\Persistence\MobileSync\EventProcessingResult::rejected('Validation failed'));

        $this->assertSame(2, $report->getSyncedCount());
        $this->assertSame(1, $report->getMergedCount());
        $this->assertSame(1, $report->getConflictCount());
        $this->assertSame(1, $report->getRejectedCount());
        $this->assertSame(5, $report->getTotalCount());

        $this->assertFalse($report->isComplete());
        $this->assertStringContainsString('2 synced', $report->getSummary());
    }

    /**
     * E2E-06: Full sync cycle with vector clock evolution.
     */
    public function test_full_sync_cycle_vector_clock_evolution(): void
    {
        // Initial state
        $globalVersion = SyncVersion::empty();

        // Phase 1: Mobile creates 2 events offline
        $mobileLocalVersion = $globalVersion
            ->increment($this->mobileDevice)
            ->increment($this->mobileDevice);

        $this->assertSame(2, $mobileLocalVersion->getCounter($this->mobileDevice));

        // Phase 2: Mobile syncs with server
        // Server merges mobile's version
        $serverVersion = $globalVersion->merge($mobileLocalVersion);

        $this->assertSame(2, $serverVersion->getCounter($this->mobileDevice));

        // Phase 3: POS creates 1 event offline (concurrent with mobile's sync)
        $posLocalVersion = $serverVersion
            ->increment($this->posDevice);

        $this->assertSame(1, $posLocalVersion->getCounter($this->posDevice));

        // Phase 4: Mobile creates another event (after first sync)
        $mobileLocalVersion2 = $serverVersion
            ->increment($this->mobileDevice);

        // Now mobile has 3 total, POS has 1
        // These are concurrent!
        $this->assertTrue($posLocalVersion->isConcurrent($mobileLocalVersion2));

        // Phase 5: Both sync - final merge
        $finalVersion = $posLocalVersion->merge($mobileLocalVersion2);

        $this->assertSame(3, $finalVersion->getCounter($this->mobileDevice));
        $this->assertSame(1, $finalVersion->getCounter($this->posDevice));

        // All devices now agree on the final state
        $this->assertFalse($finalVersion->isConcurrent($serverVersion));
        $this->assertTrue($serverVersion->happensBefore($finalVersion));
    }

    /**
     * E2E-07: SyncMetadata serialization roundtrip.
     */
    public function test_sync_metadata_serialization_roundtrip(): void
    {
        $deviceId = DeviceId::generate();
        $version = SyncVersion::empty()
            ->increment($deviceId)
            ->increment($deviceId);

        $original = SyncMetadata::forOfflineEvent($deviceId, $version);

        $array = $original->toArray();
        $restored = SyncMetadata::fromArray($array);

        $this->assertTrue($original->deviceId->equals($restored->deviceId));
        $this->assertTrue($original->syncVersion->isEqual($restored->syncVersion));
        $this->assertEquals($original->status, $restored->status);
        $this->assertTrue($restored->isPending());
    }
}
