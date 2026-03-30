# Canonical Schema Specification v1.4 (HARDENED)

This document defines the **Canonical Parallel Namespace** for the EPSILON ERP reconstruction. It is the definitive contract for the **Atomic Reconstruction** (v8 Plan).

## 1. Structural Safeguards
- **Naming Convention**: Use the `canonical_` prefix for ALL project tables during the rebuild phase (e.g., `canonical_articles`, `canonical_orders`). These will be renamed to their final plural forms (`articles`, `orders`) during the final retirement wave.
- **Ledger Status**: `canonical_orders` and `canonical_order_lines` ARE the **Canonical Commercial Ledger**. They are the source of truth for all sales/audits, not reconstructible projections.
- **Unified Tenant**: `entreprise_id` (BIGINT UNSIGNED NOT NULL) on all tenant-owned tables.
- **Strict Decimals**: Quantities `DECIMAL(15,3)`, Amounts `DECIMAL(18,2)`.
- **Primary Keys**: 
    - Referentials (Articles, Customers, Depots): `id` (BIGINT UNSIGNED).
    - Transactionals (Orders, OrderLines): `id` (CHAR 36 UUID).
- **Collation**: `utf8mb4_unicode_ci` on all columns.

---

## 2. Bounded Contexts (Aggregates)

| Aggregate | Owner | Truth Source | Target Parallel Table |
| :--- | :--- | :--- | :--- |
| **Enterprise** | Multi-Tenancy | Canonical | `canonical_entreprises` |
| **Inventory** | Items/Stock | Canonical | `canonical_articles`, `canonical_depots` |
| **Stock Projection**| Stock Checks | READ-MODEL | `canonical_stock_balances` |
| **Sales** | Trading/CRM | Canonical | `canonical_customers`, `canonical_orders`, `canonical_order_lines` |
| **Infra** | Sync/Events | Canonical | `canonical_device_sync_states`, `event_store`, `domain_outbox` |

---

## 3. Table Contract (Phase 2A Target)

### 3.1. Inventory: `canonical_articles`
- **PK**: `id`
- **FK**: `entreprise_id` (INDEX)
- **Columns**: `designation`, `sku`, `ean13`, `barcode`, `stock_quantity`, `is_active`.
- **Unique**: `uq_articles_tenant_ean13 (entreprise_id, ean13)`

### 3.2. Sales: `canonical_orders` (Commercial Ledger)
- **PK**: `id` (UUID - CHAR 36)
- **FK**: `entreprise_id`, `customer_id`, `created_by`
- **Status**: `status` (VARCHAR 20, INDEX).

### 3.3. Sales: `canonical_order_lines` (Commercial Snapshots)
- **PK**: `id` (UUID - CHAR 36)
- **FK**: `order_id` (UUID), `article_id`
- **Commercial Snapshots**: Mandatory copy (designation, sku, ean13).

---

## 4. Hot Path Index Contract (Gate B Target)
- Articles: `idx_articles_lookup (entreprise_id, barcode)`
- Orders: `idx_orders_customer (entreprise_id, customer_id, status)`
- Event Store: `idx_event_store_global (global_sequence)`
- Outbox: `idx_outbox_processing (status, created_at)`
- Sync States: `uniq_device_entity (entreprise_id, device_id, entity_type) UNIQUE`
- Stock Balances: `uq_stock_triplet (entreprise_id, depot_id, article_id) UNIQUE`
