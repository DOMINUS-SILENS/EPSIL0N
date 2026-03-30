# Gate B: SQL Profiling & Plan Validation

L'objectif strict de la Gate B est de passer au crible le moteur de persistance relationnel (MySQL) afin de garantir que les 12 requêtes chaudes du système Event-Sourced ne s'effondrent pas en production sous la charge. 

L'approche repose exclusivement sur de l'**analyse mesurée (`EXPLAIN ANALYZE`) à partir de requêtes interceptées dynamiquement (*real bindings*)**.

## 🔴 User Review Required

Ce plan contient la démarche méthodologique de la Gate B. Vous devez valider cet inventaire et le script de profilage automatisé avant que je ne lance l'extraction de métriques et ne propose des index correctifs (ou destructifs).

## 1. Méthodologie d'Audit (Couche Zero-Imagination)

Nous n'allons pas profiler du SQL théorique. Pour respecter la règle méthodologique, le script `App\Console\Commands\ProfileGateB` a été créé. Ce script :
1. Déclenche des appels Eloquent et QueryBuilder fidèles à l'implémentation métier réelle.
2. Intercepte le Query Engine via `DB::listen(...)`.
3. Réinjecte les *bindings* authentiques.
4. Soumet le SQL capturé à `EXPLAIN` (pour l'usage de l'index brut) et `EXPLAIN ANALYZE` (pour le coût opérateur et latence).

## 2. Inventaire Exhaustif Validé (Les 12 Familles)

La matrice `docs/certification/gate-b/01-query-inventory.md` a été générée avec les périmètres suivants :

### Famille 1 : Event Sourcing
- `Q1`: Aggregate Rebuild (`aggregate_id`)
- `Q2`: Incremental Sync Cursor (`global_sequence > ?`)
- `Q3`: Tenant Filtered Cursor (`tenant_id, global_sequence`)

### Famille 2 : Asynchronisme & Projectors
- `Q4`: Poll Loop Outbox (`status, created_at`)
- `Q5`: Projector Duplicate Guard (`projector_name, event_id`)

### Famille 3 : Business Critical Reads
- `Q6`: Stock Lookup Principal (`company, depot, article`)
- `Q7`: Stock List / Alerte (`company, depot, available_quantity`)
- `Q8`: Orders Open Credit (`customer_id, status`)
- `Q9`: Mobile Sales Listing (`created_by, created_at`)
- `Q10`: Article Barcode / EAN (`ean13`, `barcode`)

### Famille 4 : Synchronisation Mobile
- `Q11`: Delta Sync (`company_id, updated_at`)
- `Q12`: Sync State Checkpoint (`device_id, entity`)

## 3. Dossier de Preuve & Remédiation (Gate B Deliverables)

Une fois l'outil de profilage exécuté avec votre accord, les artefacts suivants seront compilés pour clore la Gate B :
- `02-explain-analyze.md` : Captures brutes des query plans.
- `03-index-justification.md` : Défense des ajouts (cost-benefit ratio).
- `04-write-amplification-risk.md` : Bilan pénalité `INSERT` vs bénéfice `SELECT`.
- `05-remediation-log.md` : Historique des corrections SQL, des modifications de tables (`add_index`), et de suppression d'index inutiles.

## ⚠️ Open Questions (Risk Zones)

> [!WARNING]
> La table `balance_stock` et `orders` pourraient utiliser des JSON columns (Metadata). Devons-nous spécifiquement forcer MySQL 8.0 Generated Columns sur certains `EXPLAIN` si l'ORM Laravel utilise un fallback en `LIKE` sur un casting JSON ?

## Verification Plan

### Automated Execution
Lancement de :
`php artisan gate:profile-b`

### Validation Manuel
Lecture du coût *Rows Examined* et du tag *Filesort / Using Temporary* dans l'artefact généré. Toute requête n'utilisant pas un Index ciblé entraînera une migration rectificative restrictive.
