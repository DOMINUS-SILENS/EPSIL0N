<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

class DynamicEquilibrium
{
    protected SystemModeService $modeService;

    public function __construct(SystemModeService $modeService)
    {
        $this->modeService = $modeService;
    }

    public function adjust(): void
    {
        $metrics = $this->gatherMetrics();
        $this->updateThresholds($metrics);
        $this->maybeAdjustMode($metrics);
    }

    protected function gatherMetrics(): array
    {
        $queueDepth = Queue::size('default');
        $dbConnections = DB::connection()->select('SHOW STATUS LIKE "Threads_running"');
        $activeThreads = $dbConnections[0]->Value ?? 0;

        return [
            'queue_depth' => $queueDepth,
            'active_threads' => $activeThreads,
            'cpu_load' => sys_getloadavg()[0],
        ];
    }

    protected function updateThresholds(array $metrics): void
    {
        // Dynamically adjust backpressure thresholds based on metrics
        $newThreshold = max(100, (int) ($metrics['queue_depth'] * 0.8));
        Cache::put('dynamic_queue_threshold', $newThreshold, 3600);
    }

    protected function maybeAdjustMode(array $metrics): void
    {
        if ($metrics['queue_depth'] > 5000 || $metrics['active_threads'] > 100) {
            $this->modeService->setMode(SystemModeService::MODE_DEGRADED);
        } elseif ($metrics['queue_depth'] > 10000 || $metrics['active_threads'] > 200) {
            $this->modeService->setMode(SystemModeService::MODE_SAFE_HALT);
        } else {
            $this->modeService->setMode(SystemModeService::MODE_NORMAL);
        }
    }
}
