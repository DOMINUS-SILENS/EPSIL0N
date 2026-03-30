<?php

namespace Tests\Unit;

use App\Aggregates\TaxeAggregate;
use App\Models\EventStore;
use App\Models\DomainOutbox;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AggregateReconstitutionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_reconstitutes_aggregate_from_event_store(): void
    {
        $uuid = 'taxe-123';
        
        // 1. Persist an initial state
        $aggregate = TaxeAggregate::retrieve($uuid);
        $aggregate->create(['name' => 'VAT', 'rate' => 20]);
        $aggregate->persist();

        // 2. Verify it's in the event_store
        $this->assertDatabaseHas('event_store', [
            'aggregate_id' => $uuid,
            'event_type' => 'TaxeCreated'
        ]);

        // 3. Retrieve and verify reconstitution
        $retrieved = TaxeAggregate::retrieve($uuid);
        $this->assertInstanceOf(TaxeAggregate::class, $retrieved);
        $this->assertEquals($uuid, $retrieved->uuid());
        
        // 4. Update and persist again
        $retrieved->update(['rate' => 21]);
        $retrieved->persist();

        // 5. Retrieve again to verify both events were replayed
        $final = TaxeAggregate::retrieve($uuid);
        $this->assertCount(2, DB::table('event_store')->where('aggregate_id', $uuid)->get());
    }

    #[Test]
    public function it_handles_ghost_aggregates_gracefully(): void
    {
        $uuid = 'non-existent-uuid';
        
        $aggregate = TaxeAggregate::retrieve($uuid);
        
        $this->assertInstanceOf(TaxeAggregate::class, $aggregate);
        $this->assertEquals($uuid, $aggregate->uuid());
        // Verify no events were loaded
        $this->assertDatabaseMissing('event_store', ['aggregate_id' => $uuid]);
    }

    #[Test]
    public function it_maintains_merkle_integrity_during_persistence(): void
    {
        $uuid = 'integrity-test';
        $aggregate = TaxeAggregate::retrieve($uuid);
        
        $aggregate->create(['step' => 1])->persist();
        $aggregate->update(['step' => 2])->persist();

        $events = DB::table('event_store')
            ->where('aggregate_id', $uuid)
            ->orderBy('local_sequence')
            ->get();

        $this->assertCount(2, $events);
        
        // The second event's previous_hash should match the first event's merkle_root
        $this->assertEquals($events[0]->merkle_root, $events[1]->previous_hash);
        $this->assertNotEquals('0', $events[0]->merkle_root);
    }
}
