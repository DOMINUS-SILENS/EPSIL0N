<?php

namespace App\Events;

class StockUpdated
{
    public string $uuid;
    public array $payload;

    public function __construct(string $uuid, array $payload)
    {
        $this->uuid = $uuid;
        $this->payload = $payload;
    }
}
