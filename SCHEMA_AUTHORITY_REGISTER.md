# Schema Authority Register

## Classification Rules

Every business-relevant table must be classified into exactly one of:

- **A — Canonical Authority Table**: Official business truth, write-protected, reconciliation target
- **B — Projection / Read Model**: Derived only, disposable, never authoritative
- **C — Legacy Transitional Table**: Temporary compatibility, bounded deprecation date
- **D — Infrastructure Table**: Supports consistency, transport, orchestration

**Critical Rule:** If any table is "kind of both," the architecture is broken. That ambiguity is where data corruption hides.

---

## Register Entries

### Domain: Core / Multi-tenant

| Table | Classification | Authority Path | Status | Notes |
|-------|---------------|----------------|--------|-------|
| `entreprise` | **A — Canonical** | `EntrepriseAggregate` → `EntrepriseCreated/Updated` events | PRODUCTION | Multi-tenancy root |
| `users` | **A — Canonical** | Laravel Auth + `UserAggregate` | PRODUCTION | Auth source |
| `employees` | **A — Canonical** | `HrAggregate` → domain events | MIGRATING | Transition from direct CRUD |

### Domain: Catalog / Products

| Table | Classification | Authority Path | Status | Notes |
|-------|---------------|----------------|--------|-------|
| `article` | **C — Legacy Transitional** | Direct Eloquent writes + `ArticleAggregate` dual-write | **CRITICAL** | Canonical migration in progress |
| `articles` (canonical) | **A — Canonical** | `ArticleAggregate` → `ArticleCreated/Updated` | MIGRATING | Target canonical table |
| `article_famille` | **C — Legacy Transitional** | Direct writes + aggregate events | MIGRATING | Needs classification decision |
| `article_marque` | **C — Legacy Transitional** | Direct writes + aggregate events | MIGRATING | Needs classification decision |
| `article_unite` | **C — Legacy Transitional** | Direct writes + `ArticleUnitsUpdated` event | MIGRATING | Complex variant logic |
| `article_groupe_prix` | **C — Legacy Transitional** | Direct writes | REVIEW | Price group logic |

### Domain: Inventory / Stock

| Table | Classification | Authority Path | Status | Notes |
|-------|---------------|----------------|--------|-------|
| `article_mouvement` | **C — Legacy Transitional** | `MovementAggregate` + direct table writes | **CRITICAL** | See Inventory Consistency Contract |
| `mouvement` | **C — Legacy Transitional** | Direct writes | REVIEW | Legacy movement header |
| `mouvement_ligne` | **C — Legacy Transitional** | Direct writes | REVIEW | Legacy movement lines |
| `balance_stock` | **B — Projection** | Derived from `article_mouvement` events | **RISKY** | Currently dual-written |
| `stock_balances` (canonical) | **B — Projection** | `StockProjector` from movement events | MIGRATING | Target canonical projection |
| `stock_reservations` | **A — Canonical** | `ReservationService` → events | PRODUCTION | Credit/availability logic |
| `stock_moves` | **A — Canonical** | `StockAggregate` → `StockMoved` events | PRODUCTION | New canonical movement stream |
| `depot` | **A — Canonical** | `DepotAggregate` → domain events | PRODUCTION | Warehouse master data |
| `article_unite_depot` | **C — Legacy Transitional** | Direct writes | REVIEW | Unit-depot mapping |

### Domain: Orders / Sales

| Table | Classification | Authority Path | Status | Notes |
|-------|---------------|----------------|--------|-------|
| `orders` | **A — Canonical** | `OrderAggregate` → `OrderCreated/Validated/Cancelled` | PRODUCTION | Order lifecycle |
| `order_lines` | **A — Canonical** | `OrderAggregate` → child entities | PRODUCTION | Order detail |
| `purchase_orders` | **C — Legacy Transitional** | `PurchasingAggregate` + direct writes | MIGRATING | Procurement |
| `customers` | **A — Canonical** | `CrmAggregate` → `CustomerCreated/Updated` | PRODUCTION | Customer master |

### Domain: Finance / Accounting

| Table | Classification | Authority Path | Status | Notes |
|-------|---------------|----------------|--------|-------|
| `journal_entries` | **A — Canonical** | `JournalAggregate` → `JournalEntryPosted` | PRODUCTION | Accounting entries |
| `credit_reservations` | **A — Canonical** | `CreditAggregate` → `CreditReserved/Released` | PRODUCTION | Credit holds |
| `customer_balance_projections` | **B — Projection** | `FinanceProjector` from journal events | PRODUCTION | Receivable balances |

### Domain: Event Sourcing Infrastructure

| Table | Classification | Purpose | Retention | Notes |
|-------|---------------|---------|-----------|-------|
| `event_store` | **D — Infrastructure** | Immutable event log (16 shards) | Permanent | Cryptographic signatures |
| `event_shards` | **D — Infrastructure** | Shard metadata | Permanent | Static configuration |
| `domain_outbox` | **D — Infrastructure** | Pending domain events | 7 days | Reliable delivery |
| `integration_outbox` | **D — Infrastructure** | External integration events | 7 days | Third-party sync |
| `aggregate_snapshots` | **D — Infrastructure** | Aggregate state checkpoints | Rolling | Every 100 events |
| `projection_snapshots` | **D — Infrastructure** | Read model checkpoints | Rolling | For fast recovery |
| `projection_versions` | **D — Infrastructure** | Projection offset tracking | 30 days | Replay positioning |
| `sagas` | **D — Infrastructure** | Long-running transaction state | 30 days | Orchestration |
| `saga_steps` | **D — Infrastructure** | Saga step state | 30 days | Step tracking |
| `domain_events` | **D — Infrastructure** | Published event registry | 7 days | Deduplication |
| `merkle_nodes` | **D — Infrastructure** | Event verification tree | Permanent | Integrity proof |
| `audit_logs` | **D — Infrastructure** | Operational audit | 90 days | Compliance |

### Domain: Offline Sync / Mobile

| Table | Classification | Purpose | Retention | Notes |
|-------|---------------|---------|-----------|-------|
| `device_sync_state` | **D — Infrastructure** | Per-device sync cursor | Rolling | Last sync position |
| `sync_conflicts` | **D — Infrastructure** | Conflict queue | 30 days | Resolution tracking |
| `api_idempotency_keys` | **D — Infrastructure** | Duplicate prevention | 24 hours | Request dedupe |
| `offline_sync_batches` | **D — Infrastructure** | Batch ingestion tracking | 7 days | Mobile upload |
| `sync_conflicts_resolved` | **D — Infrastructure** | Resolution audit | 90 days | Compliance |

### Domain: Fleet / Logistics

| Table | Classification | Authority Path | Status | Notes |
|-------|---------------|----------------|--------|-------|
| `vehicles` | **A — Canonical** | `FleetAggregate` → `VehicleRegistered` | PRODUCTION | Fleet master |
| `delivery_tours` | **A — Canonical** | `RouteAggregate` → tour events | PRODUCTION | Delivery planning |
| `delivery_stops` | **A — Canonical** | `RouteAggregate` → stop events | PRODUCTION | Stop lifecycle |
| `missions` | **A — Canonical** | `MissionAggregate` → `MissionCreated/Completed` | PRODUCTION | Field missions |

### Domain: Analytics / Reporting

| Table | Classification | Purpose | Source | Trust Level |
|-------|---------------|---------|--------|-------------|
| `analytics_dashboards` | **B — Projection** | Dashboard data | Multiple | P0 |
| `analytics_metrics` | **B — Projection** | Aggregated metrics | Event stream | P0 |
| `report_views` | **B — Projection** | Materialized reports | Projections | P0 |

### Domain: System / Control

| Table | Classification | Purpose | Notes |
|-------|---------------|---------|-------|
| `system_modes` | **D — Infrastructure** | Feature flags / system state | Operational control |
| `anomalies` | **D — Infrastructure** | Detected inconsistencies | Alerting |
| `contracts` | **D — Infrastructure** | Service contracts | Integration boundaries |
| `intents` | **D — Infrastructure** | Queued intentions | Async processing |
| `cache_entries` | **D — Infrastructure** | Application cache | Redis-backed |
| `failed_outbox_events` | **D — Infrastructure** | Dead letter queue | Manual review |
| `quarantine_tables` | **D — Infrastructure** | Suspicious data isolation | Security |

---

## Critical Ambiguities (Must Resolve)

### 1. `article` vs `articles` (Canonical Migration)

**Problem:** Dual table structure with unclear transition state.

**Decision Required:**
- Complete cutover date: ___________
- Legacy table kill condition: Zero reads from `article` for 30 days
- Data reconciliation proof: ___________

### 2. `article_mouvement` (Stock Movements)

**Problem:** Legacy movement table with event-sourced overlay.

**Decision Required:**
- Canonical replacement: `stock_moves` table
- Kill condition: All movement writes go through `StockAggregate`
- Data migration: Backfill `stock_moves` from `article_mouvement`

### 3. `balance_stock` vs `stock_balances`

**Problem:** Stock balance dual-write (legacy + projection).

**Decision Required:**
- `balance_stock` classification: Legacy transitional → projection only
- `stock_balances` classification: Canonical projection
- Kill condition: `balance_stock` becomes read-only, `stock_balances` single write

### 4. `orders` (Authority Verification)

**Status Check:**
- [ ] Confirm no direct `Order::create()` outside `OrderAggregate`
- [ ] Confirm all writes emit `OrderCreated/Updated/Cancelled` events
- [ ] Confirm no admin panel bypasses event path
- [ ] Document reconciliation procedure

---

## Amendment Process

1. New table requires classification before production use
2. Classification change requires architecture board approval
3. Legacy → Canonical promotion requires kill criteria met
4. All changes logged with date, owner, and justification

---

## Ownership and Governance

| Field | Value |
|-------|-------|
| **Owner** | Architecture Board |
| **Approver** | CTO |
| **Last Verified Date** | 2026-03-30 |
| **Verification Method** | Document review |
| **Production Criticality** | Tier 1 |
| **Rollback Impact** | Cannot roll back - authority classifications in use |
| **Verification Status** | Declared |

---

**Document Version:** 1.1  
**Owner:** Architecture Board  
**Next Review:** 2026-04-06
