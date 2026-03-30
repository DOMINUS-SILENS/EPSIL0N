<?php

namespace App\Services;

use App\Models\DomainOutbox;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Redis;

/**
 * Service for broadcasting real-time dashboard updates.
 * Implements WebSocket/SSE push when projections change.
 */
class LiveDashboardService
{
    protected string $channelPrefix = 'dashboard.';

    /**
     * Broadcast update when dashboard_sales projection changes.
     */
    public function broadcastSalesUpdate(int $entrepriseId, string $date, array $data): void
    {
        $channel = "{$this->channelPrefix}company.{$entrepriseId}.sales";

        $payload = [
            'type' => 'sales_updated',
            'date' => $date,
            'data' => $data,
            'timestamp' => now()->toIso8601String(),
        ];

        // Use Redis pub/sub for horizontal scalability
        Redis::publish($channel, json_encode($payload));

        // Laravel Broadcasting for WebSocket clients
        if (config('broadcasting.default') !== 'null') {
            Broadcast::event(new \App\Events\DashboardUpdated($entrepriseId, $payload));
        }
    }

    /**
     * Broadcast top articles update.
     */
    public function broadcastTopArticlesUpdate(int $entrepriseId, string $date, array $articles): void
    {
        $channel = "{$this->channelPrefix}company.{$entrepriseId}.top-articles";

        $payload = [
            'type' => 'top_articles_updated',
            'date' => $date,
            'articles' => $articles,
            'timestamp' => now()->toIso8601String(),
        ];

        Redis::publish($channel, json_encode($payload));

        if (config('broadcasting.default') !== 'null') {
            Broadcast::event(new \App\Events\TopArticlesUpdated($entrepriseId, $payload));
        }
    }

    /**
     * Subscribe a client connection to company-specific channels.
     */
    public function subscribeClient(string $connectionId, int $entrepriseId, array $channels): void
    {
        $subscriptions = [];

        foreach ($channels as $channel) {
            $subscriptions[] = "{$this->channelPrefix}company.{$entrepriseId}.{$channel}";
        }

        Redis::sadd("connections:{$connectionId}:subscriptions", ...$subscriptions);
        Redis::expire("connections:{$connectionId}:subscriptions", 3600); // 1 hour TTL
    }

    /**
     * Get current dashboard snapshot for initial page load.
     */
    public function getSnapshot(int $entrepriseId, ?string $date = null): array
    {
        $date = $date ?? now()->toDateString();

        return [
            'sales' => \DB::table('dashboard_sales')
                ->where('entreprise_id', $entrepriseId)
                ->where('date', $date)
                ->get(),
            'top_articles' => \DB::table('dashboard_top_articles')
                ->where('entreprise_id', $entrepriseId)
                ->where('date', $date)
                ->orderBy('quantity_sold', 'desc')
                ->limit(10)
                ->get(),
            'generated_at' => now()->toIso8601String(),
        ];
    }
}