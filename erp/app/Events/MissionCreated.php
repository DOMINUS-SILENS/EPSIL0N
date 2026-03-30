<?php

namespace App\Events;

class MissionCreated
{
    public string $uuid;
    public int|string $missionId;
    public int $entrepriseId;
    public array $data;
    public array $points; // Nested mission_point data

    public function __construct(string $uuid, int|string $missionId, int $entrepriseId, array $data, array $points)
    {
        $this->uuid = $uuid;
        $this->missionId = $missionId;
        $this->entrepriseId = $entrepriseId;
        $this->data = $data;
        $this->points = $points;
    }
}
