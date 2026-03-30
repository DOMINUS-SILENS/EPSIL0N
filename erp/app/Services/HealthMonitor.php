<?php

namespace App\Services;

class HealthMonitor
{
    protected SystemModeService $modeService;

    public function __construct(SystemModeService $modeService)
    {
        $this->modeService = $modeService;
    }

    /**
     * Evaluate system health and adjust mode if needed.
     */
    public function evaluate(): HealthReport
    {
        $mode = $this->getRecommendedMode();

        return new HealthReport($mode);
    }

    protected function getDbLoad(): float
    {
        // Simple query to get server load (mock)
        return 0.5;
    }

    protected function getAnomalyRate(): float
    {
        // Count anomalies in last minute / total requests
        return 0.01;
    }

    public function getRecommendedMode(): string
    {
        $metrics = $this->gatherMetrics();
        if ($metrics['queue_depth'] > 5000 || $metrics['db_load'] > 0.95) {
            return SystemModeService::MODE_SAFE_HALT;
        }
        if ($metrics['queue_depth'] > 1000 || $metrics['db_load'] > 0.9) {
            return SystemModeService::MODE_DEGRADED;
        }

        return SystemModeService::MODE_NORMAL;
    }
}
