# Infrastructure Core - Services et Composants

## 1. Event Store Service

### 1.1 Responsabilités

Le `EventStoreService` est le cœur du système d'événementiel. Il gère :

- **Stockage immuable** des événements de domaine
- **Chaînage cryptographique** (Merkle tree) pour l'intégrité
- **Séquencement** strict par agrégat
- **Publication** vers le bus d'événements

### 1.2 API du Service

```php
namespace App\Services;

class EventStoreService
{
    /**
     * Ajoute un événement au store avec chaînage cryptographique.
     *
     * @param string $aggregateType Type d'agrégat (ex: 'Movement')
     * @param int $aggregateId Identifiant de l'agrégat
     * @param string $eventType Type d'événement (ex: 'MovementValidated')
     * @param array $payload Données de l'événement
     * @param string $companyId ID de l'entreprise (pour sharding)
     * @return StoredEvent L'événement stocké avec son hash
     */
    public function append(
        string $aggregateType,
        int $aggregateId,
        string $eventType,
        array $payload,
        string $companyId
    ): StoredEvent;

    /**
     * Reconstitue un agrégat depuis ses événements.
     *
     * @param string $aggregateType
     * @param int $aggregateId
     * @param int|null $upToVersion Version cible (null = dernière)
     * @return array Liste des événements
     */
    public function getEvents(
        string $aggregateType,
        int $aggregateId,
        ?int $upToVersion = null
    ): array;

    /**
     * Vérifie l'intégrité de la chaîne d'événements.
     *
     * @param string $aggregateType
     * @param int $aggregateId
     * @return bool True si la chaîne est valide
     */
    public function verifyIntegrity(
        string $aggregateType,
        int $aggregateId
    ): bool;
}
```

### 1.3 Implémentation du Chaînage

```php
private function calculateHash(StoredEvent $event, ?string $previousHash): string
{
    $data = [
        'aggregate_type' => $event->aggregate_type,
        'aggregate_id' => $event->aggregate_id,
        'sequence' => $event->sequence,
        'event_type' => $event->event_type,
        'payload' => json_encode($event->payload),
        'previous_hash' => $previousHash,
        'timestamp' => $event->created_at->toIso8601String(),
    ];

    return hash('sha256', json_encode($data));
}

private function signHash(string $hash, string $privateKey): string
{
    openssl_sign($hash, $signature, $privateKey, OPENSSL_ALGO_SHA256);
    return base64_encode($signature);
}
```

---

## 2. Outbox Service

### 2.1 Principe de Fonctionnement

L'Outbox garantit que les événements sont publiés **exactement une fois** :

```
┌─────────────────────────────────────────────────────────┐
│  TRANSACTION SQL ATOMIQUE                               │
├─────────────────────────────────────────────────────────┤
│  BEGIN;                                                 │
│    INSERT INTO event_store (...) → ID = 12345          │
│    INSERT INTO domain_outbox (...)                     │
│      - event_store_id: 12345                          │
│      - status: 'pending'                              │
│      - payload: {...}                                 │
│  COMMIT;                                                │
└─────────────────────────────────────────────────────────┘
          │
          ▼
    ┌─────────────┐
    │   Worker    │  ◀─── Cron / Queue / Listener
    │   Poll      │
    └──────┬──────┘
           │
           ▼
    ┌─────────────┐
    │ Process     │
    │ 1. SELECT   │  ◀── pending events
    │ 2. LOCK     │  ◀── FOR UPDATE SKIP LOCKED
    │ 3. Dispatch │  ◀── To projectors
    │ 4. UPDATE   │  ◀── status = 'processed'
    └─────────────┘
```

### 2.2 Gestion des Échecs

```php
public function processOutbox(): void
{
    $events = DomainOutbox::where('status', 'pending')
        ->orWhere(function ($q) {
            $q->where('status', 'failed')
              ->where('retry_count', '<', 5)
              ->where('updated_at', '<', now()->subMinutes(5)); // Backoff
        })
        ->orderBy('id')
        ->limit(100)
        ->get();

    foreach ($events as $event) {
        try {
            $this->dispatcher->dispatch($event);
            $event->update([
                'status' => 'processed',
                'processed_at' => now(),
            ]);
        } catch (\Exception $e) {
            $event->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'retry_count' => $event->retry_count + 1,
            ]);

            if ($event->retry_count >= 5) {
                $this->alerting->notifyCritical(
                    "Outbox event {$event->id} failed permanently"
                );
            }
        }
    }
}
```

---

## 3. Sequence Service

### 3.1 Garanties de Séquencement

Le service garantit que chaque événement d'un agrégat reçoit un numéro de séquence unique et croissant.

```php
class SequenceService
{
    /**
     * Génère le prochain numéro de séquence pour un agrégat.
     *
     * Utilise un SELECT ... FOR UPDATE pour éviter les race conditions
     * entre transactions concurrentes.
     */
    public function next(string $aggregateType, int $aggregateId): int
    {
        return DB::transaction(function () use ($aggregateType, $aggregateId) {
            // Gap locking empêche d'autres transactions de modifier
            // cette ligne jusqu'à la fin de la transaction
            $sequence = DB::table('aggregate_sequences')
                ->where('aggregate_type', $aggregateType)
                ->where('aggregate_id', $aggregateId)
                ->lockForUpdate()
                ->value('sequence') ?? 0;

            $next = $sequence + 1;

            DB::table('aggregate_sequences')->updateOrInsert(
                [
                    'aggregate_type' => $aggregateType,
                    'aggregate_id' => $aggregateId,
                ],
                [
                    'sequence' => $next,
                    'updated_at' => now(),
                ]
            );

            return $next;
        });
    }

    /**
     * Récupère la séquence actuelle sans l'incrémenter.
     */
    public function current(string $aggregateType, int $aggregateId): int
    {
        return DB::table('aggregate_sequences')
            ->where('aggregate_type', $aggregateType)
            ->where('aggregate_id', $aggregateId)
            ->value('sequence') ?? 0;
    }

    /**
     * Réinitialise une séquence (utile pour les tests).
     */
    public function reset(string $aggregateType, int $aggregateId): void
    {
        DB::table('aggregate_sequences')
            ->where('aggregate_type', $aggregateType)
            ->where('aggregate_id', $aggregateId)
            ->delete();
    }
}
```

---

## 4. Stratégies de Performance & Résilience

### 4.1 Snapshotting d'Agrégat

Pour éviter de rejouer des milliers d'événements, les agrégats à haut volume (ex: `StockAggregate`) utilisent des snapshots.

**Fonctionnement :**
1. Tous les N événements (ex: 100), l'état actuel de l'agrégat est sérialisé.
2. Le snapshot est stocké dans `projection_snapshots` avec le `last_event_id`.
3. Lors du chargement, on récupère le dernier snapshot et on ne rejoue que les événements postérieurs.

### 4.2 Séquences à Haut Débit (Redis)

Pour les agrégats subissant une forte contention sur `SELECT ... FOR UPDATE`, le système peut basculer sur un générateur distribué.

- **Redis INCR** : Utilisation d'incréments atomiques Redis pour générer les IDs de séquence.
- **Backfill SQL** : Les séquences sont synchronisées périodiquement vers MySQL pour la persistance long-terme.

### 4.3 Dead Letter Handling (DLQ)

Si un événement échoue après 5 tentatives dans l'Outbox :
1. Il est marqué comme `failed` de manière permanente.
2. Une alerte critique est envoyée.
3. L'événement peut être déplacé vers une table `failed_events_log` pour analyse manuelle et rejeu forcé par un administrateur via `php artisan outbox:retry {id}`.

---

## 4. Projector Base Class

### 4.1 Idempotence Exacte (Exactly-Once)

Tous les projecteurs héritent de la classe `App\Services\Projector` qui implémente une protection contre le re-traitement au niveau infrastructure via la table `projector_processed_events`.

```php
abstract class Projector
{
    public function handle(DomainEvent $event): void
    {
        DB::transaction(function () use ($event) {
            try {
                // 1. Claim Idempotency
                DB::table('projector_processed_events')->insert([
                    'tenant_id' => $event->tenant_id,
                    'projector_id' => static::class,
                    'event_id' => $event->id,
                    'processed_at' => now(),
                ]);
            } catch (QueryException $e) {
                if ($e->getCode() === '23000') return; // Déjà traité
                throw $e;
            }

            // 2. Apply Side Effects
            $this->applyEvent($event->payload, $event);
        });
    }
}
```

### 4.2 Protection Secondaire (Out-of-Order)

En plus du claim global, les projecteurs utilisent le `last_event_id` sur les tables de lecture pour garantir l'ordre causal :

```php
class StockBalanceProjector extends Projector
{
    public function handleStockEntered(array $payload, DomainOutbox $event): void
    {
        DB::table('stock_balances')
            ->where('article_id', $payload['article_id'])
            ->where('last_event_id', '<', $event->id) // ← Protection séquencement
            ->update([
                'quantity' => DB::raw("quantity + {$payload['quantity']}"),
                'last_event_id' => $event->id,
            ]);
    }
}
```

---

## 5. Reservation Service

### 5.1 Gestion des Réservations Stock

Service crucial pour éviter les surventes :

```php
class ReservationService
{
    /**
     * Réserve du stock pour un mouvement validé.
     * Le stock est déduit de la quantité disponible mais pas encore
     * du stock physique (livraison future).
     */
    public function reserveStock(
        int $companyId,
        int $articleId,
        float $quantity,
        int $movementId
    ): void {
        DB::transaction(function () use ($companyId, $articleId, $quantity, $movementId) {
            // Lock pessimiste sur la ligne stock
            $stock = DB::table('stock_balances')
                ->where('company_id', $companyId)
                ->where('article_id', $articleId)
                ->lockForUpdate()
                ->first();

            $available = $stock->quantity - $stock->reserved_quantity;

            if ($available < $quantity) {
                throw new InsufficientStockException(
                    "Stock insuffisant: demandé {$quantity}, disponible {$available}"
                );
            }

            // Mise à jour atomique
            DB::table('stock_balances')
                ->where('company_id', $companyId)
                ->where('article_id', $articleId)
                ->update([
                    'reserved_quantity' => DB::raw("reserved_quantity + {$quantity}"),
                    'updated_at' => now(),
                ]);

            // Historique de la réservation
            DB::table('stock_reservations')->insert([
                'company_id' => $companyId,
                'article_id' => $articleId,
                'movement_id' => $movementId,
                'quantity' => $quantity,
                'status' => 'active',
                'created_at' => now(),
            ]);
        });
    }

    /**
     * Libère une réservation (annulation de commande).
     */
    public function releaseReservation(int $companyId, int $movementId): void
    {
        $reservations = DB::table('stock_reservations')
            ->where('company_id', $companyId)
            ->where('movement_id', $movementId)
            ->where('status', 'active')
            ->get();

        foreach ($reservations as $res) {
            DB::table('stock_balances')
                ->where('company_id', $companyId)
                ->where('article_id', $res->article_id)
                ->update([
                    'reserved_quantity' => DB::raw("reserved_quantity - {$res->quantity}"),
                    'updated_at' => now(),
                ]);

            DB::table('stock_reservations')
                ->where('id', $res->id)
                ->update(['status' => 'released']);
        }
    }

    /**
     * Convertit une réservation en consommation réelle (livraison).
     */
    public function consumeReservation(int $companyId, int $movementId): void
    {
        $reservations = DB::table('stock_reservations')
            ->where('company_id', $companyId)
            ->where('movement_id', $movementId)
            ->where('status', 'active')
            ->get();

        foreach ($reservations as $res) {
            DB::transaction(function () use ($companyId, $res) {
                // Décrémente à la fois la quantité et la réservation
                DB::table('stock_balances')
                    ->where('company_id', $companyId)
                    ->where('article_id', $res->article_id)
                    ->update([
                        'quantity' => DB::raw("quantity - {$res->quantity}"),
                        'reserved_quantity' => DB::raw("reserved_quantity - {$res->quantity}"),
                        'updated_at' => now(),
                    ]);

                DB::table('stock_reservations')
                    ->where('id', $res->id)
                    ->update(['status' => 'consumed']);
            });
        }
    }
}
```

---

## 6. Saga Orchestrator

### 6.1 Gestion des Processus Longs

Pour les opérations multi-étapes (commande → paiement → livraison) :

```php
class SagaOrchestrator
{
    /**
     * Démarre une saga de commande complète.
     */
    public function startOrderSaga(int $movementId, int $companyId): Saga
    {
        $saga = Saga::create([
            'type' => 'order_processing',
            'status' => 'started',
            'company_id' => $companyId,
            'payload' => ['movement_id' => $movementId],
        ]);

        // Étape 1: Validation du crédit
        $this->executeStep($saga, 'validate_credit', function () use ($movementId) {
            return $this->creditService->checkLimit($movementId);
        });

        // Étape 2: Réservation du stock
        $this->executeStep($saga, 'reserve_stock', function () use ($movementId) {
            return $this->reservationService->reserveForMovement($movementId);
        });

        // Étape 3: Validation du mouvement
        $this->executeStep($saga, 'validate_movement', function () use ($movementId, $companyId) {
            return $this->movementAggregate->validate($companyId);
        });

        return $saga;
    }

    /**
     * Exécute une étape avec compensation en cas d'échec.
     */
    private function executeStep(Saga $saga, string $stepName, callable $action): void
    {
        try {
            $result = $action();
            $saga->recordStep($stepName, 'success', $result);
        } catch (\Exception $e) {
            $saga->recordStep($stepName, 'failed', ['error' => $e->getMessage()]);
            $this->compensate($saga); // Rollback des étapes précédentes
            throw $e;
        }
    }

    /**
     * Compense les étapes réussies en cas d'échec.
     */
    private function compensate(Saga $saga): void
    {
        $steps = $saga->steps()
            ->where('status', 'success')
            ->orderByDesc('sequence')
            ->get();

        foreach ($steps as $step) {
            $compensator = "compensate{$step->name}";
            if (method_exists($this, $compensator)) {
                $this->$compensator($step->payload);
            }
        }
    }
}
```

---

## 7. Stock Service

### 7.1 Multi-Dépôt et Transferts

```php
class StockService
{
    /**
     * Transfert de stock entre dépôts avec double-écriture atomique.
     */
    public function transferStock(
        int $companyId,
        int $articleId,
        float $quantity,
        int $sourceDepotId,
        int $targetDepotId
    ): void {
        DB::transaction(function () use ($companyId, $articleId, $quantity, $sourceDepotId, $targetDepotId) {
            // Vérification stock source
            $sourceStock = DB::table('article_unite_depot')
                ->where('company_id', $companyId)
                ->where('article_id', $articleId)
                ->where('depot_id', $sourceDepotId)
                ->lockForUpdate()
                ->first();

            if (!$sourceStock || $sourceStock->stock_theorique < $quantity) {
                throw new InsufficientStockException(
                    "Stock insuffisant dans le dépôt source {$sourceDepotId}"
                );
            }

            // Décrémentation source
            DB::table('article_unite_depot')
                ->where('id', $sourceStock->id)
                ->update([
                    'stock_theorique' => DB::raw("stock_theorique - {$quantity}"),
                    'updated_at' => now(),
                ]);

            // Incrémentation cible (insert ou update)
            $targetStock = DB::table('article_unite_depot')
                ->where('company_id', $companyId)
                ->where('article_id', $articleId)
                ->where('depot_id', $targetDepotId)
                ->first();

            if ($targetStock) {
                DB::table('article_unite_depot')
                    ->where('id', $targetStock->id)
                    ->update([
                        'stock_theorique' => DB::raw("stock_theorique + {$quantity}"),
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('article_unite_depot')->insert([
                    'company_id' => $companyId,
                    'article_id' => $articleId,
                    'depot_id' => $targetDepotId,
                    'stock_theorique' => $quantity,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Émission événement
            event(new StockTransferred(
                companyId: $companyId,
                articleId: $articleId,
                quantity: $quantity,
                sourceDepotId: $sourceDepotId,
                targetDepotId: $targetDepotId
            ));
        });
    }

    /**
     * Ajustement de stock (inventaire physique).
     */
    public function adjustStock(
        int $companyId,
        int $articleId,
        int $depotId,
        float $actualQuantity,
        string $reason
    ): void {
        $current = DB::table('article_unite_depot')
            ->where('company_id', $companyId)
            ->where('article_id', $articleId)
            ->where('depot_id', $depotId)
            ->first();

        $adjustment = $actualQuantity - ($current->stock_theorique ?? 0);

        DB::table('article_unite_depot')->updateOrInsert(
            [
                'company_id' => $companyId,
                'article_id' => $articleId,
                'depot_id' => $depotId,
            ],
            [
                'stock_theorique' => $actualQuantity,
                'updated_at' => now(),
            ]
        );

        // Audit trail
        DB::table('stock_adjustments')->insert([
            'company_id' => $companyId,
            'article_id' => $articleId,
            'depot_id' => $depotId,
            'previous_quantity' => $current->stock_theorique ?? 0,
            'new_quantity' => $actualQuantity,
            'adjustment' => $adjustment,
            'reason' => $reason,
            'created_at' => now(),
        ]);

        event(new StockAdjusted(
            companyId: $companyId,
            articleId: $articleId,
            depotId: $depotId,
            previousQuantity: $current->stock_theorique ?? 0,
            actualQuantity: $actualQuantity,
            reason: $reason
        ));
    }
}
```

---

## 8. Audit Service

### 8.1 Traçabilité Complète

```php
class AuditService
{
    /**
     * Log tout événement métier avec contexte.
     */
    public function logEvent(
        string $eventType,
        array $payload,
        ?string $userId = null
    ): AuditLog {
        return AuditLog::create([
            'event_type' => $eventType,
            'payload' => $payload,
            'user_id' => $userId ?? auth()-&gt;id(),
            'ip_address' => request()-&gt;ip(),
            'user_agent' => request()-&gt;userAgent(),
            'session_id' => session()-&gt;getId(),
            'created_at' => now(),
        ]);
    }

    /**
     * Récupère l'historique complet d'un agrégat.
     */
    public function getAuditTrail(
        string $aggregateType,
        int $aggregateId
    ): array {
        // Événements du domaine
        $domainEvents = DB::table('event_store')
            -&gt;where('aggregate_type', $aggregateType)
            -&gt;where('aggregate_id', $aggregateId)
            -&gt;orderBy('sequence')
            -&gt;get();

        // Actions utilisateur
        $auditLogs = DB::table('audit_logs')
            -&gt;where('payload-&gt;aggregate_type', $aggregateType)
            -&gt;where('payload-&gt;aggregate_id', $aggregateId)
            -&gt;orderBy('created_at')
            -&gt;get();

        return [
            'domain_events' =&gt; $domainEvents,
            'user_actions' =&gt; $auditLogs,
            'timeline' =&gt; $this-&gt;mergeTimeline($domainEvents, $auditLogs),
        ];
    }

    /**
     * Vérification d'intégrité des logs.
     */
    public function verifyIntegrity(\DateTime $since): array
    {
        $anomalies = [];

        $events = DB::table('event_store')
            -&gt;where('created_at', '&gt;=', $since)
            -&gt;orderBy('id')
            -&gt;get();

        $previousHash = null;
        foreach ($events as $event) {
            // Vérifier le chaînage
            if ($event-&gt;previous_hash !== $previousHash) {
                $anomalies[] = [
                    'type' =&gt; 'broken_chain',
                    'event_id' =&gt; $event-&gt;id,
                    'expected_previous' =&gt; $previousHash,
                    'actual_previous' =&gt; $event-&gt;previous_hash,
                ];
            }

            // Vérifier la signature
            if (!$this-&gt;verifySignature($event)) {
                $anomalies[] = [
                    'type' =&gt; 'invalid_signature',
                    'event_id' =&gt; $event-&gt;id,
                ];
            }

            $previousHash = $this-&gt;calculateHash($event);
        }

        return $anomalies;
    }
}
```

---

## 9. Commandes Artisan Core

### 9.1 Référence des Commandes

| Commande               | Description                    | Usage                    |
| ---------------------- | ------------------------------ | ------------------------ |
| `outbox:process`       | Traite les événements pending  | Cron toutes les minutes  |
| `projection:rebuild`   | Reconstruit une projection     | Après mise à jour schéma |
| `event-store:verify`   | Vérifie la chaîne d'intégrité  | Hebdomadaire             |
| `event-store:backfill` | Migre données legacy           | Une fois                 |
| `bootstrap:system`     | Initialise nouvelle entreprise | Onboarding               |

### 9.2 Exemple : Vérification d'Intégrité

```php
class VerifyEventIntegrity extends Command
{
    protected $signature = 'event-store:verify {--since=24h}';

    public function handle(AuditService $audit): int
    {
        $since = now()->sub($this->option('since'));

        $this->info("Vérification depuis {$since}...");

        $anomalies = $audit->verifyIntegrity($since);

        if (empty($anomalies)) {
            $this->info('✅ Tous les événements sont intègres.');
            return Command::SUCCESS;
        }

        $this->error("⚠️  " . count($anomalies) . " anomalie(s) détectée(s) !");

        foreach ($anomalies as $anomaly) {
            $this->line("- Event #{$anomaly['event_id']}: {$anomaly['type']}");
        }

        return Command::FAILURE;
    }
}
```

---

## 10. Configuration

### 10.1 Variables d'Environnement

```env
# Event Store
EVENT_STORE_PARTITIONS=16
EVENT_STORE_VERIFY_INTEGRITY=true

# Outbox
OUTBOX_BATCH_SIZE=100
OUTBOX_MAX_RETRIES=5
OUTBOX_BACKOFF_MINUTES=5

# Projections
PROJECTION_CACHE_TTL=3600
PROJECTION_SNAPSHOT_ENABLED=true

# Séquences
SEQUENCE_GAP_LOCK_TIMEOUT=10

# Réservations
RESERVATION_EXPIRY_HOURS=48
STOCK_NEGATIVE_ALLOWED=false
```

### 10.2 Monitoring

Métriques clés à surveiller :

- `event_store.insert.latency` : Temps d'écriture
- `outbox.pending.count` : Taille de la file
- `projection.lag` : Décalage entre event store et projections
- `sequence.contention` : Nombre de transactions en attente
- `reservation.conflict` : Tentatives de réservation échouées

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
