<?php

namespace App\GraphQL\Resolvers;

use Illuminate\Support\Facades\DB;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * GraphQL Resolvers for Dashboard Queries.
 * Optimized projections access with cursor-based pagination.
 */
class DashboardResolver
{
    /**
     * Resolve sales by route query.
     */
    public function salesByRoute($root, array $args, $context, ResolveInfo $info): array
    {
        $entrepriseId = $args['entrepriseId'];
        $dateFrom = $args['dateFrom'] ?? now()->subDays(30)->toDateString();
        $dateTo = $args['dateTo'] ?? now()->toDateString();

        $query = DB::table('dashboard_sales')
            ->where('entreprise_id', $entrepriseId)
            ->whereBetween('date', [$dateFrom, $dateTo]);

        // Apply route filter if provided
        if (!empty($args['routeId'])) {
            $query->where('route_id', $args['routeId']);
        }

        // Apply cursor pagination
        if (!empty($args['after'])) {
            $query->where('id', '>', $this->decodeCursor($args['after']));
        }

        $limit = min($args['first'] ?? 50, 100); // Max 100 per page

        $results = $query
            ->orderBy('date', 'desc')
            ->limit($limit + 1)
            ->get();

        $hasMore = $results->count() > $limit;
        $nodes = $results->take($limit);

        return [
            'edges' => $nodes->map(fn($row) => [
                'node' => $this->mapSalesRow($row),
                'cursor' => $this->encodeCursor($row->id ?? "{$row->entreprise_id}-{$row->date}-{$row->route_id}"),
            ]),
            'pageInfo' => [
                'hasNextPage' => $hasMore,
                'endCursor' => $hasMore ? $this->encodeCursor($results->last()->id) : null,
            ],
        ];
    }

    /**
     * Resolve top articles query.
     */
    public function topArticles($root, array $args, $context, ResolveInfo $info): array
    {
        $entrepriseId = $args['entrepriseId'];
        $date = $args['date'] ?? now()->toDateString();
        $limit = min($args['limit'] ?? 10, 100);

        $results = DB::table('dashboard_top_articles')
            ->where('entreprise_id', $entrepriseId)
            ->where('date', $date)
            ->orderBy('quantity_sold', 'desc')
            ->limit($limit)
            ->get();

        // Enrich with article details if requested
        if ($this->fieldIsRequested('article', $info)) {
            $articleIds = $results->pluck('article_id')->toArray();
            $articles = DB::table('articles')
                ->whereIn('article_id', $articleIds)
                ->get()
                ->keyBy('article_id');

            $results = $results->map(fn($row) => (array) $row + [
                'article' => $articles[$row->article_id] ?? null,
            ]);
        }

        return [
            'items' => $results->map(fn($row) => $this->mapArticleRow($row)),
            'summary' => [
                'totalQuantity' => $results->sum('quantity_sold'),
                'totalAmount' => $results->sum('amount_ht'),
            ],
        ];
    }

    /**
     * Resolve sales KPIs (aggregated metrics).
     */
    public function salesKpi($root, array $args, $context): array
    {
        $entrepriseId = $args['entrepriseId'];
        $dateFrom = $args['dateFrom'] ?? now()->subDays(30)->toDateString();
        $dateTo = $args['dateTo'] ?? now()->toDateString();

        $aggregates = DB::table('dashboard_sales')
            ->where('entreprise_id', $entrepriseId)
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->selectRaw('
                SUM(subtotal_amount) as subtotal_amount,
                SUM(total_amount) as total_amount,
                SUM(nb_orders) as total_orders,
                SUM(nb_clients_visited) as total_visits,
                COUNT(DISTINCT route_id) as active_routes,
                AVG(subtotal_amount) as avg_daily_revenue
            ')
            ->first();

        // Compare with previous period
        $days = now()->parse($dateFrom)->diffInDays(now()->parse($dateTo)) + 1;
        $prevDateFrom = now()->parse($dateFrom)->subDays($days)->toDateString();
        $prevDateTo = now()->parse($dateFrom)->subDay()->toDateString();

        $previous = DB::table('dashboard_sales')
            ->where('entreprise_id', $entrepriseId)
            ->whereBetween('date', [$prevDateFrom, $prevDateTo])
            ->selectRaw('SUM(subtotal_amount) as subtotal_amount')
            ->first();

        $currentTotal = $aggregates->subtotal_amount ?? 0;
        $previousTotal = $previous->subtotal_amount ?? 0;
        $growth = $previousTotal > 0 ? (($currentTotal - $previousTotal) / $previousTotal) * 100 : 0;

        return [
            'revenue' => [
                'ht' => round($aggregates->subtotal_amount ?? 0, 2),
                'ttc' => round($aggregates->total_amount ?? 0, 2),
                'growth_percent' => round($growth, 2),
            ],
            'orders' => [
                'total' => $aggregates->total_orders ?? 0,
                'avg_per_route' => $aggregates->active_routes > 0
                    ? round(($aggregates->total_orders / $aggregates->active_routes), 2)
                    : 0,
            ],
            'visits' => [
                'total' => $aggregates->total_visits ?? 0,
                'conversion_rate' => $aggregates->total_visits > 0
                    ? round(($aggregates->total_orders / $aggregates->total_visits) * 100, 2)
                    : 0,
            ],
            'routes' => [
                'active' => $aggregates->active_routes ?? 0,
                'avg_revenue_per_route' => round($aggregates->avg_daily_revenue ?? 0, 2),
            ],
        ];
    }

    /**
     * Resolve real-time dashboard snapshot.
     */
    public function liveSnapshot($root, array $args, $context): array
    {
        $entrepriseId = $args['entrepriseId'];
        $today = now()->toDateString();

        $todaySales = DB::table('dashboard_sales')
            ->where('entreprise_id', $entrepriseId)
            ->where('date', $today)
            ->selectRaw('
                SUM(subtotal_amount) as subtotal_amount,
                SUM(nb_orders) as nb_orders,
                SUM(nb_clients_visited) as nb_visits
            ')
            ->first();

        $topToday = DB::table('dashboard_top_articles')
            ->where('entreprise_id', $entrepriseId)
            ->where('date', $today)
            ->orderBy('quantity_sold', 'desc')
            ->limit(5)
            ->get();

        return [
            'generated_at' => now()->toIso8601String(),
            'today' => [
                'revenue_ht' => $todaySales->subtotal_amount ?? 0,
                'orders_count' => $todaySales->nb_orders ?? 0,
                'visits_count' => $todaySales->nb_visits ?? 0,
            ],
            'top_articles' => $topToday->map(fn($row) => [
                'article_id' => $row->article_id,
                'quantity' => $row->quantity_sold,
                'amount' => $row->amount_ht,
            ]),
        ];
    }

    // Helper methods
    private function mapSalesRow($row): array
    {
        return [
            'date' => $row->date,
            'routeId' => $row->route_id,
            'totalHt' => $row->subtotal_amount,
            'totalTtc' => $row->total_amount,
            'nbOrders' => $row->nb_orders,
            'nbClientsVisited' => $row->nb_clients_visited,
            'updatedAt' => $row->updated_at,
        ];
    }

    private function mapArticleRow($row): array
    {
        return [
            'articleId' => $row->article_id,
            'quantitySold' => $row->quantity_sold,
            'amountHt' => $row->amount_ht,
            'article' => isset($row->article) ? [
                'id' => $row->article->article_id ?? null,
                'name' => $row->article->libelle ?? null,
            ] : null,
        ];
    }

    private function encodeCursor(string $id): string
    {
        return base64_encode($id);
    }

    private function decodeCursor(string $cursor): string
    {
        return base64_decode($cursor);
    }

    private function fieldIsRequested(string $field, ResolveInfo $info): bool
    {
        $fields = $info->getFieldSelection(1);
        return isset($fields[$field]);
    }
}