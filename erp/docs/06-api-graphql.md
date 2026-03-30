# Phase D : API GraphQL Query Layer

## Vue d'Ensemble

GraphQL fournit une couche de requête flexible et performante sur les projections CQRS. Contrairement à REST, GraphQL permet :
- **Sélection précise** : Le client choisit exactement les champs nécessaires
- **Relations imbriquées** : Données liées en une seule requête
- **Pagination efficace** : Cursor-based pour grandes collections
- **Introspection** : Documentation auto-générée du schéma

---

## 1. Schéma GraphQL

### 1.1 Définitions des Types

```php
class DashboardSchema
{
    // ============================================
    // Type Sales : Agrégation quotidienne par route
    // ============================================
    public static function salesType(): ObjectType
    {
        return new ObjectType([
            'name' => 'Sales',
            'description' => 'Données de vente quotidiennes par route',
            'fields' => [
                'date' => [
                    'type' => Type::nonNull(Type::string()),
                    'description' => 'Date de l\'agrégation (YYYY-MM-DD)',
                ],
                'routeId' => [
                    'type' => Type::nonNull(Type::int()),
                    'description' => 'Identifiant de la tournée',
                ],
                'totalHt' => [
                    'type' => Type::nonNull(Type::float()),
                    'description' => 'Chiffre d\'affaires HT',
                ],
                'totalTtc' => [
                    'type' => Type::nonNull(Type::float()),
                    'description' => 'Chiffre d\'affaires TTC',
                ],
                'nbOrders' => [
                    'type' => Type::nonNull(Type::int()),
                    'description' => 'Nombre de commandes',
                ],
                'nbClientsVisited' => [
                    'type' => Type::int(),
                    'description' => 'Nombre de clients visités',
                ],
                'updatedAt' => [
                    'type' => Type::string(),
                    'description' => 'Date de dernière mise à jour',
                ],
            ],
        ]);
    }

    // ============================================
    // Type TopArticle : Classement des ventes
    // ============================================
    public static function topArticleType(): ObjectType
    {
        return new ObjectType([
            'name' => 'TopArticle',
            'description' => 'Statistiques de vente par article',
            'fields' => [
                'articleId' => [
                    'type' => Type::nonNull(Type::int()),
                ],
                'quantitySold' => [
                    'type' => Type::nonNull(Type::float()),
                ],
                'amountHt' => [
                    'type' => Type::nonNull(Type::float()),
                ],
                'article' => [
                    'type' => self::articleBriefType(),
                    'description' => 'Informations de l\'article',
                ],
            ],
        ]);
    }

    // ============================================
    // Type KPI : Métriques clés
    // ============================================
    public static function kpiType(): ObjectType
    {
        return new ObjectType([
            'name' => 'SalesKPI',
            'description' => 'Indicateurs clés de performance',
            'fields' => [
                'revenue' => [
                    'type' => Type::nonNull(self::revenueKpiType()),
                ],
                'orders' => [
                    'type' => Type::nonNull(self::ordersKpiType()),
                ],
                'visits' => [
                    'type' => Type::nonNull(self::visitsKpiType()),
                ],
                'routes' => [
                    'type' => Type::nonNull(self::routesKpiType()),
                ],
            ],
        ]);
    }

    public static function revenueKpiType(): ObjectType
    {
        return new ObjectType([
            'name' => 'RevenueKPI',
            'fields' => [
                'ht' => [
                    'type' => Type::nonNull(Type::float()),
                    'description' => 'Chiffre d\'affaires HT',
                ],
                'ttc' => [
                    'type' => Type::nonNull(Type::float()),
                    'description' => 'Chiffre d\'affaires TTC',
                ],
                'growthPercent' => [
                    'type' => Type::float(),
                    'description' => 'Croissance vs période précédente',
                ],
            ],
        ]);
    }

    public static function visitsKpiType(): ObjectType
    {
        return new ObjectType([
            'name' => 'VisitsKPI',
            'fields' => [
                'total' => [
                    'type' => Type::nonNull(Type::int()),
                ],
                'conversionRate' => [
                    'type' => Type::float(),
                    'description' => 'Taux de conversion visites → commandes',
                ],
            ],
        ]);
    }

    // ============================================
    // Pagination : Relay Cursor Connections
    // ============================================
    public static function connectionType(ObjectType $nodeType): ObjectType
    {
        return new ObjectType([
            'name' => $nodeType->name . 'Connection',
            'fields' => [
                'edges' => [
                    'type' => Type::listOf(self::edgeType($nodeType)),
                    'description' => 'Les éléments de la page',
                ],
                'pageInfo' => [
                    'type' => Type::nonNull(self::pageInfoType()),
                    'description' => 'Information de pagination',
                ],
            ],
        ]);
    }

    public static function pageInfoType(): ObjectType
    {
        return new ObjectType([
            'name' => 'PageInfo',
            'fields' => [
                'hasNextPage' => [
                    'type' => Type::nonNull(Type::boolean()),
                ],
                'endCursor' => [
                    'type' => Type::string(),
                    'description' => 'Curseur pour la page suivante',
                ],
            ],
        ]);
    }
}
```

---

## 2. Résolveurs

### 2.1 DashboardResolver

```php
class DashboardResolver
{
    // ============================================
    // Query: salesByRoute
    // Récupère les ventes avec pagination par curseur
    // ============================================
    public function salesByRoute(
        $root,
        array $args,
        $context,
        ResolveInfo $info
    ): array {
        $companyId = $args['companyId'];
        $dateFrom = $args['dateFrom'] ?? now()->subDays(30)->toDateString();
        $dateTo = $args['dateTo'] ?? now()->toDateString();

        $query = DB::table('dashboard_sales')
            ->where('company_id', $companyId)
            ->whereBetween('date', [$dateFrom, $dateTo]);

        // Filtre par route
        if (!empty($args['routeId'])) {
            $query->where('route_id', $args['routeId']);
        }

        // Pagination par curseur
        if (!empty($args['after'])) {
            $query->where('id', '>', $this->decodeCursor($args['after']));
        }

        $limit = min($args['first'] ?? 50, 100);

        // Récupère limit + 1 pour détecter hasNextPage
        $results = $query
            ->orderBy('date', 'desc')
            ->orderBy('route_id')
            ->limit($limit + 1)
            ->get();

        $hasMore = $results->count() > $limit;
        $nodes = $results->take($limit);

        return [
            'edges' => $nodes->map(fn($row) => [
                'node' => [
                    'date' => $row->date,
                    'routeId' => $row->route_id,
                    'totalHt' => $row->total_ht,
                    'totalTtc' => $row->total_ttc,
                    'nbOrders' => $row->nb_orders,
                    'nbClientsVisited' => $row->nb_clients_visited,
                ],
                'cursor' => $this->encodeCursor($row->id ?? "{$row->company_id}-{$row->date}-{$row->route_id}"),
            ]),
            'pageInfo' => [
                'hasNextPage' => $hasMore,
                'endCursor' => $hasMore
                    ? $this->encodeCursor($results->last()->id)
                    : null,
            ],
        ];
    }

    // ============================================
    // Query: topArticles
    // Classement des articles les plus vendus
    // ============================================
    public function topArticles(
        $root,
        array $args,
        $context,
        ResolveInfo $info
    ): array {
        $companyId = $args['companyId'];
        $date = $args['date'] ?? now()->toDateString();
        $limit = min($args['limit'] ?? 10, 100);

        $results = DB::table('dashboard_top_articles')
            ->where('company_id', $companyId)
            ->where('date', $date)
            ->orderBy('quantity_sold', 'desc')
            ->limit($limit)
            ->get();

        // Enrichissement conditionnel (N+1 pattern évité)
        if ($this->fieldIsRequested('article', $info)) {
            $articleIds = $results->pluck('article_id')->toArray();
            $articles = DB::table('articles')
                ->whereIn('article_id', $articleIds)
                ->get()
                ->keyBy('article_id');

            $results = $results->map(fn($row) => [
                'articleId' => $row->article_id,
                'quantitySold' => $row->quantity_sold,
                'amountHt' => $row->amount_ht,
                'article' => $articles[$row->article_id] ?? null,
            ]);
        }

        return [
            'items' => $results,
            'summary' => [
                'totalQuantity' => $results->sum('quantity_sold'),
                'totalAmount' => $results->sum('amount_ht'),
            ],
        ];
    }

    // ============================================
    // Query: salesKpi
    // Métriques agrégées avec comparaison période
    // ============================================
    public function salesKpi($root, array $args, $context): array
    {
        $companyId = $args['companyId'];
        $dateFrom = $args['dateFrom'] ?? now()->subDays(30)->toDateString();
        $dateTo = $args['dateTo'] ?? now()->toDateString();

        // Agrégations période actuelle
        $current = DB::table('dashboard_sales')
            ->where('company_id', $companyId)
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->selectRaw('
                COALESCE(SUM(total_ht), 0) as revenue_ht,
                COALESCE(SUM(total_ttc), 0) as revenue_ttc,
                COALESCE(SUM(nb_orders), 0) as orders,
                COALESCE(SUM(nb_clients_visited), 0) as visits,
                COUNT(DISTINCT route_id) as active_routes
            ')
            ->first();

        // Calcul période précédente pour comparaison
        $days = now()->parse($dateFrom)->diffInDays(now()->parse($dateTo)) + 1;
        $prevFrom = now()->parse($dateFrom)->subDays($days)->toDateString();
        $prevTo = now()->parse($dateFrom)->subDay()->toDateString();

        $previous = DB::table('dashboard_sales')
            ->where('company_id', $companyId)
            ->whereBetween('date', [$prevFrom, $prevTo])
            ->selectRaw('COALESCE(SUM(total_ht), 0) as revenue_ht')
            ->first();

        $growth = $previous->revenue_ht > 0
            ? (($current->revenue_ht - $previous->revenue_ht) / $previous->revenue_ht) * 100
            : 0;

        return [
            'revenue' => [
                'ht' => round($current->revenue_ht, 2),
                'ttc' => round($current->revenue_ttc, 2),
                'growthPercent' => round($growth, 2),
            ],
            'orders' => [
                'total' => (int) $current->orders,
                'avgPerRoute' => $current->active_routes > 0
                    ? round($current->orders / $current->active_routes, 2)
                    : 0,
            ],
            'visits' => [
                'total' => (int) $current->visits,
                'conversionRate' => $current->visits > 0
                    ? round(($current->orders / $current->visits) * 100, 2)
                    : 0,
            ],
            'routes' => [
                'active' => (int) $current->active_routes,
            ],
        ];
    }

    // ============================================
    // Query: liveSnapshot
    // État temps réel pour dashboard initial
    // ============================================
    public function liveSnapshot($root, array $args, $context): array
    {
        $companyId = $args['companyId'];
        $today = now()->toDateString();

        $todaySales = DB::table('dashboard_sales')
            ->where('company_id', $companyId)
            ->where('date', $today)
            ->selectRaw('
                COALESCE(SUM(total_ht), 0) as revenue,
                COALESCE(SUM(nb_orders), 0) as orders,
                COALESCE(SUM(nb_clients_visited), 0) as visits
            ')
            ->first();

        $topArticles = DB::table('dashboard_top_articles')
            ->where('company_id', $companyId)
            ->where('date', $today)
            ->orderBy('quantity_sold', 'desc')
            ->limit(5)
            ->get();

        return [
            'generatedAt' => now()->toIso8601String(),
            'today' => [
                'revenueHt' => $todaySales->revenue,
                'ordersCount' => (int) $todaySales->orders,
                'visitsCount' => (int) $todaySales->visits,
            ],
            'topArticles' => $topArticles->map(fn($row) => [
                'articleId' => $row->article_id,
                'quantity' => $row->quantity_sold,
                'amount' => $row->amount_ht,
            ]),
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
```

---

## 3. Exemples de Requêtes

### 3.1 Dashboard Quotidien

```graphql
query DashboardDaily($companyId: ID!, $date: String) {
  liveSnapshot(companyId: $companyId) {
    today {
      revenueHt
      ordersCount
      visitsCount
    }
    topArticles {
      articleId
      quantity
      amount
    }
  }
}

# Réponse
{
  "data": {
    "liveSnapshot": {
      "today": {
        "revenueHt": 15234.50,
        "ordersCount": 47,
        "visitsCount": 62
      },
      "topArticles": [
        {"articleId": 101, "quantity": 150, "amount": 4500},
        {"articleId": 205, "quantity": 89, "amount": 2670}
      ]
    }
  }
}
```

### 3.2 KPIs avec Comparaison

```graphql
query KPIsWithComparison($companyId: ID!) {
  salesKpi(
    companyId: $companyId
    dateFrom: "2026-03-01"
    dateTo: "2026-03-31"
  ) {
    revenue {
      ht
      ttc
      growthPercent
    }
    orders {
      total
      avgPerRoute
    }
    visits {
      total
      conversionRate
    }
  }
}

# Réponse
{
  "data": {
    "salesKpi": {
      "revenue": {
        "ht": 456780.50,
        "ttc": 548136.60,
        "growthPercent": 12.5
      },
      "orders": {
        "total": 1523,
        "avgPerRoute": 84.6
      },
      "visits": {
        "total": 1890,
        "conversionRate": 80.6
      }
    }
  }
}
```

### 3.3 Pagination des Ventes

```graphql
query SalesWithPagination($companyId: ID!, $after: String) {
  salesByRoute(
    companyId: $companyId
    dateFrom: "2026-03-01"
    dateTo: "2026-03-31"
    first: 20
    after: $after
  ) {
    edges {
      node {
        date
        routeId
        totalHt
        nbOrders
        nbClientsVisited
      }
      cursor
    }
    pageInfo {
      hasNextPage
      endCursor
    }
  }
}

# Page suivante
{
  "data": {
    "salesByRoute": {
      "edges": [...],
      "pageInfo": {
        "hasNextPage": true,
        "endCursor": "eyJpZCI6MTIzNDV9"
      }
    }
  }
}
```

### 3.4 Top Articles avec Détails

```graphql
query TopArticlesDetailed($companyId: ID!, $date: String) {
  topArticles(companyId: $companyId, date: $date, limit: 10) {
    items {
      articleId
      quantitySold
      amountHt
      article {
        id
        name
        reference
      }
    }
    summary {
      totalQuantity
      totalAmount
    }
  }
}
```

---

## 4. Optimisations

### 4.1 Sélection Conditionnelle (N+1)

```php
// Optimisation DataLoader pour éviter N+1 queries
class DataLoader
{
    protected array $promises = [];

    public function loadArticles(array $ids): array
    {
        // Batch loading unique
        if (!isset($this->promises['articles'])) {
            $this->promises['articles'] = DB::table('articles')
                ->whereIn('article_id', $ids)
                ->get()
                ->keyBy('article_id');
        }

        return $ids->map(fn($id) => $this->promises['articles'][$id] ?? null);
    }
}
```

### 4.2 Query Cost Analysis

```php
class QueryComplexityAnalyzer
{
    public function analyze(string $query): array
    {
        $complexity = 0;
        $depth = 0;

        // Limite la profondeur d'imbrication
        if ($depth > 5) {
            throw new \Exception('Query too deep');
        }

        // Limite le nombre de résultats
        if ($complexity > 1000) {
            throw new \Exception('Query too complex');
        }

        return ['complexity' => $complexity, 'depth' => $depth];
    }
}
```

---

## 8. Prochaines Sections

- [01-architecture-generale.md](./01-architecture-generale.md) - Documentation technique générale
- [02-infrastructure-core.md](./02-infrastructure-core.md) - Services et composants techniques
- [03-domaines-metiers.md](./03-domaines-metiers.md) - Implémentation des 13 macro-domaines
- [04-projections-analytics.md](./04-projections-analytics.md) - Phase C : Dashboards et analytics
- [05-temps-reel-crdt.md](./05-temps-reel-crdt.md) - Phase D : Temps réel et sync mobile
- [06-api-graphql.md](./06-api-graphql.md) - Phase D : API de requête GraphQL
- [07-alerting-observabilite.md](./07-alerting-observabilite.md) - Phase D : Monitoring et alertes
- [08-deployment-operations.md](./08-deployment-operations.md) - Guide de déploiement et maintenance