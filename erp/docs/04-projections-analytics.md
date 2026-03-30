# Phase C : Projections Analytics & Dashboards

## Vue d'Ensemble

La Phase C implémente le système d'analytics temps réel basé sur les projections CQRS. Les dashboards sont alimentés automatiquement par les événements de domaine, garantissant une latence de lecture optimale (O(1)).

---

## 1. Tables de Projection Analytiques

### 1.1 dashboard_sales

Table d'agrégation des ventes quotidiennes par tournée.

```sql
CREATE TABLE dashboard_sales (
    company_id BIGINT UNSIGNED NOT NULL,
    date DATE NOT NULL,
    route_id BIGINT UNSIGNED NOT NULL,
    total_ht DECIMAL(15,2) DEFAULT 0,
    total_ttc DECIMAL(15,2) DEFAULT 0,
    nb_orders INT DEFAULT 0,
    nb_clients_visited INT DEFAULT 0,
    last_event_id BIGINT UNSIGNED,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (company_id, date, route_id)
) PARTITION BY HASH(company_id) PARTITIONS 16;
```

**Colonnes analytiques :**

| Colonne | Description | Source Événement |
|---------|-------------|------------------|
| `total_ht` | Chiffre d'affaires HT cumulé | `MovementValidated.totalHt` |
| `total_ttc` | Chiffre d'affaires TTC cumulé | `MovementValidated.totalTtc` |
| `nb_orders` | Nombre de commandes validées | `MovementValidated` (count) |
| `nb_clients_visited` | Nombre de visites effectuées | `StopVisited` (count) |

### 1.2 dashboard_top_articles

Table de classement des articles les plus vendus.

```sql
CREATE TABLE dashboard_top_articles (
    company_id BIGINT UNSIGNED NOT NULL,
    date DATE NOT NULL,
    article_id BIGINT UNSIGNED NOT NULL,
    quantity_sold DECIMAL(15,4) DEFAULT 0,
    amount_ht DECIMAL(15,2) DEFAULT 0,
    last_event_id BIGINT UNSIGNED,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (company_id, date, article_id)
) PARTITION BY HASH(company_id) PARTITIONS 16;
```

---

## 2. SalesDashboardProjector

### 2.1 Architecture

```php
class SalesDashboardProjector extends Projector
{
    /**
     * Gère deux événements métier :
     * - MovementValidated : màj des totaux ventes
     * - StopVisited : màj du compteur de visites
     */

    public function handleMovementValidated(array $payload, DomainOutbox $event): void
    {
        // UPSERT avec protection idempotence
        DB::statement("
            INSERT INTO dashboard_sales
            (company_id, date, route_id, total_ht, total_ttc, nb_orders, last_event_id, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, 1, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                total_ht = IF(last_event_id < VALUES(last_event_id),
                            total_ht + VALUES(total_ht), total_ht),
                total_ttc = IF(last_event_id < VALUES(last_event_id),
                             total_ttc + VALUES(total_ttc), total_ttc),
                nb_orders = IF(last_event_id < VALUES(last_event_id),
                           nb_orders + 1, nb_orders),
                last_event_id = IF(last_event_id < VALUES(last_event_id),
                                 VALUES(last_event_id), last_event_id),
                updated_at = IF(last_event_id < VALUES(last_event_id), NOW(), updated_at)
        ", [
            $payload['companyId'],
            $payload['date'],
            $payload['routeId'],
            $payload['totalHt'],
            $payload['totalTtc'],
            $event->id,
        ]);

        // Màj top articles
        foreach ($payload['lines'] as $line) {
            DB::statement("
                INSERT INTO dashboard_top_articles
                (company_id, date, article_id, quantity_sold, amount_ht, last_event_id, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    quantity_sold = IF(last_event_id < VALUES(last_event_id),
                                     quantity_sold + VALUES(quantity_sold), quantity_sold),
                    amount_ht = IF(last_event_id < VALUES(last_event_id),
                                 amount_ht + VALUES(amount_ht), amount_ht),
                    last_event_id = IF(last_event_id < VALUES(last_event_id),
                                     VALUES(last_event_id), last_event_id)
            ", [
                $payload['companyId'],
                $payload['date'],
                $line['article_id'],
                $line['quantity'],
                $line['price_ht'],
                $event->id,
            ]);
        }
    }

    public function handleStopVisited(array $payload, DomainOutbox $event): void
    {
        DB::statement("
            INSERT INTO dashboard_sales
            (company_id, date, route_id, nb_clients_visited, last_event_id, created_at, updated_at)
            VALUES (?, ?, ?, 1, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                nb_clients_visited = IF(last_event_id < VALUES(last_event_id),
                                      nb_clients_visited + 1, nb_clients_visited),
                last_event_id = IF(last_event_id < VALUES(last_event_id),
                                 VALUES(last_event_id), last_event_id),
                updated_at = NOW()
        ", [
            $payload['companyId'],
            $payload['date'] ?? now()->toDateString(),
            $payload['routeId'],
            $event->id,
        ]);
    }
}
```

### 2.2 Garanties Idempotentes

```
┌─────────────────────────────────────────────────────────┐
│              MÉCANISME D'IDEMPOTENCE                    │
├─────────────────────────────────────────────────────────┤
│                                                         │
│   Scénario: Même événement traité 2 fois               │
│                                                         │
│   1er traitement:                                       │
│   - last_event_id (table) = NULL                      │
│   - event_id = 100                                      │
│   - IF(NULL < 100, increment, skip) → INCREMENT       │
│   - Résultat: nb_orders = 1, last_event_id = 100      │
│                                                         │
│   2ème traitement (doublon):                           │
│   - last_event_id (table) = 100                       │
│   - event_id = 100                                      │
│   - IF(100 < 100, increment, skip) → SKIP             │
│   - Résultat: nb_orders = 1 (pas de changement)        │
│                                                         │
│   ✅ Atomicité garantie par ON DUPLICATE KEY UPDATE   │
│   ✅ Pas de race conditions (MySQL row-level locking)   │
└─────────────────────────────────────────────────────────┘
```

---

## 3. Requêtes Analytics Optimisées

### 3.1 Requêtes Standards

```sql
-- KPI du jour
SELECT
    SUM(total_ht) as ca_jour,
    SUM(nb_orders) as nb_commandes,
    SUM(nb_clients_visited) as nb_visites,
    ROUND(SUM(nb_orders) / NULLIF(SUM(nb_clients_visited), 0) * 100, 2) as taux_conversion
FROM dashboard_sales
WHERE company_id = 1
  AND date = CURDATE();

-- Évolution hebdomadaire
SELECT
    date,
    SUM(total_ht) as ca,
    SUM(nb_orders) as commandes
FROM dashboard_sales
WHERE company_id = 1
  AND date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
GROUP BY date
ORDER BY date;

-- Performance par tournée
SELECT
    r.route_id,
    r.libelle as route_name,
    SUM(ds.total_ht) as ca_total,
    SUM(ds.nb_orders) as nb_cmd,
    AVG(ds.total_ht) as panier_moyen
FROM dashboard_sales ds
JOIN routes r ON r.route_id = ds.route_id
WHERE ds.company_id = 1
  AND ds.date BETWEEN '2026-03-01' AND '2026-03-31'
GROUP BY r.route_id, r.libelle
ORDER BY ca_total DESC;

-- Top 10 articles du mois
SELECT
    a.article_id,
    a.libelle,
    SUM(dta.quantity_sold) as qte_vendue,
    SUM(dta.amount_ht) as ca_genere
FROM dashboard_top_articles dta
JOIN articles a ON a.article_id = dta.article_id
WHERE dta.company_id = 1
  AND dta.date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY a.article_id, a.libelle
ORDER BY qte_vendue DESC
LIMIT 10;
```

### 3.2 Index Stratégiques

```sql
-- Index composite pour requêtes date + route
CREATE INDEX idx_dashboard_sales_date_route
ON dashboard_sales(company_id, date, route_id);

-- Index pour top articles
CREATE INDEX idx_top_articles_qty
ON dashboard_top_articles(company_id, date, quantity_sold DESC);
```

---

## 4. Commandes de Backfill

### 4.1 Reconstruction Complète

```php
class RebuildSalesDashboard extends Command
{
    protected $signature = 'dashboard:rebuild-sales
                            {--date-from= : Date début (Y-m-d)}
                            {--date-to= : Date fin (Y-m-d)}
                            {--chunk=500 : Taille des lots}';

    public function handle(SalesDashboardProjector $projector): int
    {
        // 1. Vider les tables (si full rebuild)
        if (!$this->option('date-from')) {
            DB::table('dashboard_sales')->delete();
            DB::table('dashboard_top_articles')->delete();
        }

        // 2. Sélectionner les événements pertinents
        $query = DomainOutbox::whereIn('event_type', [
            'MovementValidated',
            'StopVisited',
        ])->where('status', 'processed');

        if ($this->option('date-from')) {
            $query->whereDate('created_at', '>=', $this->option('date-from'));
        }

        // 3. Traiter par lots pour limiter la mémoire
        $count = 0;
        $query->orderBy('id')->chunk($this->option('chunk'), function ($events) use ($projector, &$count) {
            foreach ($events as $event) {
                $projector->process($event);
                $count++;
            }
        });

        $this->info("{$count} événements retraités.");

        // 4. Rapport final
        $stats = [
            'sales_records' => DB::table('dashboard_sales')->count(),
            'top_articles_records' => DB::table('dashboard_top_articles')->count(),
            'latest_record' => DB::table('dashboard_sales')->max('updated_at'),
        ];

        $this->table(['Métrique', 'Valeur'], [
            ['Enregistrements ventes', $stats['sales_records']],
            ['Enregistrements articles', $stats['top_articles_records']],
            ['Dernier update', $stats['latest_record']],
        ]);

        return Command::SUCCESS;
    }
}
```

---

## 5. Dashboards Visualisation

### 5.1 Structure des Données pour Frontend

```php
// Endpoint API pour dashboard
class DashboardController extends Controller
{
    public function getDailyMetrics(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $today = now()->toDateString();

        $todayData = DB::table('dashboard_sales')
            ->where('company_id', $companyId)
            ->where('date', $today)
            ->selectRaw('
                SUM(total_ht) as ca,
                SUM(nb_orders) as orders,
                SUM(nb_clients_visited) as visits
            ')
            ->first();

        $yesterdayData = DB::table('dashboard_sales')
            ->where('company_id', $companyId)
            ->where('date', now()->subDay()->toDateString())
            ->selectRaw('SUM(total_ht) as ca')
            ->first();

        $topArticles = DB::table('dashboard_top_articles')
            ->where('company_id', $companyId)
            ->where('date', $today)
            ->orderBy('quantity_sold', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'today' => [
                'revenue' => round($todayData->ca ?? 0, 2),
                'orders' => $todayData->orders ?? 0,
                'visits' => $todayData->visits ?? 0,
                'conversion_rate' => $todayData->visits > 0
                    ? round(($todayData->orders / $todayData->visits) * 100, 1)
                    : 0,
            ],
            'growth' => [
                'revenue_percent' => $yesterdayData->ca > 0
                    ? round((($todayData->ca - $yesterdayData->ca) / $yesterdayData->ca) * 100, 1)
                    : 0,
            ],
            'top_articles' => $topArticles->map(fn($item) => [
                'article_id' => $item->article_id,
                'quantity' => $item->quantity_sold,
                'revenue' => $item->amount_ht,
            ]),
        ]);
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