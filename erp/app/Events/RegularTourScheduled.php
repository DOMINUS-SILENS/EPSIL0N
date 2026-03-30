<?php

namespace App\Events;

class RegularTourScheduled
{
    public string $uuid;
    public int $routeId;
    public int $entrepriseId;
    public array $planningData; // days of week, client loops
    public array $assignedClients;

    public function __construct(string $uuid, int $routeId, int $entrepriseId, array $planningData, array $assignedClients = [])
    {
        $this->uuid = $uuid;
        $this->routeId = $routeId;
        $this->entrepriseId = $entrepriseId;
        $this->planningData = $planningData;
        $this->assignedClients = $assignedClients;
    }
}
