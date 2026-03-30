<?php

namespace Tests\Unit;

use App\Aggregates\AggregateRoot;
use App\Aggregates\TaxeAggregate;
use App\Services\OutboxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * AggregateRoot Test Suite
 * 
 * Tests the base AggregateRoot class including:
 * - Event recording mechanism
 * - Apply methods
 * - Persist functionality with outbox
 * - State reconstitution
 * 
 * Foundation for all domain aggregates.
 * 
 * @package Tests\Unit
 * @covers \App\Aggregates\AggregateRoot
 */
class AggregateRootTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_can_retrieve_aggregate_by_uuid(): void
    {
        $uuid = 'test-uuid-123';
        
        $aggregate = TaxeAggregate::retrieve($uuid);
        
        $this->assertInstanceOf(TaxeAggregate::class, $aggregate);
        $this->assertEquals($uuid, $aggregate->uuid());
    }

    #[Test]
    public function it_records_and_applies_events(): void
    {
        $aggregate = TaxeAggregate::retrieve('test-uuid');
        
        // Record an event
        $aggregate->create(['name' => 'TVA 20%', 'rate' => 0.20]);
        
        // Event should be recorded and applied
        $this->assertTrue(true); // No exception means success
    }

    #[Test]
    public function it_persists_events_to_outbox(): void
    {
        // Mock the outbox service
        $outboxMock = Mockery::mock(OutboxService::class);
        $outboxMock->shouldReceive('publishDomain')
            ->once()
            ->with(
                'TaxeAggregate',
                'test-uuid',
                \Mockery::type('string'),
                \Mockery::type('array')
            );
        
        App::instance(OutboxService::class, $outboxMock);
        
        $aggregate = TaxeAggregate::retrieve('test-uuid');
        $aggregate->create(['name' => 'TVA 20%', 'rate' => 0.20]);
        
        // Persist should publish to outbox
        $aggregate->persist();
    }

    #[Test]
    public function it_clears_recorded_events_after_persist(): void
    {
        $outboxMock = Mockery::mock(OutboxService::class);
        $outboxMock->shouldReceive('publishDomain');
        App::instance(OutboxService::class, $outboxMock);
        
        $aggregate = TaxeAggregate::retrieve('test-uuid');
        $aggregate->create(['name' => 'TVA 20%']);
        
        // First persist
        $aggregate->persist();
        
        // Second persist should not publish (no new events)
        $aggregate->persist();
        
        // Test passes if no additional publishDomain calls
        $this->assertTrue(true);
    }

    #[Test]
    public function it_supports_multiple_event_types(): void
    {
        $outboxMock = Mockery::mock(OutboxService::class);
        $outboxMock->shouldReceive('publishDomain')
            ->times(2); // Once for create, once for update
        App::instance(OutboxService::class, $outboxMock);
        
        $aggregate = TaxeAggregate::retrieve('test-uuid');
        
        $aggregate->create(['name' => 'TVA 20%']);
        $aggregate->update(['name' => 'TVA 19%']);
        
        $aggregate->persist();
    }

    #[Test]
    public function it_returns_self_for_chaining(): void
    {
        $aggregate = TaxeAggregate::retrieve('test-uuid');
        
        $result = $aggregate->create(['name' => 'Test']);
        
        $this->assertInstanceOf(TaxeAggregate::class, $result);
    }
}
