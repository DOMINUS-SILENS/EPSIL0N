<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class MetricsService
{
    public function increment(string $metric, int $by = 1): void
    {
        Cache::increment("metric:{$metric}", $by);
    }

    public function get(string $metric): int
    {
        return (int) Cache::get("metric:{$metric}", 0);
    }

    public function recordAttention(): bool
    {
        $key = 'attention_budget';
        $current = Cache::get($key, 0);
        if ($current >= 1000) {
            return false;
        }
        Cache::increment($key);

        return true;
    }
}
