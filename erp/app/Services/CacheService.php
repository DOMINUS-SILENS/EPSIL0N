<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

/**
 * Application Cache Service
 * Provides optimized caching strategies for SFA operations
 */
class CacheService
{
    /** @var int Default TTL in seconds (5 minutes) */
    protected int $defaultTtl = 300;

    /** @var int Long TTL for reference data (1 hour) */
    protected int $longTtl = 3600;

    /** @var int Short TTL for frequently changing data (30 seconds) */
    protected int $shortTtl = 30;

    /**
     * Get stock balance with caching
     *
     * @param int $entrepriseId
     * @param int $depotId
     * @param int $articleId
     * @return float|null
     */
    public function getStockBalance(int $entrepriseId, int $depotId, int $articleId): ?float
    {
        $key = "stock:{$entrepriseId}:{$depotId}:{$articleId}";

        return Cache::remember($key, $this->shortTtl, function () use ($entrepriseId, $depotId, $articleId) {
            $result = \DB::table('balance_stock')
                ->where('entreprise_id', $entrepriseId)
                ->where('depot_id', $depotId)
                ->where('article_id', $articleId)
                ->value('quantite_disponible');

            return $result !== null ? (float) $result : null;
        });
    }

    /**
     * Invalidate stock balance cache
     */
    public function invalidateStockBalance(int $entrepriseId, int $depotId, int $articleId): void
    {
        $key = "stock:{$entrepriseId}:{$depotId}:{$articleId}";
        Cache::forget($key);
    }

    /**
     * Get customer credit limit with caching
     */
    public function getCustomerCreditLimit(int $customerId): ?float
    {
        $key = "credit:limit:{$customerId}";

        return Cache::remember($key, $this->defaultTtl, function () use ($customerId) {
            $result = \DB::table('credit_policies')
                ->where('contact_id', $customerId)
                ->where('is_active', true)
                ->value('credit_limit');

            return $result !== null ? (float) $result : null;
        });
    }

    /**
     * Get customer balance (used + reserved)
     */
    public function getCustomerBalance(int $customerId): array
    {
        $key = "credit:balance:{$customerId}";

        return Cache::remember($key, $this->shortTtl, function () use ($customerId) {
            $total = \DB::table('orders')
                ->where('customer_id', $customerId)
                ->whereIn('status', ['submitted', 'confirmed', 'pending'])
                ->sum('total_amount') ?: 0;

            $reserved = \DB::table('credit_reservations')
                ->where('customer_id', $customerId)
                ->where('status', 'active')
                ->sum('amount') ?: 0;

            return [
                'used' => (float) $total,
                'reserved' => (float) $reserved,
                'available' => max(0, $this->getCustomerCreditLimit($customerId) - $total - $reserved),
            ];
        });
    }

    /**
     * Get pricing group for an article
     */
    public function getArticlePricing(int $articleId, ?int $customerGroupId = null): ?array
    {
        $key = "pricing:{$articleId}:" . ($customerGroupId ?? 'default');

        return Cache::remember($key, $this->longTtl, function () use ($articleId, $customerGroupId) {
            $query = \DB::table('article_unite')
                ->where('article_id', $articleId)
                ->where('active', true)
                ->select(['article_id', 'unite_id', 'prix_vente', 'prix_achat', 'quantite']);

            if ($customerGroupId) {
                // Apply customer group pricing if available
                $query->leftJoin('article_groupe_prix', function ($join) use ($customerGroupId) {
                    $join->on('article_unite.article_id', '=', 'article_groupe_prix.article_id')
                        ->where('article_groupe_prix.groupe_client_id', $customerGroupId);
                })
                ->addSelect(['article_groupe_prix.prix_special']);
            }

            return $query->first()?->toArray();
        });
    }

    /**
     * Get article with stock for a depot
     */
    public function getArticleWithStock(int $articleId, int $depotId): ?array
    {
        $key = "article:stock:{$articleId}:{$depotId}";

        return Cache::remember($key, $this->shortTtl, function () use ($articleId, $depotId) {
            $article = \DB::table('article')
                ->where('article_id', $articleId)
                ->where('active', true)
                ->first();

            if (!$article) {
                return null;
            }

            $stock = \DB::table('balance_stock')
                ->where('article_id', $articleId)
                ->where('depot_id', $depotId)
                ->first(['quantite_disponible', 'quantite_reservee', 'quantite_min']);

            return [
                'article' => (array) $article,
                'stock' => $stock ? (array) $stock : null,
            ];
        });
    }

    /**
     * Cache aggregate sequences for idempotency
     */
    public function rememberSequence(string $aggregateType, string $aggregateId, callable $callback)
    {
        $key = "seq:{$aggregateType}:{$aggregateId}";

        return Cache::remember($key, $this->shortTtl, $callback);
    }

    /**
     * Invalidate sequence cache
     */
    public function invalidateSequence(string $aggregateType, string $aggregateId): void
    {
        $key = "seq:{$aggregateType}:{$aggregateId}";
        Cache::forget($key);
    }

    /**
     * Remember with request-level cache (array store)
     * Use for data only needed during current request
     */
    public function rememberRequest(string $key, callable $callback)
    {
        return Cache::store('array')->remember($key, 0, $callback);
    }

    /**
     * Preload stock balances for multiple articles (batch optimization)
     *
     * @param int $entrepriseId
     * @param int $depotId
     * @param array $articleIds
     * @return array Keyed by article_id
     */
    public function preloadStockBalances(int $entrepriseId, int $depotId, array $articleIds): array
    {
        // Check cache first
        $balances = [];
        $missingIds = [];

        foreach ($articleIds as $articleId) {
            $key = "stock:{$entrepriseId}:{$depotId}:{$articleId}";
            $cached = Cache::get($key);

            if ($cached !== null) {
                $balances[$articleId] = $cached;
            } else {
                $missingIds[] = $articleId;
            }
        }

        // Batch fetch missing from DB
        if (!empty($missingIds)) {
            $fetched = \DB::table('balance_stock')
                ->where('entreprise_id', $entrepriseId)
                ->where('depot_id', $depotId)
                ->whereIn('article_id', $missingIds)
                ->get(['article_id', 'quantite_disponible']);

            foreach ($fetched as $row) {
                $balances[$row->article_id] = (float) $row->quantite_disponible;
                $key = "stock:{$entrepriseId}:{$depotId}:{$row->article_id}";
                Cache::put($key, $balances[$row->article_id], $this->shortTtl);
            }
        }

        return $balances;
    }

    /**
     * Clear all SFA-related caches
     */
    public function clearAllSfaCaches(): void
    {
        $patterns = ['stock:*', 'credit:*', 'pricing:*', 'article:*', 'seq:*'];

        foreach ($patterns as $pattern) {
            // Note: Redis supports pattern deletion, but generic cache does not
            if (config('cache.default') === 'redis') {
                Redis::connection('cache')->del(Redis::connection('cache')->keys($pattern));
            }
        }

        // Clear array cache (request-level)
        Cache::store('array')->flush();
    }

    /**
     * Get cache statistics for monitoring
     */
    public function getStats(): array
    {
        if (config('cache.default') === 'redis') {
            $info = Redis::connection('cache')->info('memory');

            return [
                'used_memory' => $info['used_memory'] ?? 0,
                'used_memory_human' => $info['used_memory_human'] ?? '0B',
                'cached_keys_count' => count(Redis::connection('cache')->keys('*')),
            ];
        }

        return [
            'driver' => config('cache.default'),
            'note' => 'Stats not available for this driver',
        ];
    }
}
