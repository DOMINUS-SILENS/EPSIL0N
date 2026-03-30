<?php

namespace App\Events;

class OptimizationRun
{
    public string $uuid;
    public int $optimizationId;
    public int $entrepriseId;
    public array $parameters; // Capacity, Driver shifts, Time Windows
    public array $optimizedMissions; // Computed sub-mission drops

    public function __construct(string $uuid, int $optimizationId, int $entrepriseId, array $parameters, array $optimizedMissions)
    {
        $this->uuid = $uuid;
        $this->optimizationId = $optimizationId;
        $this->entrepriseId = $entrepriseId;
        $this->parameters = $parameters;
        $this->optimizedMissions = $optimizedMissions;
    }
}
