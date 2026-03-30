<?php

namespace App\Events;

class MovementCreated
{
    public string $uuid;
    public int $movementId;
    public int $entrepriseId;
    public array $data;
    public array $lines;

    public function __construct(string $uuid, int $movementId, int $entrepriseId, array $data, array $lines)
    {
        $this->uuid = $uuid;
        $this->movementId = $movementId;
        $this->entrepriseId = $entrepriseId;
        $this->data = $data;
        $this->lines = $lines;
    }
}
