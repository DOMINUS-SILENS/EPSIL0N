<?php

namespace App\Services;

use App\Helpers\Logging;
use Illuminate\Support\Facades\Cache;

class BootstrapService
{
    public function bootstrap(): void
    {
        $this->ensureSequencesExist();
        $this->setSystemMode();
        $this->warmCaches();
    }

    protected function ensureSequencesExist(): void
    {
        // Ensure aggregate_sequences rows for common aggregates exist
        // For now, we can just ensure the table exists
    }

    protected function setSystemMode(): void
    {
        $health = app(HealthMonitor::class)->evaluate();
        $mode = $health->getRecommendedMode();
        app(SystemModeService::class)->setMode($mode);
    }

    protected function warmCaches(): void
    {
        // Preload commonly used data into cache
        // For now, a placeholder
    }

    public function boot(): void
    {
        $this->setSystemMode();
        $this->warmCaches();
        $this->verifyCoreInvariants();
        $this->initializeServices();
        $this->recordStartupState();
    }

    protected function verifyCoreInvariants(): void
    {
        // Verify core invariants
        // For now, a placeholder
    }

    protected function initializeServices(): void
    {
        // Initialize services
        // For now, a placeholder
    }

    protected function recordStartupState(): void
    {
        $state = [
            'time' => now()->toISOString(),
            'version' => config('app.version'),
            'env' => app()->environment(),
            'git_commit' => $gitCommit = '',
        ];
        if (function_exists('exec') && is_dir('.git')) {
            $gitCommit = exec('git rev-parse HEAD 2>/dev/null') ?: '';
        }
        $state['git_commit'] = $gitCommit;
        Logging::info('System bootstrapped', $state);
        Cache::forever('bootstrap_state', $state);
    }
}
