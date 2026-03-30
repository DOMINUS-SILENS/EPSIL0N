<?php

namespace Tests\Feature;

use App\Aggregates\StockAggregate;
use App\Models\DomainEvent;
use App\Models\DomainOutbox;
use Illuminate\Support\Str;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class EventSourcingFlowTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test the full lifecycle from Command -> Aggregate -> Event -> Outbox -> Projector.
     */
    public function test_full_es_cqrs_lifecycle(): void
    {
        $uuid = (string) Str::uuid();
        $companyId = 1;

        // 1. Act: Execute Command on Aggregate
        $aggregate = StockAggregate::retrieve($uuid);
        $aggregate->receive(1, 101, $companyId, 100.0, 'Initial Stock')
                  ->persist();

        // 2. Assert: Domain Event persisted
        $this->assertDatabaseHas('domain_events', [
            'aggregate_id' => $uuid,
            'event_type' => 'StockReceived',
        ]);

        // 3. Assert: Outbox record created
        $this->assertDatabaseHas('domain_outbox', [
            'status' => 'pending',
        ]);

        // 4. Act: Verify Idempotency in reconstruction
        $reconstructed = StockAggregate::retrieve($uuid);
        // Using Reflection to check protected property $balance
        $reflection = new \ReflectionClass($reconstructed);
        $property = $reflection->getProperty('balance');
        $property->setAccessible(true);
        
        $this->assertEquals(100.0, $property->getValue($reconstructed));

        // 5. Act: Test causality (re-receive)
        $reconstructed->receive(1, 101, $companyId, 50.0, 'Additional Stock')
                      ->persist();
        
        $this->assertEquals(150.0, $property->getValue($reconstructed->persist()));

        // 6. Assert: Events count
        $this->assertEquals(2, DomainEvent::where('aggregate_id', $uuid)->count());
    }

    /**
     * Test high-throughput sequences (Redis interaction).
     */
    public function test_redis_sequence_generation(): void
    {
        $sequenceService = app(\App\Services\SequenceService::class);
        $type = 'TestAggregate';
        $id = 'test-123';

        $seq1 = $sequenceService->next(1, $type, $id);
        $seq2 = $sequenceService->next(1, $type, $id);

        $this->assertEquals($seq1 + 1, $seq2);
        
        // Assert SQL backfill
        $this->assertDatabaseHas('aggregate_sequences', [
            'aggregate_type' => $type,
            'aggregate_id' => $id,
            'seq' => $seq2
        ]);
    }
}
