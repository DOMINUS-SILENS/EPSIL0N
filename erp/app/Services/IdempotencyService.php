<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

/**
 * Idempotency Service - Optimized for batch operations with caching
 * Ensures events are processed exactly once
 */
class IdempotencyService
{
    /** @var int Cache TTL in seconds (1 hour) */
    protected int $cacheTtl = 3600;

    /** @var int Maximum age for idempotency keys (7 days) */
    protected int $maxKeyAge = 7 * 24 * 60 * 60;

    /**
     * Check if a key has already been processed.
     * Uses cache for fast negative checks.
     */
    public function exists(string $key): bool
    {
        // Check cache first (for recent keys)
        $cacheKey = "idemp:{$key}";
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return true;
        }

        // Check database
        $exists = DB::table('idempotency_keys')->where('key', $key)->exists();

        if ($exists) {
            // Cache the result
            Cache::put($cacheKey, true, $this->cacheTtl);
        }

        return $exists;
    }

    /**
     * Batch check multiple keys - returns keys that exist
     * Much more efficient than individual checks
     *
     * @param array $keys Array of idempotency keys
     * @return array Keys that already exist
     */
    public function filterExisting(array $keys): array
    {
        if (empty($keys)) {
            return [];
        }

        // Remove duplicates
        $keys = array_unique($keys);

        // Check cache first
        $existing = [];
        $cacheKeys = [];

        foreach ($keys as $key) {
            $cacheKey = "idemp:{$key}";
            if (Cache::has($cacheKey)) {
                $existing[] = $key;
            } else {
                $cacheKeys[] = $key;
            }
        }

        if (empty($cacheKeys)) {
            return $existing;
        }

        // Batch check remaining keys from database
        $chunks = array_chunk($cacheKeys, 1000); // MySQL IN clause limit

        foreach ($chunks as $chunk) {
            $found = DB::table('idempotency_keys')
                ->whereIn('key', $chunk)
                ->pluck('key')
                ->toArray();

            // Cache found keys
            foreach ($found as $key) {
                Cache::put("idemp:{$key}", true, $this->cacheTtl);
                $existing[] = $key;
            }
        }

        return $existing;
    }

    /**
     * Mark a key as processed.
     * Uses cache for fast subsequent checks.
     */
    public function record(string $key): void
    {
        // Insert into database (ignore duplicates)
        DB::table('idempotency_keys')->insertOrIgnore([
            'key' => $key,
            'created_at' => now(),
        ]);

        // Cache the result
        Cache::put("idemp:{$key}", true, $this->cacheTtl);
    }

    /**
     * Batch record multiple keys
     *
     * @param array $keys
     */
    public function recordBatch(array $keys): void
    {
        if (empty($keys)) {
            return;
        }

        $keys = array_unique($keys);
        $now = now();

        $insertData = array_map(fn($key) => [
            'key' => $key,
            'created_at' => $now,
        ], $keys);

        // Chunk to avoid exceeding query limits
        foreach (array_chunk($insertData, 1000) as $chunk) {
            DB::table('idempotency_keys')->insertOrIgnore($chunk);
        }

        // Cache all keys
        foreach ($keys as $key) {
            Cache::put("idemp:{$key}", true, $this->cacheTtl);
        }
    }

    /**
     * Clean up old idempotency keys
     * Should be run periodically (daily)
     *
     * @param int $olderThanDays Delete keys older than this many days
     * @return int Number of keys deleted
     */
    public function cleanup(int $olderThanDays = 7): int
    {
        $cutoff = now()->subDays($olderThanDays);

        // Get keys to delete (for cache invalidation)
        $keysToDelete = DB::table('idempotency_keys')
            ->where('created_at', '<', $cutoff)
            ->limit(10000)
            ->pluck('key');

        // Delete from cache
        foreach ($keysToDelete as $key) {
            Cache::forget("idemp:{$key}");
        }

        // Delete from database in batches
        $deleted = 0;
        do {
            $batchKeys = DB::table('idempotency_keys')
                ->where('created_at', '<', $cutoff)
                ->limit(1000)
                ->pluck('key');

            if ($batchKeys->isEmpty()) {
                break;
            }

            $count = DB::table('idempotency_keys')
                ->whereIn('key', $batchKeys)
                ->delete();

            $deleted += $count;
        } while ($count > 0);

        return $deleted;
    }

    /**
     * Get statistics about idempotency keys
     */
    public function getStats(): array
    {
        $total = DB::table('idempotency_keys')->count();

        $recent = DB::table('idempotency_keys')
            ->where('created_at', '>=', now()->subDay())
            ->count();

        $old = DB::table('idempotency_keys')
            ->where('created_at', '<', now()->subDays(7))
            ->count();

        return [
            'total_keys' => $total,
            'last_24h' => $recent,
            'older_than_7d' => $old,
            'estimated_size_mb' => round($total * 50 / 1024 / 1024, 2), // Approximate
        ];
    }

    /**
     * Peek at a key without recording (for debugging)
     */
    public function peek(string $key): ?array
    {
        $record = DB::table('idempotency_keys')
            ->where('key', $key)
            ->first();

        return $record ? (array) $record : null;
    }
}
