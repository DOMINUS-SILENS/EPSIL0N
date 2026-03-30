# Architecture Région / Zone / Secteur (Event-Sourced)

Ce document formalise la séparation stricte des responsabilités (Containment of Authority) entre les couches opérationnelles (Secteur), de coordination (Zone) et de stratégie (Région), construites sur l'architecture Event-Sourcing.

---

## 1. Principes de Séparation

- **Secteur = Production de faits transactionnels** (Append-only)
  Le terrain émet la réalité locale non filtrée (offline-first). Il ne raisonne pas sur les politiques globales.
  *Ex: `OrderCreated`, `StockReserved`, `VisitCompleted`.*

- **Zone = Orchestration, adaptation, résolution de conflits** (Sagas / Process Managers)
  La zone réagit aux faits du secteur, applique les politiques de la région, gère les exceptions et alloue les ressources.
  *Ex: `ReserveStockCommand`, `ReassignVisitCommand`.*

- **Région = Stratégie, politiques, contraintes** (Configuration)
  La région définit les règles du jeu. Elle n'est jamais impactée directement par une transaction unitaire du terrain.
  *Ex: `PricingPolicyChanged`, `CreditLimitUpdated`.*

---

## 2. Matrice d'Appartenance des Agrégats

| Domaine (Couche)       | Agrégat / Composant    | Type            | Rôle Architecural                               |
| ---------------------- | ---------------------- | --------------- | ----------------------------------------------- |
| **Secteur** (Terrain)  | `Order`                | Aggregate       | Capture d'une commande (immobile, offline-first)|
| **Secteur**            | `Payment`              | Aggregate       | Preuve locale de collecte financière            |
| **Secteur**            | `StockMovement`        | Aggregate       | Réalité de l'inventaire embarqué ou consommé    |
| **Secteur**            | `Visit`                | Aggregate       | Réalité de l'exécution commerciale              |
| **Zone** (Coordination)| `RoutePlan`            | Aggregate       | État de coordination des tournées               |
| **Zone**               | `Allocation`           | Aggregate       | État d'allocation stock / ressources            |
| **Zone**               | `Assignment`           | Aggregate       | Couverture / Charge de travail                  |
| **Zone**               | `OrderFulfillmentSaga` | Process Manager | Orchestration multi-étapes de la commande       |
| **Zone**               | `VisitRecoverySaga`    | Process Manager | Adaptation dynamique de l'exécution terrain     |
| **Zone**               | `StockRecoverySaga`    | Process Manager | Gestion des exceptions d'inventaire             |
| **Zone**               | `PaymentRiskSaga`      | Process Manager | Réaction immédiate aux impayés (ex: blocage)    |
| **Région** (Politique) | `PricingPolicy`        | Aggregate       | Gouvernance commerciale                         |
| **Région**             | `CreditPolicy`         | Aggregate       | Gouvernance du risque                           |
| **Région**             | `CatalogPolicy`        | Aggregate       | Gouvernance de distribution (assortissements)   |
| **Région**             | `QuotaPolicy`          | Aggregate       | Objectifs et pilotage de la performance         |

> **Règle absolue :** Secteur énonce la vérité. Zone orchestre en réaction à la vérité. Région définit les règles. Les couches ne se mutent pas directement en contournant les commandes.

---

## 3. Flux d'Événements Cœur (Zone Sagas)

### Saga 1: Order Fulfillment (Zone)
- **Déclencheur**: `OrderSubmitted` (Secteur)
- **Logique**: Tente de réserver le stock réel. Si OK -> Déclenche l'assignation de livraison. Si KO -> Déclenche une allocation de secours ou un backorder.
- **Autorité**: La Zone décide comment honorer la commande en fonction de la réalité (Stock).

### Saga 2: Visit Recovery (Zone)
- **Déclencheur**: `VisitCancelled`, `VisitNoShow`, `RepUnavailable` (Secteur)
- **Logique**: Identifie l'impact sur la couverture. Si client prioritaire -> Réassigne la visite (`ReassignVisitCommand`). Sinon -> Reprogramme (`RescheduleVisitCommand`).
- **Autorité**: La Zone protège la couverture en réaction aux aléas terrain.

### Saga 3: Payment Risk Reaction (Zone)
- **Déclencheur**: `PaymentRejected`, `OrderSubmitted` (Secteur)
- **Logique**: Vérifie la projection de `CreditPolicy` (Région). Si l'encours dépasse la limite -> `BlockFurtherOrdersCommand`.
- **Autorité**: La Zone applique la politique régionale en temps réel.

---

## 4. Stratégie de Déploiement (Roadmap Sprints)

1. **Phase 1 (Secteur Core)** : Alignement strict des agrégats `Order`, `StockMovement`, `Visit`, `Payment` sur l'append-only.
2. **Phase 2 (Projections Régionales)** : Projections intelligentes (Dexie / Backend) pour Pricing, Credit, et Catalog.
3. **Phase 3 (Orchestration Zone)** : Implémentation de `OrderFulfillmentSaga` et `VisitRecoverySaga`.
4. **Phase 4 (Adaptation Zone)** : Gestion fine des exceptions (`StockRecoverySaga`, `PaymentRiskSaga`).
