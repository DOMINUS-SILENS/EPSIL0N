<?php

namespace App\Events;

class VehicleRegistered
{
    public function __construct(
        public string $uuid,
        public int $vehicleId,
        public int $entrepriseId,
        public array $data
    ) {}
}
