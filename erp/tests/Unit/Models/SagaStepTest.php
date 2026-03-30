<?php

namespace Tests\Unit\Models;

use App\Models\Saga;
use App\Models\SagaStep;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * SagaStep Model Test Suite
 * 
 * Tests the SagaStep model including:
 * - Step creation with command/compensation
 * - Status management (pending, executed, compensated)
 * - Execution timestamps
 * - Ordering by step_index
 * 
 * @package Tests\Unit\Models
 * @covers \App\Models\SagaStep
 */
class SagaStepTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_create_saga_step(): void
    {
        // Create parent saga first
        $saga = Saga::create([
            'saga_type' => 'test',
            'saga_id' => 'test-1',
            'state' => 'pending',
            'context' => [],
            'current_step' => 0,
        ]);

        $step = SagaStep::create([
            'saga_id' => $saga->id,
            'step_index' => 0,
            'command_type' => 'reserve_stock',
            'command_payload' => ['product_id' => 1, 'qty' => 10],
            'compensation_type' => 'release_stock',
            'compensation_payload' => ['product_id' => 1, 'qty' => 10],
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('saga_steps', [
            'saga_id' => $saga->id,
            'step_index' => 0,
            'command_type' => 'reserve_stock',
        ]);
    }

    #[Test]
    public function it_manages_step_status(): void
    {
        $saga = Saga::create([
            'saga_type' => 'test',
            'saga_id' => 'test-2',
            'state' => 'pending',
            'context' => [],
            'current_step' => 0,
        ]);

        $step = SagaStep::create([
            'saga_id' => $saga->id,
            'step_index' => 0,
            'command_type' => 'process_payment',
            'command_payload' => [],
            'status' => 'pending',
        ]);

        $this->assertEquals('pending', $step->status);

        // Mark as executed
        $step->update([
            'status' => 'executed',
            'executed_at' => now(),
        ]);

        $this->assertEquals('executed', $step->fresh()->status);
        $this->assertNotNull($step->fresh()->executed_at);
    }

    #[Test]
    public function it_can_be_compensated(): void
    {
        $saga = Saga::create([
            'saga_type' => 'test',
            'saga_id' => 'test-3',
            'state' => 'pending',
            'context' => [],
            'current_step' => 0,
        ]);

        $step = SagaStep::create([
            'saga_id' => $saga->id,
            'step_index' => 0,
            'command_type' => 'ship_order',
            'command_payload' => [],
            'compensation_type' => 'cancel_shipment',
            'status' => 'executed',
            'executed_at' => now(),
        ]);

        // Compensate
        $step->update(['status' => 'compensated']);

        $this->assertEquals('compensated', $step->fresh()->status);
    }

    #[Test]
    public function it_stores_payload_as_json(): void
    {
        $saga = Saga::create([
            'saga_type' => 'test',
            'saga_id' => 'test-4',
            'state' => 'pending',
            'context' => [],
            'current_step' => 0,
        ]);

        $payload = [
            'order_id' => 123,
            'items' => [
                ['product_id' => 1, 'quantity' => 2],
                ['product_id' => 2, 'quantity' => 1],
            ],
            'total' => 150.00,
        ];

        $step = SagaStep::create([
            'saga_id' => $saga->id,
            'step_index' => 0,
            'command_type' => 'create_order',
            'command_payload' => $payload,
            'status' => 'pending',
        ]);

        $this->assertIsArray($step->command_payload);
        $this->assertEquals(123, $step->command_payload['order_id']);
        $this->assertCount(2, $step->command_payload['items']);
    }

    #[Test]
    public function it_orders_by_step_index(): void
    {
        $saga = Saga::create([
            'saga_type' => 'test',
            'saga_id' => 'test-5',
            'state' => 'pending',
            'context' => [],
            'current_step' => 0,
        ]);

        // Create steps out of order
        SagaStep::create([
            'saga_id' => $saga->id,
            'step_index' => 2,
            'command_type' => 'step_3',
            'command_payload' => [],
            'status' => 'pending',
        ]);

        SagaStep::create([
            'saga_id' => $saga->id,
            'step_index' => 0,
            'command_type' => 'step_1',
            'command_payload' => [],
            'status' => 'pending',
        ]);

        SagaStep::create([
            'saga_id' => $saga->id,
            'step_index' => 1,
            'command_type' => 'step_2',
            'command_payload' => [],
            'status' => 'pending',
        ]);

        $steps = SagaStep::where('saga_id', $saga->id)
            ->orderBy('step_index')
            ->get();

        $this->assertEquals('step_1', $steps[0]->command_type);
        $this->assertEquals('step_2', $steps[1]->command_type);
        $this->assertEquals('step_3', $steps[2]->command_type);
    }

    #[Test]
    public function it_uses_correct_table_name(): void
    {
        $step = new SagaStep();
        $this->assertEquals('saga_steps', $step->getTable());
    }

    #[Test]
    public function it_has_fillable_fields(): void
    {
        $step = new SagaStep();
        $fillable = $step->getFillable();

        $expected = [
            'saga_id',
            'step_index',
            'command_type',
            'command_payload',
            'compensation_type',
            'compensation_payload',
            'status',
        ];

        foreach ($expected as $field) {
            $this->assertContains($field, $fillable);
        }
    }
}
