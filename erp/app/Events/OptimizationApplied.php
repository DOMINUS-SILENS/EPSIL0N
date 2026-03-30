<?php

namespace App\Events;

class OptimizationApplied
{
    public string $uuid;
    public int $optimizationId;
    public int $entrepriseId;

    public function __construct(string $uuid, int $optimizationId, int $entrepriseId)
    {
        $this->uuid = $uuid;
        $this->optimizationId = $optimizationId;
        $this->entrepriseId = $entrepriseId;
    }
}
