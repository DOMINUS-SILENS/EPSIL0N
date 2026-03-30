# Documentation Technique - SFA God-Level ERP

## Vue d'Ensemble

Le SFA (Sales Force Automation) ERP est une plateforme de gestion commerciale de niveau entreprise construite sur une architecture **Event-Sourced CQRS** (Command Query Responsibility Segregation). Cette approche garantit une scalabilité infinie, une traçabilité totale des opérations, et une résilience maximale.

---

## 1. Architecture Événementielle (Event Sourcing)

### 1.1 Principe Fondamental

Contrairement aux architectures traditionnelles où seul l'état final est stocké, l'Event Sourcing conserve **chaque changement d'état sous forme d'événement immuable**.

```
┌─────────────────────────────────────────────────────────────┐
│                    ÉTAT TRADITIONNEL                      │
├─────────────────────────────────────────────────────────────┤
│  Commande #123 → {status: "delivered", total: 1500€}        │
│                                                             │
│  ❌ On perd l'historique : quand créée ? Par qui ?         │
│  ❌ Combien de fois modifiée ? Annulation possible ?       │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│                    EVENT SOURCING                         │
├─────────────────────────────────────────────────────────────┤
│  Commande #123 événements :                                 │
│  1. CommandeCreated(total: 1500€) → 08:30                 │
│  2. CommandeValidated(payment_ok) → 09:15                 │
│  3. CommandeShipped(tracking: XYZ) → 14:00                │
│  4. CommandeDelivered(signé: Dupont) → 16:45                │
│                                                             │
│  ✅ Audit complet                                           │
│  ✅ Reconstruction d'état à n'importe quel moment         │
│  ✅ Compensation/annulation possible                      │
└─────────────────────────────────────────────────────────────┘
```

### 1.2 Merkle Tree et Intégrité Cryptographique

Chaque événement est chaîné avec un hash du précédent, créant une blockchain interne vérifiable.

```php
// Structure d'un événement signé
event_id: 12345
aggregate_type: "Movement"
aggregate_id: 456
sequence: 5
event_type: "MovementValidated"
payload: {encrypted/signed data}
previous_event_hash: "a3f2c8...b2e1"  // Hash SHA-256 de l'événement 12344
signature: "RSA-SHA256...signature"
created_at: "2026-03-22 14:30:00"
```

**Bénéfices :**
- Détection immédiate de corruption de données
- Preuve d'intégrité pour audits réglementaires
- Non-répudiation des transactions

---

### 1.3 Schémas d'Événements et Versionnage

Pour garantir la pérennité des données, chaque événement suit un schéma strict et un cycle de vie contrôlé.

**Formalisation (DTOs & Attributs) :**
Les payloads d'événements sont encapsulés dans des classes PHP 8 typées pour garantir la validation à l'écriture.

```php
#[EventType('MovementValidated')]
class MovementValidatedEvent extends DomainEvent
{
    public function __construct(
        public readonly int $movementId,
        public readonly int $companyId,
        public readonly float $totalHt,
        public readonly array $lines,
        public readonly \DateTimeImmutable $occurredAt
    ) {}
}
```

**Cycle de Vie & Catalogage :**
- **Immuabilité** : Un événement publié ne peut JAMAIS être modifié.
- **Versioning** : Si une structure change, une nouvelle version (`v2`, `v3`) est créée. Les projecteurs doivent supporter la rétro-compatibilité ou déclencher un replay complet.
- **Catalogue d'Événements** : Un registre centralisé des types d'événements est maintenu pour faciliter l'intégration de nouveaux services.

---

## 2. Pattern CQRS (Command Query Responsibility Segregation)

### 2.1 Séparation des Chemins Lecture/Écriture

```
┌──────────────────────────────────────────────────────────────┐
│                        COMMAND SIDE                          │
│                      (Écriture/Modification)                 │
├──────────────────────────────────────────────────────────────┤
│                                                              │
│   Client ──▶ Controller ──▶ Aggregate ──▶ Domain Event       │
│                                    │                         │
│                                    ▼                         │
│                              Event Store                     │
│                              (Source de Vérité)              │
│                                    │                         │
│                                    ▼                         │
│                              Outbox Pattern                  │
│                                    │                         │
└────────────────────────────────────┼─────────────────────────┘
                                     │
                              Projection Dispatcher
                                     │
                        ┌────────────┼────────────┐
                        ▼            ▼            ▼
                   ┌────────┐  ┌─────────┐  ┌──────────┐
                   │Movement│  │ Stock   │  │ Dashboard│
                   │Projector│  │Projector│  │Projector │
                   └────┬───┘  └────┬────┘  └────┬─────┘
                        │            │            │
                        ▼            ▼            ▼
                   ┌────────┐  ┌─────────┐  ┌──────────┐
                   │mouvemts│  │stock_   │  │dashboard │
                   │ table  │  │balances │  │_sales    │
                   │(legacy)│  │ table   │  │ table    │
                   └────────┘  └─────────┘  └──────────┘
                        │            │            │
                        └────────────┴────────────┘
                                     │
                                     ▼
┌──────────────────────────────────────────────────────────────┐
│                         QUERY SIDE                           │
│                        (Lecture seule)                       │
├──────────────────────────────────────────────────────────────┤
│                                                              │
│   Client ──▶ GraphQL API ──▶ Projection Optimisée          │
│                                                              │
│   ✅ Latence O(1) - Requêtes ultra-rapides                  │
│   ✅ Tables optimisées pour lecture (index, partitions)     │
│   ✅ Pas de jointures complexes à runtime                   │
└──────────────────────────────────────────────────────────────┘
```

### 2.2 Flux d'une Commande

1. **Réception** : L'API reçoit une requête de validation de commande
2. **Chargement** : L'agrégat est reconstitué depuis ses événements historiques
3. **Validation** : L'agrégat vérifie les règles métier (crédit, stock disponible)
4. **Génération** : Un nouvel événement est créé (`MovementValidated`)
5. **Persistance** : L'événement est stocké dans l'Event Store
6. **Publication** : L'événement est publié dans l'Outbox
7. **Projection** : Les projecteurs mettent à jour les tables de lecture
8. **Réponse** : Le client reçoit une confirmation

---

## 3. Infrastructure Core

### 3.1 Event Store (Source de Vérité)

**Table : `event_store`**

| Colonne | Type | Description |
|---------|------|-------------|
| id | BIGINT PK | Identifiant unique séquentiel |
| aggregate_type | VARCHAR | Type d'agrégat (Movement, Mission, etc.) |
| aggregate_id | BIGINT | ID de l'agrégat |
| sequence | INT | Numéro de séquence dans l'agrégat |
| event_type | VARCHAR | Type d'événement (Class name) |
| payload | JSON | Données de l'événement |
| previous_hash | VARCHAR | Hash de l'événement précédent |
| signature | VARCHAR | Signature cryptographique |
| created_at | TIMESTAMP | Date de création |

**Sharding par entreprise** : La table est partitionnée par `company_id` pour isoler les données et permettre une scalabilité horizontale.

### 3.2 Domain Outbox Pattern

Le pattern Outbox garantit la cohérence entre l'Event Store et les projections.

```
┌────────────────────────────────────────────────────────┐
│              TRANSACTION ATOMIQUE                      │
├────────────────────────────────────────────────────────┤
│                                                        │
│   BEGIN TRANSACTION                                    │
│                                                        │
│   1. INSERT INTO event_store (...)                     │
│                                                        │
│   2. INSERT INTO domain_outbox (...)                   │
│      ├── event_id (référence)                          │
│      ├── status: 'pending'                               │
│      └── payload: copie de l'événement                 │
│                                                        │
│   COMMIT                                               │
│                                                        │
│   4. REDIS PUBLISH (Real-time trigger)                 │
│                                                        │
└────────────────────────────────────────────────────────┘
```

**Pourquoi l'Outbox ?**
- Atomicité : soit l'événement est écrit ET publié, soit rien
- Résilience : si le projecteur plante, l'événement reste en pending
- Replay possible : on peut retraiter les événements historiques

### 3.3 Sequence Service

Garantit l'unicité et l'ordre des séquences par agrégat.

```php
// Fonctionnement
$seq = $sequenceService->next('Movement', 123);
// Retourne 5 si le dernier événement de Movement#123 est sequence=4

// Implémentation optimisée avec gap locking
DB::transaction(function () {
    $current = DB::table('aggregate_sequences')
        ->where('aggregate_type', $type)
        ->where('aggregate_id', $id)
        ->lockForUpdate()
        ->first();

    $next = ($current->sequence ?? 0) + 1;

    DB::table('aggregate_sequences')->updateOrInsert(
        ['aggregate_type' => $type, 'aggregate_id' => $id],
        ['sequence' => $next]
    );

    return $next;
});
```

### 3.4 Projection Versioning

Les projections peuvent être reconstruites à tout moment grâce au versionnage.

```
┌────────────────────────────────────────────────────────┐
│              REBUILD STRATEGY                          │
├────────────────────────────────────────────────────────┤
│                                                        │
│   Version 1 : Création initiale                        │
│   Version 2 : Ajout colonne 'taxe_amount'              │
│   Version 3 : Modification calcul total               │
│                                                        │
│   Pour upgrader :                                      │
│   1. Incrémenter version dans ProjectionVersion       │
│   2. Vider la table de projection                      │
│   3. Replay tous les événements avec nouveau code     │
│                                                        │
└────────────────────────────────────────────────────────┘
```

---

## 4. Modèle de Données Physique

### 4.1 Event Store Tables

```sql
-- Événements principaux
CREATE TABLE event_store (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    aggregate_type VARCHAR(255) NOT NULL,
    aggregate_id BIGINT UNSIGNED NOT NULL,
    sequence INT UNSIGNED NOT NULL,
    event_type VARCHAR(255) NOT NULL,
    payload JSON NOT NULL,
    previous_hash VARCHAR(64),
    signature VARCHAR(512),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_sequence (company_id, aggregate_type, aggregate_id, sequence),
    INDEX idx_aggregate (company_id, aggregate_type, aggregate_id),
    INDEX idx_event_type (event_type),
    INDEX idx_created (created_at)
) PARTITION BY HASH(company_id) PARTITIONS 16;

-- Outbox pour projections
CREATE TABLE domain_outbox (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    event_store_id BIGINT UNSIGNED NOT NULL,
    aggregate_type VARCHAR(255) NOT NULL,
    aggregate_id BIGINT UNSIGNED NOT NULL,
    sequence INT UNSIGNED NOT NULL,
    event_type VARCHAR(255) NOT NULL,
    payload JSON NOT NULL,
    status ENUM('pending', 'processing', 'processed', 'failed') DEFAULT 'pending',
    error_message TEXT NULL,
    retry_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    INDEX idx_status (status, created_at),
    INDEX idx_aggregate (company_id, aggregate_type, aggregate_id),
    INDEX idx_event_type (event_type)
) PARTITION BY HASH(company_id) PARTITIONS 16;

-- Séquences par agrégat
CREATE TABLE aggregate_sequences (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    aggregate_type VARCHAR(255) NOT NULL,
    aggregate_id BIGINT UNSIGNED NOT NULL,
    sequence BIGINT UNSIGNED DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_aggregate (company_id, aggregate_type, aggregate_id)
);

-- Versions des projections
CREATE TABLE projection_versions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    projector_name VARCHAR(255) NOT NULL UNIQUE,
    version INT UNSIGNED DEFAULT 1,
    last_rebuild_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Snapshots pour rebuild rapide
CREATE TABLE projection_snapshots (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    projector_name VARCHAR(255) NOT NULL,
    aggregate_id BIGINT UNSIGNED NOT NULL,
    snapshot JSON NOT NULL,
    last_event_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_snapshot (company_id, projector_name, aggregate_id),
    INDEX idx_last_event (last_event_id)
) PARTITION BY HASH(company_id) PARTITIONS 16;
```

### 4.2 Tables de Projection Lecture

```sql
-- Projections mouvements (état actuel)
CREATE TABLE mouvements (
    company_id BIGINT UNSIGNED NOT NULL,
    mouvement_id BIGINT UNSIGNED NOT NULL,
    -- ... champs métier ...
    status ENUM('draft', 'validated', 'delivered', 'cancelled'),
    last_event_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (company_id, mouvement_id),
    INDEX idx_status (company_id, status),
    INDEX idx_last_event (last_event_id)
) PARTITION BY HASH(company_id) PARTITIONS 16;

-- Projections stock temps réel
CREATE TABLE stock_balances (
    company_id BIGINT UNSIGNED NOT NULL,
    article_id BIGINT UNSIGNED NOT NULL,
    quantity DECIMAL(15,4) DEFAULT 0,
    reserved_quantity DECIMAL(15,4) DEFAULT 0,
    last_event_id BIGINT UNSIGNED NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (company_id, article_id),
    INDEX idx_last_event (last_event_id)
) PARTITION BY HASH(company_id) PARTITIONS 16;
```

---

## 5. Architecture de Déploiement

### 5.1 Topologie Multi-Couches

```
┌─────────────────────────────────────────────────────────────┐
│                       LOAD BALANCER                         │
│                      (HAProxy / Nginx)                      │
└───────────────────────────┬───────────────────────────────────┘
                            │
           ┌────────────────┼────────────────┐
           │                │                │
    ┌──────▼──────┐  ┌──────▼──────┐  ┌──────▼──────┐
    │   Web 01    │  │   Web 02    │  │   Web 03    │
    │  (Laravel)  │  │  (Laravel)  │  │  (Laravel)  │
    └──────┬──────┘  └──────┬──────┘  └──────┬──────┘
           │                │                │
           └────────────────┼────────────────┘
                            │
    ┌───────────────────────┼───────────────────────┐
    │                       │                       │
    ▼                       ▼                       ▼
┌─────────┐          ┌─────────────┐          ┌──────────┐
│  MySQL  │          │    Redis    │          │  Queue   │
│ Primary │◄────────►│  Pub-Sub /  │          │ Workers  │
│    │    │          │  SSE Stream │          │  (Horizon)
│    ▼    │          └─────────────┘          └──────────┘
┌─────────┐
│ MySQL   │
│ Replica │  -- Lecture queries only
└─────────┘
```

### 5.2 Partitionnement Horizontal (Sharding)

Le système utilise le **Partitionnement par Entreprise** (Company Sharding) :

```
Company ID: 1 ──▶ Partition 1
Company ID: 2 ──▶ Partition 2
...
Company ID: 16 ──▶ Partition 16
Company ID: 17 ──▶ Partition 1 (cycle)

Avantages :
- Isolation totale des données par client
- Possibilité de migrer une entreprise sur un shard dédié
- Requêtes toujours routées vers une partition unique
```

---

## 6. Garanties du Système

### 6.1 Consistency (Cohérence)

- **Atomicité** : Transactions ACID pour écritures
- **Ordre** : Séquences garantissent l'ordre des événements par agrégat
- **Causalité** : Les événements causalement liés sont ordonnés

### 6.2 Availability (Disponibilité)

- **Read Replicas** : Requêtes de lecture routées vers replicas
- **Circuit Breaker** : Dégradation gracieuse si service indisponible
- **Retry Logic** : Rejeu automatique avec backoff exponentiel

### 6.3 Partition Tolerance (Tolérance aux Partitions)

- **Event Store** : Continue à accepter écritures même si projections lentes
- **Async Projections** : Pas de blocage sur mise à jour projections
- **Offline Mode** : Mobile peut fonctionner offline avec CRDT

### 6.4 Durability (Durabilité)

- **Triple Replication** : MySQL Group Replication
- **Snapshot Automatique** : Toutes les 24h
- **Backup Transaction Logs** : PITR (Point In Time Recovery)

---

## 7. Glossaire des Termes

| Terme | Définition |
|-------|------------|
| **Aggregate** | Entité métier qui encapsule la logique et émet des événements |
| **Event** | Changement d'état immuable représentant une action passée |
| **Projection** | Vue lecture optimisée construite à partir des événements |
| **Command** | Intention de modifier l'état du système |
| **Outbox** | Pattern garantissant la publication fiable des événements |
| **Merkle Tree** | Structure arborescente de hachage pour vérification d'intégrité |
| **CRDT** | Structure de données conflict-free pour synchronisation offline |
| **Vector Clock** | Horloge logique pour ordonnancement d'événements distribués |

---

## 8. Prochaines Sections

- [02-infrastructure-core.md](./02-infrastructure-core.md) - Services et composants techniques
- [03-domaines-metiers.md](./03-domaines-metiers.md) - Implémentation des 13 macro-domaines
- [04-projections-analytics.md](./04-projections-analytics.md) - Phase C : Dashboards et analytics
- [05-temps-reel-crdt.md](./05-temps-reel-crdt.md) - Phase D : Temps réel et sync mobile
- [06-api-graphql.md](./06-api-graphql.md) - Phase D : API de requête GraphQL
- [07-alerting-observabilite.md](./07-alerting-observabilite.md) - Phase D : Monitoring et alertes
- [08-deployment-operations.md](./08-deployment-operations.md) - Guide de déploiement et maintenance