# Documentation SFA God-Level ERP

Bienvenue dans la documentation complète du SFA (Sales Force Automation) ERP, une plateforme de gestion commerciale de niveau entreprise construite sur une architecture **Event-Sourced CQRS**.

---

## 📚 Table des Matières

### Phase Fondamentale (Phases 1-4)

1. **[Architecture Générale](./01-architecture-generale.md)**
    - Principes Event Sourcing
    - Pattern CQRS
    - Infrastructure Core
    - Modèle de données physique
    - Garanties système (CAP)

2. **[Infrastructure Core](./02-infrastructure-core.md)**
    - Event Store Service
    - Outbox Pattern
    - Sequence Service
    - Projection Dispatcher
    - Reservation Service
    - Saga Orchestrator
    - Commandes Artisan

3. **[Domaines Métiers](./03-domaines-metiers.md)**
    - Référentiel Articles (Articleggregate)
    - Gestion des Stocks (StockAggregate)
    - Préventes et Mouvements (MovementAggregate)
    - Missions et Tournées (MissionAggregate)
    - Encaissements et Crédits (CreditAggregate)
    - Moteur de Promotions (PromotionEngine)
    - Optimisation des Routes (RouteOptimization)
    - CRM et Visites (CrmAggregate)
    - Comptabilité Analytique (JournalEntry)

### Phase C : Analytics

4. **[Projections Analytics](./04-projections-analytics.md)**
    - Tables dashboard_sales et dashboard_top_articles
    - SalesDashboardProjector
    - Garanties idempotentes
    - Requêtes analytiques optimisées
    - Commandes de backfill

### Phase D : Temps Réel et Sync

5. **[Temps Réel et CRDT](./05-temps-reel-crdt.md)**
    - Live Dashboard Service (WebSocket/SSE)
    - CRDT (Conflict-Free Replicated Data Types)
    - Sync offline-first pour mobile
    - Vector clocks et merge strategies

6. **[API GraphQL](./06-api-graphql.md)**
    - Schéma GraphQL complet
    - Resolvers optimisés
    - Pagination cursor-based
    - Requêtes exemples

7. **[Alerting et Observabilité](./07-alerting-observabilite.md)**
    - Alerting Service
    - Métriques métier
    - OpenTelemetry tracing
    - Health checks
    - Audit trail

### Phase E : Mobile Offline-First

8. **[Architecture Offline-First SFA](./11-architecture-offline-first.md)**
    - Transactions Dexie.js locales
    - Causal Outbox & Sync Engine
    - Idempotence et Résolution de Conflits
    - Ingestion Serveur Laravel
    - SFA "Local-First"

### Opérations

9. **[Déploiement et Opérations](./08-deployment-operations.md)**
    - Architecture de déploiement
    - Installation et configuration
    - Commandes opérationnelles
    - Backup et restauration
    - Monitoring et alerting
    - Scaling horizontal
    - Sécurité

---

## 🏗️ Architecture en Aperçu

```
┌─────────────────────────────────────────────────────────────────────────┐
│                          SFA GOD-LEVEL ERP                              │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐                 │
│  │   Mobile    │    │   Web       │    │   ERP       │                 │
│  │   App       │    │   Dashboard │    │   Legacy    │                 │
│  └──────┬──────┘    └──────┬──────┘    └──────┬──────┘                 │
│         │                   │                   │                         │
│         └───────────────────┼───────────────────┘                         │
│                             │                                           │
│                    ┌────────▼────────┐                                  │
│                    │   GraphQL API   │                                  │
│                    │   / REST        │                                  │
│                    └────────┬────────┘                                  │
│                             │                                           │
│  ╔═════════════════════════╧═══════════════════════════╗                │
│  ║                 COMMAND SIDE                        ║                │
│  ║  ┌─────────────┐      ┌─────────────────────────┐  ║                │
│  ║  │ Aggregates  │─────▶│      Event Store        │  ║                │
│  ║  │ (9 domains) │      │  (Source of Truth)      │  ║                │
│  ║  └─────────────┘      └───────────┬───────────────┘  ║                │
│  ╚═════════════════════════════════╪══════════════════╝                │
│                                    │                                    │
│                         ┌──────────▼──────────┐                          │
│                         │    Domain Outbox    │                          │
│                         └──────────┬──────────┘                          │
│                                    │                                    │
│  ╔═════════════════════════════════╪══════════════════╗                │
│  ║                 QUERY SIDE                        ║                │
│  ║  ┌──────────────────────────┐  │  ┌─────────────┐  ║                │
│  ║  │     Projections          │  │  │  Analytics  │  ║                │
│  ║  │ ┌─────┐ ┌─────┐ ┌─────┐  │  │  │ ┌─────────┐ │  ║                │
│  ║  │ │Mvt  │ │Stock│ │CRM  │  │◀─┘  │ │Dashboard│ │  ║                │
│  ║  │ └─────┘ └─────┘ └─────┘  │     │ └─────────┘ │  ║                │
│  ║  └──────────────────────────┘     └─────────────┘  ║                │
│  ╚════════════════════════════════════════════════════╝                │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 🚀 Démarrage Rapide

### Installation Développement

```bash
# Clone et installation
git clone git@github.com:entreprise/sfa-erp.git
cd sfa-erp
composer install
cp .env.example .env
php artisan key:generate

# Configuration base de données
# Éditer .env avec vos credentials MySQL

# Migrations
php artisan migrate
php artisan db:seed --class=DemoSeeder

# Serveur de développement
php artisan serve
```

### Commandes Essentielles

```bash
# Traitement des événements
php artisan outbox:process

# Reconstruction des projections
php artisan dashboard:rebuild-sales

# Évaluation des alertes
php artisan alerts:evaluate

# Sync CRDT
php artisan crdt:sync

# Vérification d'intégrité
php artisan event-store:verify
```

---

## 📊 Statistiques Architecture

- **13 Macro-Domaines** implémentés
- **40+ Événements de Domaine**
- **16 Projecteurs CQRS**
- **Partitionnement** par entreprise (16 shards)
- **Latence lecture** O(1) sur projections
- **Intégrité** garantie par Merkle tree
- **Sync offline** avec CRDT

---

## 🔗 Ressources Externes

- [Documentation Laravel](https://laravel.com/docs)
- [GraphQL Specification](https://spec.graphql.org)
- [Event Sourcing Pattern](https://martinfowler.com/eaaDev/EventSourcing.html)
- [CQRS Pattern](https://martinfowler.com/bliki/CQRS.html)
- [CRDT Paper](https://hal.inria.fr/file/index/index/docid/555588/file/techreport.pdf)

---

## 📝 Notes de Version

### v2.1.0 (2026-03-25)

- ✅ **Hardening & Resilience**: Aggregate Snapshotting, Dead Letter Queue (DLQ), Redis Sequences.
- ✅ **Observability**: Prometheus Exporter (`/api/metrics`) and enhanced ES/CQRS metrics.
- ✅ **Stability**: Full integration tests for Event Sourcing lifecycle.

### v2.0.0 (2026-03-22)

- ✅ Phase D complète: Temps réel, CRDT, GraphQL, Alerting
- ✅ Documentation complète
- ✅ Tests unitaires complets

### v1.0.0 (2026-01-15)

- ✅ Phases 1-4: Core + 13 domaines
- ✅ Phase C: Analytics
- ✅ Migrations legacy

---

## 👥 Équipe

- **Architecture** : Lead Tech ERP
- **Développement** : Équipe Backend
- **DevOps** : Équipe Infrastructure
- **Documentation** : Équipe Produit

---

## 📄 Licence

Propriétaire - © 2026 Entreprise SARL
Tous droits réservés.

---

_Dernière mise à jour : 22 Mars 2026_
