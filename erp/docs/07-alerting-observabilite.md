# Phase D : Alerting & Observabilité

## Vue d'Ensemble

Le système d'observabilité fournit :
- **Métriques métier** : Suivi des indicateurs critiques (stock, crédit, ventes)
- **Alertes temps réel** : Notification automatique sur seuils dépassés
- **Audit trail** : Traçabilité complète des actions
- **Health checks** : Surveillance de la santé du système

---

## 1. Alerting Service

### 1.1 Modèle de Règles d'Alerte

```php
class AlertRule extends Model
{
    protected $casts = [
        'conditions' => 'array',  // Seuils et critères
        'actions' => 'array',     // Canaux de notification
        'enabled' => 'boolean',
        'cooldown_minutes' => 'integer',
        'last_triggered_at' => 'datetime',
    ];

    /**
     * Types de métriques supportés.
     */
    const METRIC_TYPES = [
        'stock_balance' => 'Solde de stock',
        'stock_rupture' => 'Risque rupture',
        'credit_limit' => 'Limite de crédit',
        'credit_limit_breach' => 'Dépassement crédit',
        'sales_rate' => 'Taux de vente',
        'validation_rate' => 'Taux de validation',
        'delivery_rate' => 'Taux de livraison',
    ];

    /**
     * Opérateurs de comparaison.
     */
    const OPERATORS = [
        '>' => 'Supérieur à',
        '>=' => 'Supérieur ou égal',
        '<' => 'Inférieur à',
        '<=' => 'Inférieur ou égal',
        '==' => 'Égal à',
        '!=' => 'Différent de',
    ];

    /**
     * Vérifie si l'alerte est en période de cooldown.
     */
    public function isInCooldown(): bool
    {
        if (!$this->last_triggered_at) {
            return false;
        }

        return $this->last_triggered_at->diffInMinutes(now()) < $this->cooldown_minutes;
    }

    /**
     * Récupère la source de données pour cette métrique.
     */
    public function getMetricSource(): string
    {
        return match ($this->metric_type) {
            'stock_balance', 'stock_rupture' => 'stock_balances',
            'credit_limit', 'credit_limit_breach' => 'contacts',
            'sales_rate' => 'dashboard_sales',
            'validation_rate' => 'mouvements',
            'delivery_rate' => 'missions',
            default => throw new \InvalidArgumentException(
                "Type de métrique inconnu: {$this->metric_type}"
            ),
        };
    }
}
```

### 1.2 Configuration d'une Règle

```php
// Exemple: Alerte de rupture de stock
$rule = AlertRule::create([
    'company_id' => 1,
    'name' => 'Alerte Stock Bas',
    'description' => 'Notifie quand un article passe sous 10 unités',
    'metric_type' => 'stock_balance',
    'conditions' => [
        'column' => 'available_quantity',
        'operator' => '<',
        'threshold' => 10,
        'aggregation' => 'min',
    ],
    'actions' => [
        [
            'type' => 'log',
            'severity' => 'warning',
        ],
        [
            'type' => 'webhook',
            'url' => 'https://hooks.slack.com/services/...',
            'secret' => env('SLACK_WEBHOOK_SECRET'),
        ],
        [
            'type' => 'email',
            'recipients' => ['stock@entreprise.com'],
        ],
    ],
    'severity' => 'warning',
    'cooldown_minutes' => 60,
    'enabled' => true,
]);
```

### 1.3 Service d'Évaluation

```php
class AlertingService
{
    public function __construct(
        protected MetricsService $metrics,
        protected AuditService $audit,
    ) {}

    /**
     * Évalue toutes les règles actives d'une entreprise.
     */
    public function evaluateRules(int $companyId): array
    {
        $rules = AlertRule::active()
            ->forCompany($companyId)
            ->readyToEvaluate()
            ->get();

        $triggered = [];

        foreach ($rules as $rule) {
            if ($this->evaluateRule($rule)) {
                $this->triggerAlert($rule);
                $triggered[] = $rule;
            }
        }

        return $triggered;
    }

    /**
     * Évalue une règle spécifique.
     */
    public function evaluateRule(AlertRule $rule): bool
    {
        try {
            $conditions = $rule->conditions;

            // Calcule la valeur métrique
            $metricValue = match ($rule->metric_type) {
                'stock_rupture' => $this->calculateStockRuptureRisk($rule->company_id),
                'credit_limit_breach' => $this->calculateCreditBreachCount($rule->company_id),
                'validation_rate' => $this->calculateValidationRate($rule),
                default => $this->queryMetricValue($rule),
            };

            if ($metricValue === null) {
                return false;
            }

            // Évalue la condition
            return $this->compare(
                $metricValue,
                $conditions['operator'],
                $conditions['threshold']
            );

        } catch (\Exception $e) {
            \Log::error('Évaluation alerte échouée', [
                'rule_id' => $rule->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Calcule le pourcentage de risque rupture de stock.
     */
    protected function calculateStockRuptureRisk(int $companyId): float
    {
        $total = DB::table('stock_balances')
            ->where('company_id', $companyId)
            ->count();

        if ($total === 0) return 0;

        $atRisk = DB::table('stock_balances')
            ->where('company_id', $companyId)
            ->whereRaw('quantity <= reserved_quantity + 10')
            ->count();

        return ($atRisk / $total) * 100;
    }

    /**
     * Calcule le taux de validation des commandes.
     */
    protected function calculateValidationRate(AlertRule $rule): float
    {
        $hours = $rule->conditions['time_window_hours'] ?? 24;

        $total = DB::table('mouvements')
            ->where('company_id', $rule->company_id)
            ->where('created_at', '>=', now()->subHours($hours))
            ->count();

        if ($total === 0) return 0;

        $validated = DB::table('mouvements')
            ->where('company_id', $rule->company_id)
            ->where('status', 'validated')
            ->where('created_at', '>=', now()->subHours($hours))
            ->count();

        return ($validated / $total) * 100;
    }

    /**
     * Compare une valeur selon l'opérateur.
     */
    protected function compare(float $value, string $operator, float $threshold): bool
    {
        return match ($operator) {
            '>' => $value > $threshold,
            '>=' => $value >= $threshold,
            '<' => $value < $threshold,
            '<=' => $value <= $threshold,
            '==' => $value == $threshold,
            '!=' => $value != $threshold,
            default => false,
        };
    }

    /**
     * Déclenche les actions configurées.
     */
    protected function triggerAlert(AlertRule $rule): void
    {
        foreach ($rule->actions as $action) {
            match ($action['type']) {
                'log' => $this->sendLogAlert($rule, $action),
                'webhook' => $this->sendWebhookAlert($rule, $action),
                'email' => $this->sendEmailAlert($rule, $action),
                'slack' => $this->sendSlackAlert($rule, $action),
                default => \Log::warning("Type d'action inconnu: {$action['type']}"),
            };
        }

        // Met à jour la règle
        $rule->update([
            'last_triggered_at' => now(),
            'trigger_count' => DB::raw('trigger_count + 1'),
        ]);

        // Audit
        $this->audit->logEvent('alert_triggered', [
            'rule_id' => $rule->id,
            'rule_name' => $rule->name,
            'company_id' => $rule->company_id,
        ]);
    }

    protected function sendLogAlert(AlertRule $rule, array $action): void
    {
        \Log::warning("[ALERTE] {$rule->name}", [
            'severity' => $action['severity'] ?? 'warning',
            'description' => $rule->description,
            'company_id' => $rule->company_id,
        ]);
    }

    protected function sendWebhookAlert(AlertRule $rule, array $action): void
    {
        $payload = [
            'alert_name' => $rule->name,
            'description' => $rule->description,
            'severity' => $rule->severity,
            'timestamp' => now()->toIso8601String(),
            'company_id' => $rule->company_id,
        ];

        // Signature HMAC pour sécurité
        $signature = hash_hmac(
            'sha256',
            json_encode($payload),
            $action['secret'] ?? ''
        );

        dispatch(function () use ($action, $payload, $signature) {
            try {
                \Http::timeout(10)
                    ->withHeaders(['X-Alert-Signature' => $signature])
                    ->post($action['url'], $payload);
            } catch (\Exception $e) {
                \Log::error('Webhook alerte échoué', [
                    'url' => $action['url'],
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }
}
```

### 1.4 Commande d'Évaluation

```php
class EvaluateAlerts extends Command
{
    protected $signature = 'alerts:evaluate {--company= : ID entreprise spécifique}';

    public function handle(AlertingService $service): int
    {
        if ($companyId = $this->option('company')) {
            $triggered = $service->evaluateRules($companyId);
        } else {
            $companies = AlertRule::active()
                ->distinct('company_id')
                ->pluck('company_id');

            $triggered = [];
            foreach ($companies as $id) {
                $triggered = array_merge($triggered, $service->evaluateRules($id));
            }
        }

        foreach ($triggered as $alert) {
            $this->line("[{$alert->severity}] {$alert->name}");
        }

        return Command::SUCCESS;
    }
}
```

---

## 2. Intégration OpenTelemetry

### 2.1 Tracing Distribué

```php
class TracingMiddleware
{
    public function handle($request, Closure $next)
    {
        $tracer = app(TracerInterface::class);

        $span = $tracer->spanBuilder($request->route()->getName() ?? 'unknown')
            ->startSpan();

        $span->setAttribute('http.method', $request->method());
        $span->setAttribute('http.url', $request->url());
        $span->setAttribute('user.id', auth()->id());

        try {
            $response = $next($request);
            $span->setStatus(StatusCode::STATUS_OK);
            return $response;
        } catch (\Exception $e) {
            $span->recordException($e);
            $span->setStatus(StatusCode::STATUS_ERROR, $e->getMessage());
            throw $e;
        } finally {
            $span->end();
        }
    }
}
```

### 2.2 Métriques Personnalisées

```php
class MetricsService
{
    public function recordProjectionLag(string $projector, int $lagMs): void
    {
        // Envoie vers Prometheus/Grafana
        $this->histogram->record($lagMs, [
            'projector' => $projector,
        ]);
    }

    public function incrementEventProcessed(string $eventType): void
    {
        $this->counter->increment([
            'event_type' => $eventType,
        ]);
    }

    public function gaugeStockLevel(int $companyId, int $articleId, float $level): void
    {
        $this->gauge->set($level, [
            'company_id' => $companyId,
            'article_id' => $articleId,
        ]);
    }
}
```

---

## 3. Health Checks

### 3.1 Endpoints de Santé

```php
class HealthController extends Controller
{
    public function check(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'event_store' => $this->checkEventStore(),
            'outbox' => $this->checkOutbox(),
            'projections' => $this->checkProjections(),
            'redis' => $this->checkRedis(),
        ];

        $healthy = !in_array(false, array_column($checks, 'healthy'));

        return response()->json([
            'status' => $healthy ? 'healthy' : 'unhealthy',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }

    protected function checkOutbox(): array
    {
        $pending = DomainOutbox::where('status', 'pending')->count();
        $failed = DomainOutbox::where('status', 'failed')->where('retry_count', '>=', 5)->count();

        return [
            'healthy' => $pending < 1000 && $failed < 10,
            'pending_count' => $pending,
            'failed_count' => $failed,
            'message' => $failed > 0 ? "{$failed} événements en échec" : 'OK',
        ];
    }

    protected function checkProjections(): array
    {
        $lag = DB::selectOne("
            SELECT MAX(es.id - p.last_event_id) as lag
            FROM event_store es
            JOIN projection_versions p ON 1=1
            WHERE es.created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");

        return [
            'healthy' => ($lag->lag ?? 0) < 100,
            'lag_events' => $lag->lag ?? 0,
        ];
    }
}
```

---

## 4. Audit Trail

### 4.1 Enregistrement des Actions

```php
class AuditService
{
    public function logEvent(string $eventType, array $payload, ?string $userId = null): AuditLog
    {
        return AuditLog::create([
            'event_type' => $eventType,
            'payload' => $payload,
            'user_id' => $userId ?? auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId(),
            'created_at' => now(),
        ]);
    }

    public function getAuditTrail(string $aggregateType, int $aggregateId): array
    {
        return [
            'domain_events' => EventStore::where('aggregate_type', $aggregateType)
                ->where('aggregate_id', $aggregateId)
                ->orderBy('sequence')
                ->get(),
            'user_actions' => AuditLog::where('payload->aggregate_type', $aggregateType)
                ->where('payload->aggregate_id', $aggregateId)
                ->orderBy('created_at')
                ->get(),
        ];
    }
}
```

---

## 5. Seuils Critiques & Alerting (Best Practices)

Pour maintenir la santé de l'infrastructure Event-Sourced, les seuils suivants sont recommandés :

| Métrique | Seuil Warning | Seuil Critical | Action |
|----------|---------------|----------------|--------|
| `outbox.pending.count` | > 500 events | > 2000 events | Scaling workers |
| `projection.lag` | > 2 minutes | > 10 minutes | Check consumer health |
| `sequence.contention` | > 500ms wait | > 2s wait | Switch to Redis sequences |
| `outbox.failed.count` | 1 event | > 5 events | Manual intervention (DLQ) |
| `event_store.latency` | > 100ms | > 500ms | DB Optimization |

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