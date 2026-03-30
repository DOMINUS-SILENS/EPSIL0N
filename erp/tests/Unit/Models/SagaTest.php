<?php

namespace Tests\Unit\Models;

use App\Models\Entreprise;
use App\Models\Saga;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Saga Model Test Suite
 * 
 * Tests the Saga model including:
 * - Saga creation with context
 * - State management (pending, completed, failed)
 * - Step tracking
 * - Context storage
 * 
 * @package Tests\Unit\Models
 * @covers \App\Models\Saga
 */
class SagaTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_create_saga(): void
    {
        $saga = Saga::create([
            'saga_type' => 'order_processing',
            'saga_id' => 'order-123',
            'state' => 'pending',
            'context' => ['order_id' => 123, 'customer_id' => 456],
            'current_step' => 0,
            'version' => 1,
        ]);

        $this->assertDatabaseHas('sagas', [
            'saga_type' => 'order_processing',
            'saga_id' => 'order-123',
        ]);
    }

    #[Test]
    public function it_manages_saga_states(): void
    {
        $saga = Saga::create([
            'saga_type' => 'payment',
            'saga_id' => 'pay-456',
            'state' => 'pending',
            'context' => [],
            'current_step' => 0,
        ]);

        $this->assertEquals('pending', $saga->state);

        $saga->update(['state' => 'completed']);
        $this->assertEquals('completed', $saga->fresh()->state);

        $saga->update(['state' => 'failed']);
        $this->assertEquals('failed', $saga->fresh()->state);
    }

    #[Test]
    public function it_tracks_current_step(): void
    {
        $saga = Saga::create([
            'saga_type' => 'workflow',
            'saga_id' => 'wf-789',
            'state' => 'pending',
            'context' => [],
            'current_step' => 0,
        ]);

        $saga->update(['current_step' => 1]);
        $this->assertEquals(1, $saga->fresh()->current_step);

        $saga->update(['current_step' => 2]);
        $this->assertEquals(2, $saga->fresh()->current_step);
    }

    #[Test]
    public function it_stores_context_as_json(): void
    {
        $context = [
            'order_id' => 123,
            'customer_id' => 456,
            'items' => [
                ['product_id' => 1, 'qty' => 2],
                ['product_id' => 2, 'qty' => 1],
            ],
        ];

        $saga = Saga::create([
            'saga_type' => 'complex_order',
            'saga_id' => 'ord-789',
            'state' => 'pending',
            'context' => $context,
            'current_step' => 0,
        ]);

        $this->assertIsArray($saga->context);
        $this->assertEquals(123, $saga->context['order_id']);
        $this->assertCount(2, $saga->context['items']);
    }

    #[Test]
    public function it_uses_correct_table_name(): void
    {
        $saga = new Saga();
        $this->assertEquals('sagas', $saga->getTable());
    }

    #[Test]
    public function it_has_fillable_fields(): void
    {
        $saga = new Saga();
        $fillable = $saga->getFillable();

        $expected = [
            'saga_type',
            'saga_id',
            'state',
            'context',
            'current_step',
            'version',
        ];

        foreach ($expected as $field) {
            $this->assertContains($field, $fillable);
        }
    }
}
