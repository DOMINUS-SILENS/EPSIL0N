# Architecture de la Logique Base de Données - God SFA CRM

## Vue d'Ensemble

Ce document décrit l'architecture logique de la base de données du système God SFA CRM. L'architecture suit un pattern orienté domaine avec séparation entre les tables métier (stock), les tables d'événements (Event Sourcing), et les tables de projection.

---

## 1. Couche Métier - Gestion de Stock (Core Domain)

### 1.1 Entités Principales

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│   entreprise    │────<│    article      │>────│  article_famille│
│   (Entreprise)  │     │   (Produit)     │     │   (Famille)     │
└─────────────────┘     └─────────────────┘     └─────────────────┘
         │                       │
         │                       │
         v                       v
┌─────────────────┐     ┌─────────────────┐
│     depot       │<────│article_ │
│   (Dépôt)       │     │    unite        │
│                 │     │  (Unité/PU)     │
└─────────────────┘     └─────────────────┘
         ▲                       │
         │                       │
         └───────────────────────┘
         │
         v
┌─────────────────────────┐
│ article_unite   │
│        _depot           │
│   (Stock par dépôt)    │
└─────────────────────────┘
```

### 1.2 Tables et Relations

| Table                   | Description                        | Clés                    | Relations                        |
| ----------------------- | ---------------------------------- | ----------------------- | -------------------------------- |
| **entreprise**          | Entité juridique principale        | `entreprise_id` PK      | Parent de tout                   |
| **article**             | Produits/articles                  | `article_id` PK         | FK → entreprise, famille, marque |
| **article_famille**     | Catégories de produits             | `article_famille_id` PK | Hiérarchique (nested set)        |
| **article_marque**      | Marques                            | `article_marque_id` PK  | FK → entreprise                  |
| **article_unite**       | Unités de mesure par article (PCU) | `article_unite_id` PK   | FK → article                     |
| **depot**               | Entrepôts/emplacements             | `depot_id` PK           | FK → entreprise, hiérarchique    |
| **article_unite_depot** | Stock par article/unité/dépôt      | Composite PK            | FK → article, unité, dépôt       |

### 1.3 Mouvements de Stock

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│   mouvement     │────<│ mouvement_ligne │>────│article_mouvement│
│  (Document)     │     │  (Ligne doc)    │     │(Mouvement réel) │
└─────────────────┘     └─────────────────┘     └─────────────────┘
                              │
                              v
                    ┌─────────────────┐
                    │article_mouvement│
                    │  _mouvement_    │
                    │   _ligne        │
                    │ (Lien Ligne-    │
                    │   Mouvement)    │
                    └─────────────────┘
```

| Table                                 | Description                            | Clés                      | Relations                               |
| ------------------------------------- | -------------------------------------- | ------------------------- | --------------------------------------- |
| **mouvement**                         | Documents (entrée/sortie/transfert)    | `mouvement_id` PK         | -                                       |
| **mouvement_ligne**                   | Lignes de document                     | `mouvement_ligne_id` PK   | FK → mouvement                          |
| **article_mouvement**                 | Mouvements réels de stock (108 champs) | `article_mouvement_id` PK | FK → article, dépôt source/destination  |
| **article_mouvement_mouvement_ligne** | Table de liaison                       | Composite PK              | FK → mouvement_ligne, article_mouvement |

### 1.4 Prix et Tarification

```
┌─────────────────┐     ┌─────────────────────────┐
│article_groupe_  │<────│article_unite_ │
│     prix        │     │article_groupe_prix     │
│ (Groupes tarif) │     │  (Prix par groupe)      │
└─────────────────┘     └─────────────────────────┘
```

---

## 2. Couche Événementielle (Event Sourcing)

### 2.1 Stockage des Événements

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│   event_store   │────<│event_shard_seq- │     │  event_schema   │
│  (Événements)   │     │   uences        │     │   (Schémas)     │
└─────────────────┘     └─────────────────┘     └─────────────────┘
         │
         v
┌─────────────────┐
│  audit_logs     │
│  (Audit chaîne  │
│   de hachage)   │
└─────────────────┘
```

| Table                     | Description                         | Clés                   | Usage            |
| ------------------------- | ----------------------------------- | ---------------------- | ---------------- |
| **event_store**           | Événements immuables avec signature | `event_id` PK, sharded | Source de vérité |
| **event_shard_sequences** | Séquences par shard                 | `shard_id` PK          | Ordre garanti    |
| **audit_logs**            | Chaîne de hachage pour audit        | `id` PK                | Intégrité        |
| **event_schema**          | Schémas de validation JSON          | `event_type` PK        | Validation       |

### 2.2 Outbox Pattern

```
┌─────────────────┐     ┌─────────────────┐
│  domain_outbox  │     │integration_outbox│
│(Événements du   │     │ (Événements     │
│   domaine)      │     │  d'intégration) │
└─────────────────┘     └─────────────────┘
```

| Table                  | Description                       | Usage                  |
| ---------------------- | --------------------------------- | ---------------------- |
| **domain_outbox**      | Événements métiers à publier      | Event Sourcing interne |
| **integration_outbox** | Événements pour systèmes externes | Anti-corruption layer  |

---

## 3. Couche Projection (Read Models)

### 3.1 Projections Métier

```
┌─────────────────────────┐
│customer_balance_projections│
│  (Solde client temps réel) │
└─────────────────────────┘
         ▲
         │
┌─────────────────────────┐
│    journal_entries      │
│  (Écritures comptables) │
└─────────────────────────┘
```

| Table                            | Description               | Source                   |
| -------------------------------- | ------------------------- | ------------------------ |
| **customer_balance_projections** | Solde client calculé      | Projection des écritures |
| **journal_entries**              | Écritures comptables      | Événements domaine       |
| **stock_balances**               | Balance de stock calculée | article_mouvement        |

### 3.2 Gestion des Projections

```
┌─────────────────┐     ┌─────────────────┐
│projection_vers- │     │projection_snap- │
│     ions        │     │    shots       │
│ (Versionning)   │     │  (Snapshots)   │
└─────────────────┘     └─────────────────┘
```

| Table                    | Description                  | Usage                   |
| ------------------------ | ---------------------------- | ----------------------- |
| **projection_versions**  | Versions des projections     | Contrôle de concurrence |
| **projection_snapshots** | Snapshots pour replay rapide | Optimisation            |

---

## 4. Couche Orchestration (Sagas)

```
┌─────────────────┐     ┌─────────────────┐
│      sagas      │────<│   saga_steps     │
│  (Orquestation) │     │  (Étapes saga)  │
└─────────────────┘     └─────────────────┘
```

| Table          | Description              | Clés                |
| -------------- | ------------------------ | ------------------- |
| **sagas**      | Instances de saga        | `id` PK             |
| **saga_steps** | Étapes avec compensation | `id` PK, FK → sagas |

---

## 5. Couche Réservation (Soft Reservations)

```
┌─────────────────────────┐     ┌─────────────────────────┐
│   credit_reservations   │     │   stock_reservations    │
│ (Réservation crédit)    │     │ (Réservation stock)     │
└─────────────────────────┘     └─────────────────────────┘
```

| Table                   | Description                  | Usage                 |
| ----------------------- | ---------------------------- | --------------------- |
| **credit_reservations** | Réservation de crédit client | Commandes             |
| **stock_reservations**  | Réservation de stock soft    | Allocation temporaire |

---

## 6. Couche Contrat et Validation

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│    contracts    │     │     intents     │     │    anomalies    │
│ (Contrats prédicats)│  │   (Intentions)   │     │ (Anomalies)     │
└─────────────────┘     └─────────────────┘     └─────────────────┘
```

| Table         | Description             | Usage                |
| ------------- | ----------------------- | -------------------- |
| **contracts** | Contrats avec prédicats | Validation métier    |
| **intents**   | Intensions utilisateur  | Contexte décisionnel |
| **anomalies** | Détections anomalies    | Monitoring           |

---

## 7. Séquences et Identifiants

```
┌─────────────────┐     ┌─────────────────┐
│aggregate_seq-   │     │  stock_moves    │
│   uences       │     │ (Mouvements     │
│ (IDs séquentiels)│     │  simplifiés)    │
└─────────────────┘     └─────────────────┘
```

| Table                   | Description                  | Clés                             |
| ----------------------- | ---------------------------- | -------------------------------- |
| **aggregate_sequences** | Séquences par agrégat        | `aggregate_type`, `aggregate_id` |
| **stock_moves**         | Log simplifié des mouvements | Auto-increment                   |

---

## 8. Vue Stock (View)

```
┌─────────────────┐
│  stock_quants   │
│ (Vue quantités  │
│  consolidées)   │
└─────────────────┘
```

**stock_quants**: Vue SQL consolidant les quantités par article/dépôt

---

## 9. Schéma Relationnel Complet

```
entreprise
├── article ──┬── article_famille
│             ├── article_marque
│             └── article_unite ──┬── article_unite_depot
│                                         │   └── depot
│                                         └── article_groupe_prix
│                                             └── article_unite_article_groupe_prix
│
├── mouvement ──┬── mouvement_ligne ──┬── article_mouvement_mouvement_ligne
│               │                     └── article_mouvement ──┬── article (FK)
│               │                                             ├── depot (source)
│               │                                             └── depot (destination)
│               └── balance_stock
│
├── customers ──┬── credit_reservations
│               └── journal_entries ──> customer_balance_projections
│
├── event_store ──┬── event_shard_sequences
│                 ├── audit_logs
│                 └── event_schema
│
├── domain_outbox
├── integration_outbox
├── sagas ── saga_steps
├── contracts
├── intents
├── anomalies
└── system_modes
```

---

## 10. Contraintes et Intégrité

### 10.1 Clés Primaires

| Table               | Type           | Champ(s)                           |
| ------------------- | -------------- | ---------------------------------- |
| entreprise          | Auto-incrément | `entreprise_id`                    |
| article             | Auto-incrément | `article_id`                       |
| article_unite       | Auto-incrément | `article_unite_id`                 |
| depot               | Auto-incrément | `depot_id`                         |
| article_mouvement   | Auto-incrément | `article_mouvement_id`             |
| article_unite_depot | Composite      | `(article_id, unite_id, depot_id)` |

### 10.2 Index Importants

```sql
-- Articles
INDEX (entreprise_id)
INDEX (article_famille_id)
INDEX (article_marque_id)
INDEX (active)

-- Mouvements
INDEX (article_id)
INDEX (depot_id_source)
INDEX (depot_id_destination)

-- Dépôts
INDEX (entreprise_id)
INDEX (depot_parent_id) -- Pour requêtes hiérarchiques
```

### 10.3 Foreign Keys

```sql
-- Article Unité
FOREIGN KEY (article_id) REFERENCES article(article_id)

-- Stock par dépôt
FOREIGN KEY (article_id) REFERENCES article(article_id)
FOREIGN KEY (unite_id) REFERENCES article_unite(article_unite_id)
FOREIGN KEY (depot_id) REFERENCES depot(depot_id)

-- Mouvements
FOREIGN KEY (article_id) REFERENCES article(article_id)
FOREIGN KEY (depot_id_source) REFERENCES depot(depot_id)
FOREIGN KEY (depot_id_destination) REFERENCES depot(depot_id)
```

---

## 11. Triggers et Procédures

### 11.1 Triggers article_mouvement

| Trigger                  | Événement | Action                    |
| ------------------------ | --------- | ------------------------- |
| `after_insert_mouvement` | INSERT    | Mise à jour balance_stock |
| `after_update_mouvement` | UPDATE    | Recalcul quantités        |
| `after_delete_mouvement` | DELETE    | Ajustement stock          |

### 11.2 Procédures Stockées

| Procédure                 | Description                 |
| ------------------------- | --------------------------- |
| `calculate_balance_stock` | Calcul complet des balances |

---

## 12. Patterns Architecturaux

### 12.1 Event Sourcing Pattern

```
Command → Event Store → Projection → Read Model
              ↓
         Outbox → External Systems
```

### 12.2 CQRS Pattern

```
┌─────────────┐     ┌─────────────┐
│  Commands   │     │   Queries   │
│  (Writes)   │     │   (Reads)   │
│             │     │             │
│ Event Store │     │ Projections │
│   + Saga    │     │    + Views  │
└─────────────┘     └─────────────┘
```

### 12.3 Saga Pattern

```
Saga: Order Processing
├── Step 1: Reserve Stock (Compensation: Release Stock)
├── Step 2: Reserve Credit (Compensation: Release Credit)
└── Step 3: Create Order (Compensation: Cancel Order)
```

---

## 13. Résumé des 45 Tables

| Domaine            | Tables                                                                                                                                                                                                                                                   | Nombre |
| ------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------ |
| **Core Stock**     | entreprise, article, article_famille, article_marque, article_unite, depot, article_unite_depot, article_mouvement, mouvement, mouvement_ligne, article_mouvement_mouvement_ligne, balance_stock, article_groupe_prix, article_unite_article_groupe_prix | 14     |
| **Event Sourcing** | event_store, event_shard_sequences, event_schema, audit_logs, domain_outbox, integration_outbox                                                                                                                                                          | 6      |
| **Projections**    | customer_balance_projections, projection_versions, projection_snapshots, stock_quants (view), stock_balances                                                                                                                                             | 5      |
| **Sagas**          | sagas, saga_steps                                                                                                                                                                                                                                        | 2      |
| **Réservations**   | credit_reservations, stock_reservations                                                                                                                                                                                                                  | 2      |
| **Contrats**       | contracts, intents, anomalies, system_modes, decision_audit                                                                                                                                                                                              | 5      |
| **Séquences**      | aggregate_sequences, stock_moves                                                                                                                                                                                                                         | 2      |
| **Laravel Core**   | users, cache, jobs, customers, journal_entries, merkle_nodes                                                                                                                                                                                             | 6      |
| **Total**          |                                                                                                                                                                                                                                                          | **45** |

---

## 14. Points d'Attention

### 14.1 Performance

- Utilisation de sharding pour event_store
- Index composite sur les tables de liaison
- Snapshots pour accélérer les replays
- Vue materialisée pour stock_quants

### 14.2 Consistance

- Transactions pour les réservations
- Triggers pour maintenir balance_stock
- Chaîne de hachage pour audit_logs
- Clés étrangères avec cascade delete

### 14.3 Extensibilité

- Schémas JSON pour validation événements
- System modes pour feature flags
- Contracts pour logique métier dynamique
- Sagas pour orchestration complexe

---

---

## Prochaines Sections

- [01-architecture-generale.md](./01-architecture-generale.md) - Documentation technique générale
- [02-infrastructure-core.md](./02-infrastructure-core.md) - Services et composants techniques
- [03-domaines-metiers.md](./03-domaines-metiers.md) - Implémentation des 13 macro-domaines
- [04-projections-analytics.md](./04-projections-analytics.md) - Phase C : Dashboards et analytics
- [05-temps-reel-crdt.md](./05-temps-reel-crdt.md) - Phase D : Temps réel et sync mobile
- [06-api-graphql.md](./06-api-graphql.md) - Phase D : API de requête GraphQL
- [07-alerting-observabilite.md](./07-alerting-observabilite.md) - Phase D : Monitoring et alertes
- [08-deployment-operations.md](./08-deployment-operations.md) - Guide de déploiement et maintenance

_Document généré le: 25 Mars 2026_
_Architecture: Domain-Driven Design + Event Sourcing + CQRS_
