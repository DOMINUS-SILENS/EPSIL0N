# Data Profiling & Anomaly Audit (Phase 1.5 - HARDENED v3)

This document formalizes the **Anomaly Gate** for the EPSILON ERP reconstruction. Any record violating these criteria will be trapped during Phase 3 migration.

## 1. Primary & Foreign Key Integrity (FAIL-HARD GATES)

| Relation Check | Anomaly Type | Policy | Risk |
| :--- | :--- | :--- | :--- |
| `articles.entreprise_id`| Missing tenant | **FAIL HARD** | Critical: Data Leakage |
| `stock_balances` | Orphaned article_id| **FAIL HARD** | Critical: Stock Corruption|
| `stock_balances` | Orphaned depot_id | **FAIL HARD** | Critical: Stock Corruption|
| `canonical_orders` | Orphaned customer_id| **FAIL HARD** | High: Logical Orphan |
| `canonical_order_lines`| Orphaned order_id | **SKIP ROW** | High: Ghost Data |

---

## 2. Mandatory Nullability Audit (DEFAULT GATES)

| canonical column | source field | invalid condition | resolution policy |
| :--- | :--- | :--- | :--- |
| `articles.designation` | `article_designation`| empty after TRIM() | fallback: `[UNNAMED ARTICLE]` |
| `articles.ean13` | `article_ean13` | duplicates found | **LATEST WINS** |
| `depots.designation` | `depot_designation` | empty after TRIM() | fallback: `[DEFAULT DEPOT]` |
| `customer.name` | `name` | empty | fallback: `[UNNAMED CUSTOMER]` |

---

## 3. Transactional Sanity (FAIL-HARD GATES)

> [!IMPORTANT]
> **No Magic Correction**: we do not silently sum or overwrite transactional records.
> If a duplicate is found in the following categories, the migration MUST fail.

| category | check | policy |
| :--- | :--- | :--- |
| **Orders** | Multiple UUID/Reference match | **FAIL HARD** |
| **Movements** | Multiple event_id match | **FAIL HARD** |
| **Event Store** | Collision on (shard, sequence) | **FAIL MIGRATION** |

---

## 4. Format & Timestamp Correction Gate

| field | legacy condition | canonical resolution |
| :--- | :--- | :--- |
| `last_sync_timestamp` | Epoch Integer | `Carbon::createFromTimestamp()` |
| `last_sync_timestamp` | ISO String | `Carbon::parse()` |
| `last_sync_timestamp` | Malformed string | `NULL` + **WARNING LOG** |
| `created_at` | Invalid MySQL date | `NOW()` + **WARNING LOG** |
