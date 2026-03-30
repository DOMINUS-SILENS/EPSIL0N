<?php

namespace App\Services;

class HealthReport
{
    protected string $recommendedMode;

    public function __construct(string $recommendedMode)
    {
        $this->recommendedMode = $recommendedMode;
    }

    public function getRecommendedMode(): string
    {
        return $this->recommendedMode;
    }
}
