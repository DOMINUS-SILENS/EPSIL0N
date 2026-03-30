<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ByzantineAdapter
{
    protected $serviceName;

    protected $failureCountKey;

    public function __construct(string $serviceName)
    {
        $this->serviceName = $serviceName;
        $this->failureCountKey = "circuit:{$serviceName}";
    }

    public function call(string $method, string $url, array $options = [])
    {
        // Check circuit breaker
        $failures = Cache::get($this->failureCountKey, 0);
        if ($failures >= 5) {
            throw new \RuntimeException("Circuit open for {$this->serviceName}");
        }

        try {
            $response = Http::withOptions($options)->$method($url);
            if ($response->successful()) {
                Cache::forget($this->failureCountKey);

                return $response->json();
            }
            // Track failure
            Cache::increment($this->failureCountKey);
            Cache::expire($this->failureCountKey, 60);
            throw new \RuntimeException("Call to {$this->serviceName} failed: {$response->status()}");
        } catch (\Exception $e) {
            Cache::increment($this->failureCountKey);
            Cache::expire($this->failureCountKey, 60);
            throw $e;
        }
    }

    public function callIdempotent(string $method, string $url, string $idempotencyKey, array $options = [])
    {
        $cacheKey = "idempotent:{$idempotencyKey}";
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        $result = $this->call($method, $url, $options);
        Cache::put($cacheKey, $result, 3600);

        return $result;
    }
}
