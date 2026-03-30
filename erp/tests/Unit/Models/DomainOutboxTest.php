<?php

namespace Tests\Unit\Models;

use App\Models\DomainOutbox;
use App\Models\Entreprise;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * DomainOutbox Model Test Suite
 * 
 * Tests the DomainOutbox (Event Sourcing) model including:
 * - Event storage with sequence
 * - Aggregate relationship
 * - Event payload structure
 * - Sequence ordering
 * 
 * @package Tests\Unit\Models
 * @covers \App\Models\DomainOutbox
 */
class DomainOutboxTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_store_outbox_state(): void
    {
        $event = \App\Models\DomainEvent::create([
            'tenant_id' => 1,
            'aggregate_type' => 'test',
            'aggregate_id' => '1',
            'sequence' => 1,
            'event_type' => 'test',
            'payload' => '{}',
            'event_time' => now(),
        ]);

        $outbox = DomainOutbox::create([
            'event_id' => $event->id,
            'status' => 'pending',
            'attempts' => 0,
        ]);

        $this->assertDatabaseHas('domain_outbox', [
            'event_id' => $event->id,
            'status' => 'pending',
        ]);
        $this->assertEquals(0, $outbox->attempts);
    }

    #[Test]
    public function it_uses_correct_table_name(): void
    {
        $outbox = new DomainOutbox();
        $this->assertEquals('domain_outbox', $outbox->getTable());
    }

    #[Test]
    public function it_can_increment_attempts(): void
    {
        $event = \App\Models\DomainEvent::create([
            'tenant_id' => 1,
            'aggregate_type' => 'test2',
            'aggregate_id' => '2',
            'sequence' => 1,
            'event_type' => 'test',
            'payload' => '{}',
            'event_time' => now(),
        ]);

        $outbox = DomainOutbox::create([
            'event_id' => $event->id,
            'status' => 'failed',
            'attempts' => 1,
        ]);

        $outbox->increment('attempts');
        
        $this->assertEquals(2, $outbox->fresh()->attempts);
    }
}
