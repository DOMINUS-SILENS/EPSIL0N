<?php

namespace App\Events;

class MissionLoaded
{
    public string $uuid;
    public int $missionId;
    public int $entrepriseId;

    public function __construct(string $uuid, int $missionId, int $entrepriseId)
    {
        $this->uuid = $uuid;
        $this->missionId = $missionId;
        $this->entrepriseId = $entrepriseId;
    }
}
