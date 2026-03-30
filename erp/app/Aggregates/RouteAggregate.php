<?php

namespace App\Aggregates;

use App\Events\RouteCreated;
use App\Events\RegularTourScheduled;
use App\Events\OptimizationRun;
use App\Events\OptimizationApplied;
use Exception;

class RouteAggregate extends AggregateRoot
{
    public function defineRoute(int $routeId, int $entrepriseId, array $data): static
    {
        $this->recordThat(new RouteCreated($this->uuid(), $routeId, $entrepriseId, $data));
        return $this;
    }

    public function scheduleTour(int $routeId, int $entrepriseId, array $planningData, array $assignedClients): static
    {
        // Enforce basic planning parameters exist, e.g., 'days_of_week'
        if (empty($planningData['days_of_week'])) {
            throw new Exception("God-Level Logic Rule: Tour schedules require fixed calendar mapping logic.");
        }

        $this->recordThat(new RegularTourScheduled($this->uuid(), $routeId, $entrepriseId, $planningData, $assignedClients));
        return $this;
    }

    public function snapshotOptimization(int $optimizationId, int $entrepriseId, array $params, array $missions): static
    {
        // Snapshot the heavy mathematical routing payload
        $this->recordThat(new OptimizationRun($this->uuid(), $optimizationId, $entrepriseId, $params, $missions));
        return $this;
    }

    public function applyOptimization(int $optimizationId, int $entrepriseId): static
    {
        // Formal execution sealing the optimization intent physically
        $this->recordThat(new OptimizationApplied($this->uuid(), $optimizationId, $entrepriseId));
        return $this;
    }
}
