# Gate B - 01 Query Inventory (Matrice des Requêtes Chaudes)

**Statut**: 📝 En préparation
**Date**: 29 March 2026

Ce document recense exhaustivement les 12 familles de requêtes SQL critiques du SFA qui feront l'objet d'un profilage agressif `EXPLAIN ANALYZE` pour chasser les Filesorts, Table Scans et Temporary Tables.

## Famille 1 : Event Sourcing (Les Fondations Absolues)

| ID | Domaine | Requête Métier | Objectif d'Optimisation |
| :--- | :--- | :--- | :--- |
| **Q1** | Event Store | Aggregate Rebuild (`aggregate_id`) | Validation index `uniq_aggregate_version`. Pas de filesort. |
| **Q2** | Event Store | Incremental Sync Cursor (`global_sequence > ?`) | Index optimal pour sequential scan massif sans filter. |
| **Q3** | Event Store | Tenant Filtered Cursor | Index composite `(tenant_id, global_sequence)`. Évaluer le json schema extraction sur metadata. |

## Famille 2 : Asynchronisme & Projectors

| ID | Domaine | Requête Métier | Objectif d'Optimisation |
| :--- | :--- | :--- | :--- |
| **Q4** | Outbox | Poll Loop (`status = 'pending' ORDER BY created_at`) | Index composite exact `(status, created_at)`. |
| **Q5** | Projectors | Duplicate Guard Checkpoint | Vérification de l'lookup index-only sur `(projector_name, event_id)` de la table `projector_processed_events`. |

## Famille 3 : Business Critical Reads (Le terrain)

| ID | Domaine | Requête Métier | Objectif d'Optimisation |
| :--- | :--- | :--- | :--- |
| **Q6** | Stock | Lookup Principal (`company, depot, article`) | Validation de la clef composite optimale sur le lookup précis 1-row. |
| **Q7** | Stock | Liste / Alerte Faible Stock | Indexation supportant le scan des stocks avec filtre sur `available_qty < threshold`. |
| **Q8** | Orders | Open Orders Customer Credit (`customer_id`, statuses) | Performance de la fonction d'agrégation `SUM` avec filtres via index. |
| **Q9** | Orders | Mobile Sales Listing (`created_by`, pagination DESC) | Évaluation du coût de pagination `OFFSET` sur `created_at DESC` vs Curseurs keyset. |
| **Q10** | Articles | Barcode / EAN Lookup | Index unique natif. Performance du string matching. |

## Famille 4 : Synchronisation Mobile (Delta & État)

| ID | Domaine | Requête Métier | Objectif d'Optimisation |
| :--- | :--- | :--- | :--- |
| **Q11** | Delta Sync | Changed Entities (`updated_at > ?`) | Risque majeur de full scan détecté. Preuve de l'usage effectif de l'index sur `updated_at` combiné au `company_id`. |
| **Q12** | Sync State | Device Checkpoint lookup | Éviter la croissance polynomiale et s'assurer de `(device_id, entity)` comme clé stable. |

---
**Étape Suivante :** Un script d'analyse dynamique capture les *query bindings* réels via le Query Logger Laravel et soumet les générateurs natifs à un `EXPLAIN ANALYZE` MySQL.
