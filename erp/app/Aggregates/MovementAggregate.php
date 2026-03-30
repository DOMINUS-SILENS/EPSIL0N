<?php

namespace App\Aggregates;

use App\Events\MovementCreated;
use App\Events\MovementValidated;
use App\Events\MovementDelivered;
use App\Events\MovementCancelled;
use Exception;

class MovementAggregate extends AggregateRoot
{
    protected string $state = 'none'; // draft, validated, delivered, cancelled
    protected array $lines = [];
    protected int $movementId;
    
    // In a pure event-sourced aggregate, we rebuild state via historical events:
    protected function applyMovementCreated(MovementCreated $event): void
    {
        $this->state = 'draft';
        $this->movementId = $event->movementId;
        $this->lines = $event->lines;
    }

    protected function applyMovementValidated(MovementValidated $event): void
    {
        $this->state = 'validated';
    }

    protected function applyMovementDelivered(MovementDelivered $event): void
    {
        $this->state = 'delivered';
    }

    protected function applyMovementCancelled(MovementCancelled $event): void
    {
        $this->state = 'cancelled';
    }

    public function create(array $data, array $lines): static
    {
        if ($this->state !== 'none') {
            throw new Exception("Movement already exists.");
        }
        
        $this->recordThat(new MovementCreated($this->uuid(), $data['mouvement_id'], $data['entreprise_id'], $data, $lines));
        return $this;
    }

    public function validate(
        int $entrepriseId,
        int $contactId,
        float $totalOrderAmount,
        int $routeId,
        string $date,
        float $totalHt,
        float $totalTtc,
        array $enrichedLines
    ): static {
        if ($this->state !== 'draft') {
            throw new Exception("Only draft movements can be validated. Current state: {$this->state}");
        }

        // Note: Credit limit checks are now a Zone concern (PaymentRiskSaga) or a local 
        // offline projection concern. The Secteur aggregate only emits the operational fact.


        $this->recordThat(new MovementValidated(
            $this->uuid(),
            $this->movementId,
            $entrepriseId,
            $routeId,
            $date,
            $totalHt,
            $totalTtc,
            $contactId,
            $enrichedLines
        ));
        return $this;
    }

    public function deliver(int $entrepriseId): static
    {
        if ($this->state !== 'validated') {
            throw new Exception("Only validated movements can be delivered. Current state: {$this->state}");
        }

        $this->recordThat(new MovementDelivered($this->uuid(), $this->movementId, $entrepriseId, $this->lines));
        return $this;
    }

    public function cancel(int $entrepriseId): static
    {
        if ($this->state === 'delivered' || $this->state === 'cancelled') {
            throw new Exception("Cannot cancel a delivered or already cancelled movement.");
        }

        $this->recordThat(new MovementCancelled($this->uuid(), $this->movementId, $entrepriseId, $this->lines, $this->state));
        return $this;
    }

    /**
     * Snapshottable interface
     */
    protected function toSnapshot(): array
    {
        return [
            'state' => $this->state,
            'lines' => $this->lines,
            'movementId' => $this->movementId,
            'tenantId' => $this->tenantId,
        ];
    }

    protected function fromSnapshot(array $data): void
    {
        $this->state = $data['state'] ?? 'none';
        $this->lines = $data['lines'] ?? [];
        $this->movementId = $data['movementId'] ?? 0;
        $this->tenantId = $data['tenantId'] ?? null;
    }
}
