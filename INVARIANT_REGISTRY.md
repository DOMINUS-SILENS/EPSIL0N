# Invariant Registry

## Purpose

Architecture ultimately exists to preserve invariants, not folder structures. This document defines all non-negotiable business and consistency truths for EPSILON.

**Rule:** Every invariant violation is a stop-ship condition.

---

## Invariant Entry Format

Every invariant must include:

- **Invariant ID** - Unique identifier (e.g., INV-001)
- **Domain** - Business domain (Inventory, Order, Finance, etc.)
- **Statement** - Clear, testable assertion
- **Authority Source** - Where this truth originates
- **Enforcement Layer** - Where checked (Domain / DB / Projector / Reconciliation / Sync)
- **Test Coverage** - Test IDs that verify this
- **Failure Impact** - What breaks if violated
- **Recovery Procedure** - How to recover from violation
- **Owner** - Team responsible
- **Verification Status** - Declared / Partially Verified / Operationally Verified / Recovery Verified

---

## Inventory Invariants

### INV-001: Stock Balance Equivalence

| Field | Value |
|-------|-------|
| **Invariant ID** | INV-001 |
| **Domain** | Inventory |
| **Statement** | For all (article_id, depot_id): projected_balance = Σ(accepted_stock_events.quantity) |
| **Authority Source** | `stock_moves` event stream |
| **Enforcement Layer** | Reconciliation (hourly), Projector (event processing) |
| **Test Coverage** | T-INV-001, T-INV-001-A (chaos) |
| **Failure Impact** | Stock drift → oversell/undersell → customer impact → financial loss |
| **Recovery Procedure** | Halt stock-affecting operations, rebuild projection from event zero, verify drift = 0, resume |
| **Owner** | Inventory Team |
| **Verification Status** | Partially Verified |

### INV-002: Replay Determinism

| Field | Value |
|-------|-------|
| **Invariant ID** | INV-002 |
| **Domain** | Inventory |
| **Statement** | Given same ordered set of events, final balance is always identical |
| **Authority Source** | Event Store immutability |
| **Enforcement Layer** | Projector (deterministic apply logic), Daily replay test |
| **Test Coverage** | T-INV-002 |
| **Failure Impact** | Non-determinism makes debugging impossible, recovery unreliable |
| **Recovery Procedure** | Root cause analysis, fix projector logic, rebuild |
| **Owner** | Platform Team |
| **Verification Status** | Declared |

### INV-003: Duplicate Immunity

| Field | Value |
|-------|-------|
| **Invariant ID** | INV-003 |
| **Domain** | Inventory |
| **Statement** | Same event ingested twice (same idempotency key) produces no balance change on second ingestion |
| **Authority Source** | Idempotency service |
| **Enforcement Layer** | Sync ingestion, API layer, Idempotency check |
| **Test Coverage** | T-INV-003, T-SYNC-001 (duplicate batch) |
| **Failure Impact** | Duplicate stock issue → oversell → customer shortage |
| **Recovery Procedure** | Compensating event if detected, idempotency fix |
| **Owner** | Platform Team |
| **Verification Status** | Partially Verified |

### INV-004: Out-of-Order Convergence

| Field | Value |
|-------|-------|
| **Invariant ID** | INV-004 |
| **Domain** | Inventory |
| **Statement** | Events arriving out of order converge to same balance as if ordered (for commutative operations) |
| **Authority Source** | Event Store with vector clocks |
| **Enforcement Layer** | Sync ordering, Causal tracking |
| **Test Coverage** | T-INV-004 (chaos ordering test) |
| **Failure Impact** | Stock drift under network partition |
| **Recovery Procedure** | Causal replay, conflict resolution per Sync Consistency Model |
| **Owner** | Platform Team |
| **Verification Status** | Declared |

### INV-005: Compensating Event Preservation

| Field | Value |
|-------|-------|
| **Invariant ID** | INV-005 |
| **Domain** | Inventory |
| **Statement** | Original event + Compensating event = Net zero balance effect, with both events retained in audit trail |
| **Authority Source** | Event Store append-only |
| **Enforcement Layer** | Domain logic (no hard deletes), Audit logging |
| **Test Coverage** | T-INV-005 |
| **Failure Impact** | Silent deletion destroys audit trail, compliance risk |
| **Recovery Procedure** | None needed (invariant enforced by design) |
| **Owner** | Platform Team |
| **Verification Status** | Declared |

### INV-006: Projection Non-Authority

| Field | Value |
|-------|-------|
| **Invariant ID** | INV-006 |
| **Domain** | Inventory |
| **Statement** | No code may treat `stock_balances` projection as source of truth for writes |
| **Authority Source** | Write Authority Matrix |
| **Enforcement Layer** | Static analysis, Runtime guards, Code review |
| **Test Coverage** | T-INV-006 (static analysis test) |
| **Failure Impact** | Dual-write chaos, divergence, unrecoverable state |
| **Recovery Procedure** | Rollback violating change, enforce static analysis |
| **Owner** | Architecture Board |
| **Verification Status** | Declared |

### INV-007: No Negative Stock (Configurable)

| Field | Value |
|-------|-------|
| **Invariant ID** | INV-007 |
| **Domain** | Inventory |
| **Statement** | For all (article_id, depot_id) where stock_tracking_enabled: balance >= 0 |
| **Authority Source** | Domain rules per depot |
| **Enforcement Layer** | StockAggregate (validation), DB constraint (optional) |
| **Test Coverage** | T-INV-007 |
| **Failure Impact** | Oversell, fulfillment failure, customer impact |
| **Recovery Procedure** | Emergency backorder creation, stock adjustment with approval |
| **Owner** | Inventory Team |
| **Verification Status** | Declared |

---

## Order Invariants

### INV-101: Order Lifecycle Validity

| Field | Value |
|-------|-------|
| **Invariant ID** | INV-101 |
| **Domain** | Order |
| **Statement** | Validated orders cannot return to draft. Shipped orders cannot be cancelled (only returned). |
| **Authority Source** | Order state machine |
| **Enforcement Layer** | OrderAggregate (state validation) |
| **Test Coverage** | T-ORD-001, T-ORD-002 |
| **Failure Impact** | State confusion, fulfillment errors, financial mismatch |
| **Recovery Procedure** | State correction via compensating event (rare, audited) |
| **Owner** | Sales Team |
| **Verification Status** | Declared |

### INV-102: Order Line Total Consistency

| Field | Value |
|-------|-------|
| **Invariant ID** | INV-102 |
| **Domain** | Order |
| **Statement** | order.total = Σ(order_lines.quantity × order_lines.unit_price × (1 - discount)) + taxes + shipping |
| **Authority Source** | Order calculation logic |
| **Enforcement Layer** | OrderAggregate (calculation), Reconciliation (daily) |
| **Test Coverage** | T-ORD-003 |
| **Failure Impact** | Financial discrepancy, customer billing errors |
| **Recovery Procedure** | Recalculation, price adjustment event if needed |
| **Owner** | Sales Team |
| **Verification Status** | Declared |

---

## Finance Invariants

### INV-201: Journal Entry Immutability

| Field | Value |
|-------|-------|
| **Invariant ID** | INV-201 |
| **Domain** | Finance |
| **Statement** | Posted journal entries are immutable. Corrections require reversal + new entry. |
| **Authority Source** | Accounting principles |
| **Enforcement Layer** | JournalAggregate (no edits after post), DB constraint |
| **Test Coverage** | T-FIN-001 |
| **Failure Impact** | Audit failure, compliance violation, Sarbanes-Oxley risk |
| **Recovery Procedure** | Reversal and re-post only |
| **Owner** | Finance Team |
| **Verification Status** | Declared |

### INV-202: Customer Balance Accuracy

| Field | Value |
|-------|-------|
| **Invariant ID** | INV-202 |
| **Domain** | Finance |
| **Statement** | customer_balance = Σ(journal_entries.debits) - Σ(journal_entries.credits) for customer scope |
| **Authority Source** | `journal_entries` event stream |
| **Enforcement Layer** | Reconciliation (daily), Projector (real-time) |
| **Test Coverage** | T-FIN-002 |
| **Failure Impact** | Incorrect receivables, bad debt, customer disputes |
| **Recovery Procedure** | Rebuild balance projection, verify against journal |
| **Owner** | Finance Team |
| **Verification Status** | Declared |

### INV-203: Payment Allocation Integrity

| Field | Value |
|-------|-------|
| **Invariant ID** | INV-203 |
| **Domain** | Finance |
| **Statement** | Payment cannot exceed outstanding receivable unless explicit overpayment flow used |
| **Authority Source** | Payment rules |
| **Enforcement Layer** | PaymentAggregate (validation) |
| **Test Coverage** | T-FIN-003 |
| **Failure Impact** | Overpayment mishandling, customer refund errors |
| **Recovery Procedure** | Refund processing, balance correction |
| **Owner** | Finance Team |
| **Verification Status** | Declared |

---

## Sync / Consistency Invariants

### INV-301: Idempotence Guarantee

| Field | Value |
|-------|-------|
| **Invariant ID** | INV-301 |
| **Domain** | Sync |
| **Statement** | Same sync operation cannot apply twice (same idempotency key = single execution) |
| **Authority Source** | Idempotency service |
| **Enforcement Layer** | Sync ingestion, API middleware |
| **Test Coverage** | T-SYNC-001, T-SYNC-002 (retry storm) |
| **Failure Impact** | Duplicate orders, duplicate stock issues, customer impact |
| **Recovery Procedure** | Compensating events if detected, idempotency fix |
| **Owner** | Platform Team |
| **Verification Status** | Partially Verified |

### INV-302: Aggregate Version Uniqueness

| Field | Value |
|-------|-------|
| **Invariant ID** | INV-302 |
| **Domain** | Sync / Event Store |
| **Statement** | Same aggregate version cannot be committed twice (optimistic concurrency) |
| **Authority Source** | Event store sequence |
| **Enforcement Layer** | EventStore::append (sequence check) |
| **Test Coverage** | T-SYNC-003 |
| **Failure Impact** | Lost updates, inconsistent state |
| **Recovery Procedure** | Conflict resolution, retry with new version |
| **Owner** | Platform Team |
| **Verification Status** | Declared |

### INV-303: Causal Ordering Preservation

| Field | Value |
|-------|-------|
| **Invariant ID** | INV-303 |
| **Domain** | Sync |
| **Statement** | Causally dependent events are processed in order (if A → B, then A processed before B) |
| **Authority Source** | Vector clocks / causality tracking |
| **Enforcement Layer** | Sync ordering, Event routing |
| **Test Coverage** | T-SYNC-004 |
| **Failure Impact** | State inconsistency, business logic violations |
| **Recovery Procedure** | Causal replay, dependency resolution |
| **Owner** | Platform Team |
| **Verification Status** | Declared |

---

## Event Store Invariants

### INV-401: Event Immutability

| Field | Value |
|-------|-------|
| **Invariant ID** | INV-401 |
| **Domain** | Event Store |
| **Statement** | Events in event_store are append-only and immutable. No updates, no deletes. |
| **Authority Source** | Event Store design |
| **Enforcement Layer** | DB permissions (INSERT only), Application layer |
| **Test Coverage** | T-EVT-001 |
| **Failure Impact** | Audit trail corruption, compliance failure |
| **Recovery Procedure** | None (violation is catastrophic, requires investigation) |
| **Owner** | Platform Team |
| **Verification Status** | Declared |

### INV-402: Replay Safety

| Field | Value |
|-------|-------|
| **Invariant ID** | INV-402 |
| **Domain** | Event Store |
| **Statement** | Replaying accepted event stream must not alter canonical state (idempotent replay) |
| **Authority Source** | Event sourcing principles |
| **Enforcement Layer** | Projectors (idempotent apply), Idempotency service |
| **Test Coverage** | T-EVT-002, T-EVT-003 (full replay) |
| **Failure Impact** | State corruption on recovery, unrecoverable drift |
| **Recovery Procedure** | Projector fix, rebuild |
| **Owner** | Platform Team |
| **Verification Status** | Declared |

---

## Enforcement

### Invariant Testing Requirements

Every invariant must have:

1. **Unit Test** - Verifies logic in isolation
2. **Integration Test** - Verifies in system context
3. **Chaos Test** (for critical invariants) - Verifies under failure conditions

### Invariant Violation Response

| Severity | Response | Timeframe |
|----------|----------|-----------|
| Critical (INV-001, INV-003, INV-301) | Immediate halt of affected operations | < 5 minutes |
| High (INV-101, INV-201, INV-401) | Urgent investigation, potential rollback | < 1 hour |
| Medium | Sprint priority fix | < 1 week |

### Continuous Verification

- Daily reconciliation jobs verify INV-001, INV-202
- Hourly drift detection monitors projection invariants
- CI/CD invariant tests run on every build

---

## Invariant Amendment Process

1. New invariant requires architecture board approval
2. Must include test coverage before addition
3. Removal of invariant requires replacement or risk acceptance
4. Amendment logged with date, reason, and approver

---

**Document Version:** 1.0  
**Owner:** Architecture Board  
**Last Verified:** 2026-03-30  
**Next Review:** 2026-04-06  
**Verification Status:** Declared
