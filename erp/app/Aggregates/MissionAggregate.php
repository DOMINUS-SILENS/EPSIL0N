<?php

namespace App\Aggregates;

use App\Events\MissionCreated;
use App\Events\MissionLoaded;
use App\Events\StopVisited;
use App\Events\MissionCompleted;
use Exception;

class MissionAggregate extends AggregateRoot
{
    protected string $state = 'none'; // planned, loaded, in_progress, completed
    protected int $missionId;

    protected function applyMissionCreated(MissionCreated $event): void
    {
        $this->state = 'planned';
        $this->missionId = $event->missionId;
    }

    protected function applyMissionLoaded(MissionLoaded $event): void
    {
        $this->state = 'loaded';
    }

    protected function applyStopVisited(StopVisited $event): void
    {
        $this->state = 'in_progress';
    }

    protected function applyMissionCompleted(MissionCompleted $event): void
    {
        $this->state = 'completed';
    }

    public function create(array $data, array $points): static
    {
        if ($this->state !== 'none') {
            throw new Exception("Mission already exists.");
        }
        
        $this->recordThat(new MissionCreated(
            $this->uuid(), 
            $data['mission_id'], 
            $data['entreprise_id'], 
            $data, 
            $points
        ));
        
        return $this;
    }

    public function loadPhysicalStock(int $entrepriseId): static
    {
        if ($this->state !== 'planned') {
            throw new Exception("Only planned missions can be loaded. Current state: {$this->state}");
        }

        $this->recordThat(new MissionLoaded($this->uuid(), $this->missionId, $entrepriseId));
        return $this;
    }

    public function visitStop(
        int $entrepriseId,
        int $pointId,
        int $routeId,
        string $visitedAt,
        array $deliveryData
    ): static {
        if (!in_array($this->state, ['loaded', 'in_progress'])) {
            throw new Exception("Can only execute stops if mission is loaded or currently active. State: {$this->state}");
        }

        $this->recordThat(new StopVisited(
            $this->uuid(),
            $this->missionId,
            $entrepriseId,
            $pointId,
            $routeId,
            $visitedAt,
            $deliveryData
        ));
        return $this;
    }

    public function complete(int $entrepriseId): static
    {
        if ($this->state !== 'in_progress') {
            throw new Exception("Cannot conclude an idle or unfinished mission.");
        }

        $this->recordThat(new MissionCompleted($this->uuid(), $this->missionId, $entrepriseId));
        return $this;
    }
}
