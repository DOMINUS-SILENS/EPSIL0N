<?php

namespace Tests\Unit;

use App\Models\Saga;
use App\Models\SagaStep;
use App\Services\OutboxService;
use App\Services\SagaOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SagaOrchestratorTest extends TestCase
{
    use RefreshDatabase;

    protected OutboxService $outbox;

    protected SagaOrchestrator $orchestrator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->outbox = app(OutboxService::class);
        $this->orchestrator = new SagaOrchestrator($this->outbox);
    }

    #[Test]
    public function it_starts_saga_and_executes_first_step()
    {
        $steps = [
            [
                'command_type' => 'stock.reserve',
                'command_payload' => ['product_id' => 1, 'qty' => 10],
            ],
            [
                'command_type' => 'credit.reserve',
                'command_payload' => ['amount' => 100],
            ],
        ];

        $this->orchestrator->startSaga('order', 'ORD-123', $steps, ['customer_id' => 1]);

        $this->assertDatabaseHas('domain_events', [
            'aggregate_type' => 'Saga',
            'aggregate_id' => 'ORD-123',
            'event_type' => 'SagaStarted',
        ]);

        $this->assertDatabaseHas('domain_events', [
            'aggregate_type' => 'Saga',
            'aggregate_id' => 'ORD-123',
            'event_type' => 'stock.reserve', // Outbox uses command as event type for saga steps
        ]);
    }

    #[Test]
    public function it_completes_step_and_moves_to_next()
    {
        // Setup initial history
        $this->outbox->publishDomain('Saga', 'ORD-123', 'SagaStarted', [
            'saga_type' => 'order',
            'steps' => [
                [
                    'command_type' => 'stock.reserve',
                    'command_payload' => [],
                ],
                [
                    'command_type' => 'credit.reserve',
                    'command_payload' => [],
                ]
            ],
            'context' => []
        ]);

        $this->orchestrator->stepCompleted('ORD-123', 0, []);

        $this->assertDatabaseHas('domain_events', [
            'aggregate_type' => 'Saga',
            'aggregate_id' => 'ORD-123',
            'event_type' => 'SagaStepExecuted',
        ]);

        $this->assertDatabaseHas('domain_events', [
            'aggregate_type' => 'Saga',
            'aggregate_id' => 'ORD-123',
            'event_type' => 'credit.reserve',
        ]);
    }

    #[Test]
    public function it_compensates_on_step_failure()
    {
        $this->outbox->publishDomain('Saga', 'ORD-123', 'SagaStarted', [
            'saga_type' => 'order',
            'steps' => [
                [
                    'command_type' => 'stock.reserve',
                    'command_payload' => [],
                    'compensation_type' => 'stock.release',
                    'compensation_payload' => ['product_id' => 1],
                ],
                [
                    'command_type' => 'credit.reserve',
                    'command_payload' => [],
                    'max_retries' => 1,
                    'compensation_type' => 'credit.release',
                    'compensation_payload' => ['amount' => 100],
                ]
            ],
            'context' => []
        ]);

        $this->orchestrator->stepCompleted('ORD-123', 0, []);

        $this->orchestrator->stepFailed('ORD-123', 1, 'Insufficient credit');

        $this->assertDatabaseHas('domain_events', [
            'aggregate_type' => 'Saga',
            'aggregate_id' => 'ORD-123',
            'event_type' => 'SagaStepFailed',
        ]);

        // Expect compensation
        $this->assertDatabaseHas('domain_events', [
            'aggregate_type' => 'Saga',
            'aggregate_id' => 'ORD-123',
            'event_type' => 'stock.release',
        ]);
    }
}
