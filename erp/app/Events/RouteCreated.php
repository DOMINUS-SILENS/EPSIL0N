<?php

namespace App\Events;

class RouteCreated
{
    public string $uuid;
    public int $routeId;
    public int $entrepriseId;
    public array $data; // zone, sector hierarchies metadata

    public function __construct(string $uuid, int $routeId, int $entrepriseId, array $data)
    {
        $this->uuid = $uuid;
        $this->routeId = $routeId;
        $this->entrepriseId = $entrepriseId;
        $this->data = $data;
    }
}
