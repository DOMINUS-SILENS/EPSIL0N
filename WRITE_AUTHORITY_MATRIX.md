# Write Authority Matrix

## Purpose

For each domain object, this matrix defines:
- Authoritative write path
- Forbidden write paths
- Emitted event(s)
- Projection targets
- Reconciliation source
- Rollback behavior

**Rule:** If you cannot write this for every core domain, the architecture is not under control.

---

## Domain: Stock / Inventory

### Domain Object: Stock Movement

| Attribute | Definition |
|-----------|------------|
| **Authoritative Write Path** | `StockAggregate::recordMovement()` → `EventStore::append()` → `StockMoved` event → `StockProjector` → `stock_balances` projection |
| **Forbidden Write Paths** | Direct `balance_stock` mutation, `DB::table('article_mouvement')->insert()`, controller-level stock edits, SQL repair scripts, admin panel "adjustments" |
| **Emitted Events** | `StockReceived`, `StockIssued`, `StockTransferred`, `StockAdjusted`, `StockReversed` |
| **Projection Targets** | `stock_balances` (canonical), `stock_history`, `availability_views`, `warehouse_summary` |
| **Reconciliation Source** | `stock_moves` event stream (ordered by `event_store.local_sequence`) |
| **Rollback Behavior** | Compensating movement event only (`StockReversed` with reference to original event ID); never silent balance patch |
| **Idempotency Key** | `(aggregate_id, movement_type, reference_number, device_id)` |
| **Ordering Constraint** | Same `article_id` + `depot_id` movements must be processed sequentially per shard |

### Domain Object: Stock Reservation

| Attribute | Definition |
|-----------|------------|
| **Authoritative Write Path** | `ReservationService::reserve()` → `CreditReservation` record + `StockReserved` event |
| **Forbidden Write Paths** | Direct `stock_reservations` table edits, reservation bypass in order flow |
| **Emitted Events** | `StockReserved`, `StockReservationReleased`, `StockReservationExpired` |
| **Projection Targets** | `available_stock_view` (calculated: balance - reservations) |
| **Reconciliation Source** | `stock_reservations` table (authoritative) + `stock_balances` projection |
| **Rollback Behavior** | Automatic expiration or explicit release event |
| **Idempotency Key** | `(order_id, article_id, depot_id)` |
| **Expiry Rule** | 24 hours default, configurable per reservation type |

---

## Domain: Orders / Sales

### Domain Object: Order

| Attribute | Definition |
|-----------|------------|
| **Authoritative Write Path** | `OrderAggregate::create()` / `validate()` / `cancel()` → `OrderCreated` / `OrderValidated` / `OrderCancelled` events |
| **Forbidden Write Paths** | Direct `Order::create()`, `Order::where()->update()`, controller saves, admin panel status edits |
| **Emitted Events** | `OrderCreated`, `OrderUpdated`, `OrderValidated`, `OrderCancelled`, `OrderAssigned`, `OrderDelivered` |
| **Projection Targets** | `order_status_view`, `customer_orders_summary`, `delivery_queue`, `revenue_projection` |
| **Reconciliation Source** | `orders` table (canonical authority) |
| **Rollback Behavior** | Compensating `OrderCancelled` with inventory release; financial reversal via `PaymentReversed` |
| **Idempotency Key** | `(customer_id, external_reference, device_id)` |
| **Lifecycle States** | `draft` → `validated` → `assigned` → `picked` → `shipped` → `delivered` → `invoiced` → `paid` |
| **State Transition Rules** | Validated orders cannot return to draft. Shipped orders cannot be cancelled (only returned). |

### Domain Object: Order Line

| Attribute | Definition |
|-----------|------------|
| **Authoritative Write Path** | Child of `OrderAggregate`, created with parent order event |
| **Forbidden Write Paths** | Direct `OrderLine` creation outside order aggregate, line-level status patches |
| **Emitted Events** | `OrderLineAdded`, `OrderLineUpdated`, `OrderLineCancelled` (child events of order) |
| **Projection Targets** | `order_line_details`, `product_sales_summary` |
| **Reconciliation Source** | `order_lines` table (child of `orders` authority) |
| **Rollback Behavior** | Line cancellation emits `OrderLineCancelled` → inventory release |
| **Idempotency Key** | `(order_id, line_number)` |

---

## Domain: Catalog / Products

### Domain Object: Article (Product)

| Attribute | Definition |
|-----------|------------|
| **Authoritative Write Path** | `ArticleAggregate::create()` / `update()` → `ArticleCreated` / `ArticleUpdated` events |
| **Forbidden Write Paths** | `Article::create()`, `Article::save()`, admin panel direct edits, import scripts bypassing aggregate |
| **Emitted Events** | `ArticleCreated`, `ArticleUpdated`, `ArticleArchived`, `ArticleUnitsUpdated` |
| **Projection Targets** | `product_catalog_view`, `pricing_view`, `stock_eligible_products` |
| **Reconciliation Source** | `articles` (canonical) — migration from `article` table in progress |
| **Rollback Behavior** | `ArticleArchived` event (soft delete); no hard deletes permitted |
| **Idempotency Key** | `(ean13)` or `(bar_code)` — uniqueness enforced |
| **Current Status** | **DUAL-WRITE** — Legacy `article` + new `articles` — Cutover required |
| **Kill Condition** | All product reads moved to `articles`, zero reconciliation drift for 30 days |

### Domain Object: Article Price

| Attribute | Definition |
|-----------|------------|
| **Authoritative Write Path** | `ArticleAggregate::updatePricing()` → `ArticlePricingUpdated` event |
| **Forbidden Write Paths** | Direct `article_groupe_prix` mutations, SQL price updates |
| **Emitted Events** | `ArticlePricingUpdated` |
| **Projection Targets** | `current_prices_view`, `price_history` |
| **Reconciliation Source** | Event stream (prices fully event-sourced) |
| **Rollback Behavior** | New pricing event with previous price reference |
| **Idempotency Key** | `(article_id, price_list_id, effective_date)` |

---

## Domain: Finance / Accounting

### Domain Object: Journal Entry

| Attribute | Definition |
|-----------|------------|
| **Authoritative Write Path** | `JournalAggregate::postEntry()` → `JournalEntryPosted` event → `AccountingProjector` |
| **Forbidden Write Paths** | Direct `journal_entries` insertion, SQL accounting fixes, balance corrections |
| **Emitted Events** | `JournalEntryPosted`, `JournalEntryReversed` |
| **Projection Targets** | `customer_balance_projections`, `account_summary_view`, `general_ledger` |
| **Reconciliation Source** | `journal_entries` table (canonical) |
| **Rollback Behavior** | `JournalEntryReversed` with reference to original entry ID |
| **Idempotency Key** | `(reference_number, entry_date, account_code)` |
| **Immutability Rule** | Posted entries never edited; only reversed and re-posted |

### Domain Object: Payment

| Attribute | Definition |
|-----------|------------|
| **Authoritative Write Path** | `PaymentAggregate::record()` → `PaymentRecorded` event |
| **Forbidden Write Paths** | Direct payment table updates, status patches |
| **Emitted Events** | `PaymentRecorded`, `PaymentReversed`, `PaymentAllocated`, `PaymentRefunded` |
| **Projection Targets** | `customer_balance_projections`, `revenue_projection` |
| **Reconciliation Source** | `payments` table (canonical) |
| **Rollback Behavior** | `PaymentReversed` or `PaymentRefunded` with full audit trail |
| **Idempotency Key** | `(transaction_reference, gateway_id)` |

---

## Domain: CRM / Customers

### Domain Object: Customer

| Attribute | Definition |
|-----------|------------|
| **Authoritative Write Path** | `CrmAggregate::createCustomer()` → `CustomerCreated` event |
| **Forbidden Write Paths** | Direct `Customer::create()`, CRM import bypassing events |
| **Emitted Events** | `CustomerCreated`, `CustomerUpdated`, `CustomerMerged`, `CustomerDeactivated` |
| **Projection Targets** | `customer_summary_view`, `customer_activity_timeline`, `credit_status_view` |
| **Reconciliation Source** | `customers` table (canonical) |
| **Rollback Behavior** | `CustomerDeactivated` (soft delete); merge creates `CustomerMerged` event with lineage |
| **Idempotency Key** | `(email, entreprise_id)` or `(phone, entreprise_id)` |

### Domain Object: Lead

| Attribute | Definition |
|-----------|------------|
| **Authoritative Write Path** | `LeadAggregate::create()` / `convert()` → `LeadCreated` / `LeadConverted` events |
| **Forbidden Write Paths** | Direct `Lead` model mutations, status bypasses |
| **Emitted Events** | `LeadCreated`, `LeadUpdated`, `LeadConverted`, `LeadAssigned` |
| **Projection Targets** | `lead_pipeline_view`, `conversion_summary` |
| **Reconciliation Source** | Leads table (authoritative until conversion, then archived) |
| **Rollback Behavior** | Cannot un-convert; creates new lead if needed |
| **Idempotency Key** | `(source, external_id, entreprise_id)` |

---

## Domain: Fleet / Logistics

### Domain Object: Delivery Tour

| Attribute | Definition |
|-----------|------------|
| **Authoritative Write Path** | `RouteAggregate::createTour()` → `TourCreated` → stop events |
| **Forbidden Write Paths** | Direct tour table edits, stop reordering via SQL |
| **Emitted Events** | `TourCreated`, `TourStarted`, `TourCompleted`, `StopVisited`, `StopDelivered`, `StopFailed` |
| **Projection Targets** | `delivery_board`, `driver_mobile_queue`, `delivery_performance` |
| **Reconciliation Source** | `delivery_tours` + `delivery_stops` tables |
| **Rollback Behavior** | Tour cancellation emits `TourCancelled` + unassigns orders |
| **Idempotency Key** | `(route_id, tour_date, driver_id)` |

### Domain Object: Mission (Field Visit)

| Attribute | Definition |
|-----------|------------|
| **Authoritative Write Path** | `MissionAggregate::create()` → `MissionCreated` → lifecycle events |
| **Forbidden Write Paths** | Direct mission updates, status patches |
| **Emitted Events** | `MissionCreated`, `MissionStarted`, `MissionCompleted`, `MissionCancelled`, `MissionAborted` |
| **Projection Targets** | `mission_board`, `rep_activity_view` |
| **Reconciliation Source** | `missions` table |
| **Rollback Behavior** | `MissionAborted` with reason code |
| **Idempotency Key** | `(rep_id, customer_id, scheduled_date)` |

---

## Verification Checklist

Before any code reaches production:

- [ ] Authoritative write path is the ONLY path documented here
- [ ] All forbidden paths are blocked by code review or runtime guards
- [ ] Events are emitted synchronously with transaction commit
- [ ] Projections are eventually consistent and drift-detectable
- [ ] Reconciliation source is queryable and testable
- [ ] Rollback behavior is tested in staging
- [ ] Idempotency key prevents duplicates under retry

---

## Enforcement

Code review must verify authority alignment:

1. Any new write path requires matrix entry
2. Any deviation requires architecture board exception
3. Runtime violations logged as critical errors
4. Weekly authority audit: scan for direct model saves

---

## Failure Modes

### FM-WAM-001: Direct Model Save in Controller

**Scenario:** Developer adds `Order::create()` in controller, bypassing `OrderAggregate`.

**Detection:** Static analysis flags `::create()` or `::save()` on models. Weekly audit.

**Impact:** Event not emitted. Projection stale. Replay impossible.

**Recovery:** Revert change. Backfill event if possible. Team training.

---

### FM-WAM-002: Event Emitted but Transaction Rolled Back

**Scenario:** Event published to outbox but DB transaction rolls back. Event published anyway.

**Detection:** Reconciliation shows event without corresponding state change.

**Impact:** Ghost events. Projection drift. State inconsistency.

**Recovery:** Compensating event. Outbox pattern fix (transactional).

---

### FM-WAM-003: Idempotency Key Collision Across Domains

**Scenario:** Different domains use same idempotency key format, causing false duplicates.

**Detection:** Unexpected duplicate rejections. Investigation.

**Impact:** Legitimate operations blocked. Customer impact.

**Recovery:** Key format update. Migration of affected records.

---

## Ownership and Governance

| Field | Value |
|-------|-------|
| **Owner** | Architecture Board |
| **Approver** | CTO |
| **Last Verified Date** | 2026-03-30 |
| **Verification Method** | Document review |
| **Production Criticality** | Tier 1 |
| **Rollback Impact** | Cannot roll back - authority paths in use |
| **Verification Status** | Declared |

---

**Document Version:** 1.1  
**Last Updated:** 2026-03-30  
**Next Review:** 2026-04-06
