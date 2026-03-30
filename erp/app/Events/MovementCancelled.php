<?php

namespace App\Events;

class MovementCancelled
{
    public string $uuid;
    public int $movementId;
    public int $entrepriseId;
    public array $lines;
    public string $previousState;

    public function __construct(string $uuid, int $movementId, int $entrepriseId, array $lines, string $previousState)
    {
        $this->uuid = $uuid;
        $this->movementId = $movementId;
        $this->entrepriseId = $entrepriseId;
        $this->lines = $lines;
        $this->previousState = $previousState;
    }
}
