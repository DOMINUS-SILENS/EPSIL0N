<?php

namespace App\Services;

use App\Models\DomainEvent;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SagaOrchestrator
{
    protected OutboxService $outbox;

    public function __construct(OutboxService $outbox)
    {
        $this->outbox = $outbox;
    }

    /**
     * CQRS Mathematical Closure: 
     * Reconstruct saga state natively by folding over its chronological event history.
     * No SQL read models or foreign references are allowed to dictate state.
     */
    protected function getSagaState(string $sagaId): array
    {
        $events = DomainEvent::where('aggregate_type', 'Saga')
            ->where('aggregate_id', $sagaId)
            ->orderBy('sequence') // Absolute Causal Truth
            ->get();
            
        if ($events->isEmpty()) {
            throw new RuntimeException("Saga {$sagaId} not found in Event Store. Cannot reconstitute state.");
        }
            
        $state = [
            'id' => $sagaId,
            'state' => 'pending',
            'current_step' => 0,
            'steps' => [],
        ];
        
        // Pure Left Fold state machine
        foreach ($events as $event) {
            $payload = is_string($event->payload) ? json_decode($event->payload, true) : $event->payload;
            switch ($event->event_type) {
                case 'SagaStarted':
                    $state['steps'] = $payload['steps'];
                    foreach ($state['steps'] as &$step) {
                        $step['status'] = 'pending';
                        $step['retry_count'] = 0;
                    }
                    $state['state'] = 'executing';
                    break;
                case 'SagaStepExecuted':
                    $state['steps'][$payload['step_index']]['status'] = 'executed';
                    $state['current_step']++;
                    if ($state['current_step'] >= count($state['steps'])) {
                        $state['state'] = 'completed';
                    }
                    break;
                case 'SagaStepFailed':
                    $idx = $payload['step_index'];
                    $state['steps'][$idx]['retry_count']++;
                    if ($state['steps'][$idx]['retry_count'] >= ($state['steps'][$idx]['max_retries'] ?? 3)) {
                        $state['steps'][$idx]['status'] = 'terminal_failed';
                        $state['state'] = 'compensating';
                    }
                    break;
                case 'SagaStepCompensated':
                    $state['steps'][$payload['step_index']]['status'] = 'compensated';
                    break;
            }
        }
        return $state;
    }

    public function startSaga(string $sagaType, string $sagaId, array $steps, array $context = []): void
    {
        DB::transaction(function () use ($sagaType, $sagaId, $steps, $context) {
            $this->outbox->publishDomain('Saga', $sagaId, 'SagaStarted', [
                'saga_type' => $sagaType,
                'steps' => $steps,
                'context' => $context
            ]);
            
            $this->executeNextStep($sagaId);
        });
    }

    protected function executeNextStep(string $sagaId): void
    {
        $sagaState = $this->getSagaState($sagaId);

        if ($sagaState['state'] === 'completed' || $sagaState['state'] === 'compensating') {
            return;
        }

        $idx = $sagaState['current_step'];
        if (!isset($sagaState['steps'][$idx])) {
            return; 
        }
        
        $nextStep = $sagaState['steps'][$idx];

        if ($nextStep['status'] === 'executed') {
            return;
        }

        $this->outbox->publishDomain(
            'Saga',
            $sagaState['id'],
            $nextStep['command_type'],
            array_merge($nextStep['command_payload'], ['saga_id' => $sagaState['id'], 'step_index' => $idx])
        );
    }

    public function stepCompleted(string $sagaId, int $stepIndex, array $result = []): void
    {
        DB::transaction(function () use ($sagaId, $stepIndex, $result) {
            $state = $this->getSagaState($sagaId);

            if ($state['steps'][$stepIndex]['status'] === 'executed' || $state['steps'][$stepIndex]['status'] === 'compensated') {
                return;
            }

            $this->outbox->publishDomain('Saga', $sagaId, 'SagaStepExecuted', [
                'step_index' => $stepIndex,
                'result' => $result
            ]);

            $this->executeNextStep($sagaId);
        });
    }

    public function stepFailed(string $sagaId, int $stepIndex, string $error): void
    {
        DB::transaction(function () use ($sagaId, $stepIndex, $error) {
            $state = $this->getSagaState($sagaId);

            if ($state['steps'][$stepIndex]['status'] === 'executed' || $state['steps'][$stepIndex]['status'] === 'compensated') {
                return; 
            }

            $this->outbox->publishDomain('Saga', $sagaId, 'SagaStepFailed', [
                'step_index' => $stepIndex,
                'error' => $error
            ]);

            $newState = $this->getSagaState($sagaId);

            if ($newState['state'] === 'compensating') {
                $this->compensate($newState['id']);
            } else {
                $this->executeNextStep($newState['id']);
            }
        });
    }

    protected function compensate(string $sagaId): void
    {
        $state = $this->getSagaState($sagaId);

        for ($i = $state['current_step']; $i >= 0; $i--) {
            if (isset($state['steps'][$i]) && $state['steps'][$i]['status'] === 'executed') {
                if (!empty($state['steps'][$i]['compensation_type'])) {
                    $this->outbox->publishDomain(
                        'Saga',
                        $state['id'],
                        $state['steps'][$i]['compensation_type'],
                        array_merge($state['steps'][$i]['compensation_payload'] ?? [], ['saga_id' => $state['id'], 'step_index' => $i])
                    );
                    
                    $this->outbox->publishDomain('Saga', $state['id'], 'SagaStepCompensated', [
                        'step_index' => $i
                    ]);
                }
            }
        }
    }
}
