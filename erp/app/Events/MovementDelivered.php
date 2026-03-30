<?php

namespace App\Events;

class MovementDelivered
{
    public string $uuid;
    public int $movementId;
    public int $entrepriseId;
    public array $lines;

    public function __construct(string $uuid, int $movementId, int $entrepriseId, array $lines)
    {
        $this->uuid = $uuid;
        $this->movementId = $movementId;
        $this->entrepriseId = $entrepriseId;
        $this->lines = $lines;
    }
}
