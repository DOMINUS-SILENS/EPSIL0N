<?php

namespace Tests\Unit\Models;

use App\Models\ProjectionSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * ProjectionSnapshot Model Test Suite
 * 
 * Tests the ProjectionSnapshot model including:
 * - Snapshot storage
 * - JSON state persistence
 * - Last event tracking
 * - Projector name association
 * 
 * @package Tests\Unit\Models
 * @covers \App\Models\ProjectionSnapshot
 */
class ProjectionSnapshotTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_create_projection_snapshot(): void
    {
        $snapshot = ProjectionSnapshot::create([
            'id' => 1,
            'projector_name' => 'CustomerBalanceProjector',
            'aggregate_id' => 123,
            'snapshot' => ['balance' => 1000.00, 'last_transaction' => '2026-01-01'],
            'last_event_id' => 456,
        ]);

        $this->assertDatabaseHas('projection_snapshots', [
            'projector_name' => 'CustomerBalanceProjector',
            'aggregate_id' => 123,
            'last_event_id' => 456,
        ]);
    }

    #[Test]
    public function it_stores_snapshot_as_json(): void
    {
        $state = [
            'balance' => 1500.50,
            'transactions' => [
                ['id' => 1, 'amount' => 100],
                ['id' => 2, 'amount' => -50],
            ],
            'version' => 5,
        ];

        $snapshot = ProjectionSnapshot::create([
            'id' => 1,
            'projector_name' => 'AccountProjector',
            'aggregate_id' => 1,
            'snapshot' => $state,
            'last_event_id' => 100,
        ]);

        $this->assertIsArray($snapshot->snapshot);
        $this->assertEquals(1500.50, $snapshot->snapshot['balance']);
        $this->assertCount(2, $snapshot->snapshot['transactions']);
    }

    #[Test]
    public function it_tracks_last_event_id(): void
    {
        $snapshot = ProjectionSnapshot::create([
            'id' => 1,
            'projector_name' => 'OrderProjector',
            'aggregate_id' => 1,
            'snapshot' => ['orders' => []],
            'last_event_id' => 999,
        ]);

        $this->assertEquals(999, $snapshot->last_event_id);

        // Update after new events
        $snapshot->update(['last_event_id' => 1005]);
        $this->assertEquals(1005, $snapshot->fresh()->last_event_id);
    }

    #[Test]
    public function it_finds_by_projector_and_aggregate(): void
    {
        ProjectionSnapshot::create([
            'id' => 1,
            'projector_name' => 'CustomerProjector',
            'aggregate_id' => 1,
            'snapshot' => [],
            'last_event_id' => 1,
        ]);

        ProjectionSnapshot::create([
            'id' => 2,
            'projector_name' => 'CustomerProjector',
            'aggregate_id' => 2,
            'snapshot' => [],
            'last_event_id' => 2,
        ]);

        $found = ProjectionSnapshot::where('projector_name', 'CustomerProjector')
            ->where('aggregate_id', 1)
            ->first();

        $this->assertNotNull($found);
        $this->assertEquals(1, $found->aggregate_id);
    }

    #[Test]
    public function it_uses_correct_table_name(): void
    {
        $snapshot = new ProjectionSnapshot();
        $this->assertEquals('projection_snapshots', $snapshot->getTable());
    }

    #[Test]
    public function it_uses_incrementing_key(): void
    {
        $snapshot = new ProjectionSnapshot();
        $this->assertTrue($snapshot->incrementing);
    }
}
