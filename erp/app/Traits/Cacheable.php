<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Query\Builder;

trait Cacheable
{
    /**
     * Cache key prefix for this model
     */
    protected function cachePrefix(): string
    {
        return 'model:' . $this->getTable() . ':';
    }

    /**
     * Remember a query result for a given time
     *
     * @param string $key
     * @param \Closure $callback
     * @param int $ttl Seconds
     * @return mixed
     */
    public static function cached(string $key, \Closure $callback, int $ttl = 300)
    {
        $fullKey = 'model:' . (new static)->getTable() . ':' . $key;

        return Cache::remember($fullKey, $ttl, function () use ($callback) {
            return $callback();
        });
    }

    /**
     * Flush all cache entries for this model
     */
    public static function flushCache(): void
    {
        $instance = new static;
        $prefix = 'model:' . $instance->getTable() . ':';

        // Laravel Cache doesn't support wildcards, so we use tags if available
        // or document keys for manual clearing
        if (Cache::getStore() instanceof \Illuminate\Cache\TaggableStore) {
            Cache::tags([$instance->getTable()])->flush();
        }
    }

    /**
     * Remember with tags if supported
     *
     * @param string $key
     * @param \Closure $callback
     * @param int $ttl
     * @return mixed
     */
    public static function cachedWithTags(string $key, \Closure $callback, int $ttl = 300)
    {
        $fullKey = 'model:' . (new static)->getTable() . ':' . $key;
        $tags = [(new static)->getTable()];

        if (Cache::getStore() instanceof \Illuminate\Cache\TaggableStore) {
            return Cache::tags($tags)->remember($fullKey, $ttl, $callback);
        }

        return Cache::remember($fullKey, $ttl, $callback);
    }

    /**
     * Invalidate specific cache key
     *
     * @param string $key
     */
    public static function invalidateCache(string $key): void
    {
        $fullKey = 'model:' . (new static)->getTable() . ':' . $key;
        Cache::forget($fullKey);
    }
}
