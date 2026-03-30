# Legacy-to-Canonical Mapping v8 (HARDENED)

This document provides the definitive, machine-safe mapping from the legacy schema to the Canonical Schema v1.4. This version prioritizes **Infra-Level Precision**, **Safe Routing**, and **Parallel Namespace Integrity** over "best-effort" migration.

## 1. Parallel Namespace Strategy

To prevent collisions with legacy tables (e.g., `customers`, `orders`), ALL canonical tables will use the `canonical_` prefix during the reconstruction phase.

| Logical Entity | Parallel Canonical Table | Legacy Table |
| :--- | :--- | :--- |
| **Enterprise** | `canonical_entreprises` | `entreprise` |
| **Article** | `canonical_articles` | `article` |
| **Depot** | `canonical_depots` | `depot` |
| **Customer** | `canonical_customers` | `customers` |
| **Order** | `canonical_orders` | `orders` |
| **OrderLine** | `canonical_order_lines` | `order_lines` |
| **StockBalance**| `canonical_stock_balances`| `balance_stock` |
| **SyncState** | `canonical_device_sync_states`| `device_sync_state` |

---

## 2. Infrastructure-Only Migration Policy

> [!IMPORTANT]
> **NO ELOQUENT DURING MIGRATION**: The `canonical:migrate-legacy` command MUST use strictly **DB::table() / Query Builder / SQL raw**.
> It is strictly **FORBIDDEN** to use:
> - Eloquent Models (Articles, Orders, etc.)
> - Model Observers or Events
> - Factories or Seeders

---

## 3. Verification & Reconciliation (HARDENED)

- **Forbidden**: `php artisan migrate:fresh`.
- **Mandatory**: `php artisan migrate` (targeted DDL).
- **Mandatory Reconciliation**: After every execution, generate `docs/certification/schema/reconciliation-report.md`.
    - `LEGACY_COUNT`
    - `CANONICAL_COUNT`
    - `FAILED_COUNT`
    - `FINANCIAL_SUM_PARITY` (For Orders/Stocks)

---

## 4. Forbidden Migration Behaviors

> [!CAUTION]
> **PROHIBITED ACTIONS** during migration:
> - No `FLOAT` or `DOUBLE` persistence (use `DECIMAL`).
> - No use of `canonical_` prefixes for final tables *after* cutover.
> - No fallback to `entreprise_id = 1` for missing tenants.
> - No silent mutation of historical `event_store` payloads.
