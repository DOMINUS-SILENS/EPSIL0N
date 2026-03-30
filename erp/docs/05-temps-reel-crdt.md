# Phase D : Temps Réel & Sync Mobile (CRDT)

## Vue d'Ensemble

La Phase D ajoute trois capacités critiques :
1. **Dashboards temps réel** : Push WebSocket/SSE des mises à jour
2. **Sync offline-first** : CRDTs pour mobile sans connexion
3. **Observabilité** : Alertes métier et métriques système

---

## 1. Live Dashboard Service

### 1.1 Architecture Push

```
┌──────────────────────────────────────────────────────────────┐
│                   FLUX TEMPS RÉEL                            │
├──────────────────────────────────────────────────────────────┤
│                                                              │
│   ┌──────────────┐                                          │
│   │ Mobile App   │◀────────────────────────┐               │
│   │ Dashboard    │                         │               │
│   └──────────────┘                         │               │
│          ▲                                 │               │
│          │ SSE/WebSocket                   │               │
│          │                                 │               │
│   ┌──────┴───────┐              ┌───────────▼────────┐      │
│   │   Laravel    │              │  Projection        │      │
│   │   Backend    │              │  Dispatcher       │      │
│   └──────┬───────┘              └───────────┬────────┘      │
│          │                                  │               │
│          │ 3. Broadcast                     │               │
│          │                                  │               │
│   ┌──────▼───────┐              ┌───────────▼────────┐      │
│   │    Redis     │◀─────────────│  Domain Outbox    │      │
│   │   Pub/Sub    │              │  (Event Stream)   │      │
│   └──────────────┘              └───────────────────┘      │
│          ▲                                                   │
│          │ 2. Dispatch                                        │
│          │                                                   │
│   ┌──────┴───────┐                                          │
│   │ LiveDashboard│                                          │
│   │   Service    │                                          │
│   └──────────────┘                                          │
│          ▲                                                   │
│          │ 1. Projection Update                               │
│          │                                                   │
│   ┌──────┴───────┐                                          │
│   │SalesDashboard│                                          │
│   │  Projector   │                                          │
│   └──────────────┘                                          │
│                                                              │
└──────────────────────────────────────────────────────────────┘
```

### 1.2 Implémentation

```php
class LiveDashboardService
{
    /**
     * Publie une mise à jour du dashboard vers tous les clients connectés.
     */
    public function broadcastSalesUpdate(
        int $companyId,
        string $date,
        array $data
    ): void {
        $channel = "dashboard.company.{$companyId}.sales";

        $payload = [
            'type' => 'sales_updated',
            'date' => $date,
            'data' => $data,
            'timestamp' => now()->toIso8601String(),
        ];

        // Option 1: Redis Pub/Sub (scalable horizontal)
        Redis::publish($channel, json_encode($payload));

        // Option 2: Laravel Broadcasting (WebSocket)
        if (config('broadcasting.default') !== 'null') {
            broadcast(new DashboardUpdated($companyId, $payload));
        }

        // Option 3: SSE (Server-Sent Events)
        // Le client maintient une connexion HTTP longue
    }

    /**
     * Récupère le snapshot actuel pour initialisation.
     */
    public function getSnapshot(int $companyId, ?string $date = null): array
    {
        $date = $date ?? now()->toDateString();

        return [
            'sales' => DB::table('dashboard_sales')
                ->where('company_id', $companyId)
                ->where('date', $date)
                ->get(),
            'top_articles' => DB::table('dashboard_top_articles')
                ->where('company_id', $companyId)
                ->where('date', $date)
                ->orderBy('quantity_sold', 'desc')
                ->limit(10)
                ->get(),
            'generated_at' => now()->toIso8601String(),
        ];
    }
}

// Dans le Projector
class SalesDashboardProjector extends Projector
{
    public function __construct(
        protected LiveDashboardService $liveDashboard,
        // ...
    ) {}

    public function handleMovementValidated(array $payload, DomainOutbox $event): void
    {
        // ... UPSERT existant ...

        // Push temps réel
        $this->liveDashboard->broadcastSalesUpdate(
            companyId: $payload['companyId'],
            date: $payload['date'],
            data: [
                'total_ht' => $payload['totalHt'],
                'total_ttc' => $payload['totalTtc'],
                'route_id' => $payload['routeId'],
            ]
        );
    }
}
```

### 1.3 Client JavaScript (SSE)

```javascript
// Écoute des mises à jour temps réel
class DashboardRealtimeClient {
    constructor(companyId, onUpdate) {
        this.companyId = companyId;
        this.onUpdate = onUpdate;
        this.connect();
    }

    connect() {
        // Server-Sent Events pour compatibilité
        this.eventSource = new EventSource(
            `/api/dashboard/${this.companyId}/stream`
        );

        this.eventSource.onmessage = (event) => {
            const data = JSON.parse(event.data);
            this.onUpdate(data);
        };

        this.eventSource.onerror = () => {
            // Reconnexion automatique après délai
            setTimeout(() => this.connect(), 5000);
        };
    }

    disconnect() {
        this.eventSource?.close();
    }
}

// Utilisation
const client = new DashboardRealtimeClient(1, (update) => {
    if (update.type === 'sales_updated') {
        updateSalesWidget(update.data);
    }
});
```

---

## 2. CRDT : Conflict-Free Replicated Data Types

### 2.1 Pourquoi les CRDT ?

Les commerciaux travaillent souvent hors connexion (zones sans réseau). CRDT permet :
- **Offline-first** : Enregistrement local sans serveur
- **Sync automatique** : Dès la reconnexion
- **Sans conflits** : Fusion déterministe des modifications

```
┌──────────────────────────────────────────────────────────┐
│              SYNC OFFLINE-FIRST                          │
├──────────────────────────────────────────────────────────┤
│                                                          │
│   [Commercial Mobile]        [Serveur Central]          │
│                                                          │
│   1. Crée visite (offline)                                │
│   ┌─────────────┐                                         │
│   │ Visite #1   │                                         │
│   │ - Client A  │                                         │
│   │ - Notes     │                                         │
│   │ - Vector:   │                                         │
│   │   {m1:1}    │                                         │
│   └──────┬──────┘                                         │
│          │                                                │
│          │ 2. Connexion réseau                            │
│          │                                                │
│          ▼                                                │
│   ┌─────────────┐        3. Merge CRDT        ┌─────────┐ │
│   │ Visite #1   │────────────────────────────▶│ Visite  │ │
│   │ + Visite #2 │◄────────────────────────────│ Fusion  │ │
│   │ (concurrent)│        4. Convergence        └─────────┘ │
│   │ - Client B  │                                         │
│   │ - Vector:   │                                         │
│   │   {m1:2}    │                                         │
│   └─────────────┘                                         │
│                                                          │
│   Résultat: Les 2 visites sont préservées               │
│                                                          │
└──────────────────────────────────────────────────────────┘
```

### 2.2 Types CRDT Implémentés

```php
class MergeService
{
    // ===========================================
    // G-Counter : Compteur croissant (uniquement +)
    // Usage : Nombre de visites, commandes passées
    // ===========================================
    public function mergeGCounter(array $counterA, array $counterB): array
    {
        $result = [];
        $allKeys = array_unique(
            array_merge(array_keys($counterA), array_keys($counterB))
        );

        foreach ($allKeys as $key) {
            $valA = $counterA[$key] ?? 0;
            $valB = $counterB[$key] ?? 0;
            $result[$key] = max($valA, $valB);
        }

        return $result;
    }

    // Exemple: Compteur de visites
    // Device A: ['mobile1' => 5]  (5 visites enregistrées sur mobile1)
    // Device B: ['mobile2' => 3]  (3 visites enregistrées sur mobile2)
    // Fusion: ['mobile1' => 5, 'mobile2' => 3]
    // Total: 8 visites

    // ===========================================
    // PN-Counter : Compteur + et - (Stock)
    // Usage : Quantité stock (entrées/sorties)
    // ===========================================
    public function mergePNCounter(array $counterA, array $counterB): array
    {
        return [
            'p' => $this->mergeGCounter(
                $counterA['p'] ?? [],
                $counterB['p'] ?? []
            ),
            'n' => $this->mergeGCounter(
                $counterA['n'] ?? [],
                $counterB['n'] ?? []
            ),
        ];
    }

    public function calculatePNValue(array $pnCounter): int
    {
        $positive = array_sum($pnCounter['p'] ?? []);
        $negative = array_sum($pnCounter['n'] ?? []);
        return $positive - $negative;
    }

    // Exemple: Stock
    // Device A: {p: {m1: 10}, n: {m1: 0}}   (+10 entrées)
    // Device B: {p: {m2: 0}, n: {m2: 3}}    (-3 sorties)
    // Fusion: {p: {m1: 10, m2: 0}, n: {m1: 0, m2: 3}}
    // Valeur: 10 - 3 = 7 unités en stock

    // ===========================================
    // LWW-Register : Registre Last-Write-Wins
    // Usage : Dernière date de visite, notes
    // ===========================================
    public function mergeLWWRegister(array $regA, array $regB): array
    {
        $clockA = $regA['vector_clock'] ?? [];
        $clockB = $regB['vector_clock'] ?? [];

        $comparison = $this->compareVectorClocks($clockA, $clockB);

        if ($comparison === 1) {
            return $regA;  // A est plus récent
        } elseif ($comparison === -1) {
            return $regB;  // B est plus récent
        } else {
            // Concurrent : départage par ID replica
            $replicaA = $regA['replica_id'] ?? '';
            $replicaB = $regB['replica_id'] ?? '';
            return $replicaA > $replicaB ? $regA : $regB;
        }
    }

    // ===========================================
    // Vector Clock : Horloge logique distribuée
    // Usage : Ordre causal entre événements
    // ===========================================
    public function compareVectorClocks(array $clockA, array $clockB): int
    {
        $allNodes = array_unique(
            array_merge(array_keys($clockA), array_keys($clockB))
        );

        $aGreater = false;
        $bGreater = false;

        foreach ($allNodes as $node) {
            $valA = $clockA[$node] ?? 0;
            $valB = $clockB[$node] ?? 0;

            if ($valA > $valB) $aGreater = true;
            if ($valB > $valA) $bGreater = true;
        }

        if ($aGreater && !$bGreater) return 1;   // A > B
        if ($bGreater && !$aGreater) return -1; // B > A
        return 0; // Concurrents
    }

    public function incrementVectorClock(
        array $clock,
        string $nodeId
    ): array {
        $clock[$nodeId] = ($clock[$nodeId] ?? 0) + 1;
        return $clock;
    }
}
```

### 2.3 Sync Process

```php
class ProcessCrdtSync extends Command
{
    public function handle(MergeService $merge): int
    {
        // Récupère les opérations pending
        $operations = DB::table('crdt_operations')
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->get();

        foreach ($operations as $op) {
            DB::transaction(function () use ($merge, $op) {
                // Charge l'état actuel
                $current = DB::table('crdt_states')
                    ->where('replica_id', $op->replica_id)
                    ->where('entity_id', $op->entity_id)
                    ->first();

                $payload = json_decode($op->payload, true);
                $vectorClock = json_decode($op->vector_clock, true);

                if (!$current) {
                    // Premier état pour cette entité
                    DB::table('crdt_states')->insert([
                        'replica_id' => $op->replica_id,
                        'entity_id' => $op->entity_id,
                        'vector_clock' => json_encode($vectorClock),
                        'state' => json_encode($payload),
                    ]);
                } else {
                    // Merge avec état existant
                    $merged = match ($op->operation_type) {
                        'gc_inc' => [
                            'value' => $merge->mergeGCounter(
                                json_decode($current->state, true)['value'],
                                $payload['value']
                            )
                        ],
                        'lww_set' => $merge->mergeLWWRegister(
                            [
                                'value' => json_decode($current->state, true),
                                'vector_clock' => json_decode($current->vector_clock, true),
                                'replica_id' => $current->replica_id,
                            ],
                            [
                                'value' => $payload,
                                'vector_clock' => $vectorClock,
                                'replica_id' => $op->replica_id,
                            ]
                        ),
                        default => $payload,
                    };

                    // Met à jour l'état convergent
                    DB::table('crdt_states')
                        ->where('id', $current->id)
                        ->update([
                            'state' => json_encode($merged),
                            'vector_clock' => json_encode(
                                $merge->incrementVectorClock(
                                    json_decode($current->vector_clock, true),
                                    $op->replica_id
                                )
                            ),
                        ]);
                }

                // Marque comme traité
                DB::table('crdt_operations')
                    ->where('id', $op->id)
                    ->update(['status' => 'synced']);
            });
        }

        return Command::SUCCESS;
    }
}
```

### 2.4 Tables CRDT

```sql
-- État convergent par réplica
CREATE TABLE crdt_states (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    entity_type VARCHAR(255) NOT NULL, -- visit, order_line, etc.
    entity_id BIGINT UNSIGNED NOT NULL,
    replica_id VARCHAR(255) NOT NULL, -- UUID du device
    vector_clock JSON NOT NULL, -- {"device1": 5, "device2": 3}
    state JSON NOT NULL, -- Valeur CRDT
    timestamp TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (company_id, entity_type, entity_id, replica_id)
);

-- Queue d'opérations pending
CREATE TABLE crdt_operations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    operation_type VARCHAR(50) NOT NULL, -- gc_inc, lww_set, etc.
    entity_type VARCHAR(255) NOT NULL,
    entity_id BIGINT UNSIGNED NOT NULL,
    replica_id VARCHAR(255) NOT NULL,
    vector_clock JSON NOT NULL,
    payload JSON NOT NULL,
    status ENUM('pending', 'synced', 'conflict') DEFAULT 'pending',
    applied_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (company_id, status, created_at)
);
```

---

## 3. Migrations Schéma

```php
// Migration complète Phase D
return new class extends Migration {
    public function up(): void
    {
        // Alertes métier
        Schema::create('alert_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('metric_type');
            $table->json('conditions');
            $table->json('actions');
            $table->string('severity')->default('warning');
            $table->boolean('enabled')->default(true);
            $table->unsignedInteger('cooldown_minutes')->default(60);
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'enabled', 'last_triggered_at']);
        });

        // Historique des alertes
        Schema::create('alert_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->foreignId('alert_rule_id')->constrained()->onDelete('cascade');
            $table->decimal('metric_value', 15, 4);
            $table->decimal('threshold', 15, 4);
            $table->string('status');
            $table->json('context')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'created_at']);
        });

        // CRDT tables (voir section 2.4)
        // ...
    }
};
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