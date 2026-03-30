# Migration Exit Criteria

## Purpose

"Transition" cannot be a permanent state. This document defines the system-level criteria that must be met before EPSILON may be described as "post-transition" or "canonical."

Without exit criteria, migration becomes an excuse for indefinite ambiguity.

---

## System Classification Evolution

| Phase | Status | Description |
|-------|--------|-------------|
| Current | **Transitional Distributed Monolith** | Canonical migration under consistency risk |
| Target | **Canonical Event-Driven Platform** | Full authority clarity, no dual-write paths |
| Exit Gate | Must pass ALL criteria below | No exceptions |

---

## Exit Criteria

### Criterion 1: Authority Coverage (100% Tier-1)

**Requirement:** 100% of Tier-1 write paths have declared single authority.

**Tier-1 Domains:**
- Inventory / Stock
- Orders / Sales
- Finance / Payments
- Customer / CRM

**Measurement:**
```
Coverage = (Tier-1 paths with declared authority) / (Total Tier-1 paths) = 1.0
```

**Evidence Required:**
- [ ] SCHEMA_AUTHORITY_REGISTER.md shows 100% A-classification for Tier-1 tables
- [ ] WRITE_AUTHORITY_MATRIX.md has entries for all Tier-1 domains
- [ ] No "C — Legacy Transitional" tables remain in Tier-1
- [ ] Static analysis confirms no direct model saves in Tier-1

**Owner:** Architecture Board  
**Verification Status:** Declared  
**Current Measurement:** ~40% (estimated)

---

### Criterion 2: Idempotence Coverage (100% Stock-Affecting)

**Requirement:** 100% of stock-affecting paths are idempotence-tested.

**Measurement:**
```
Coverage = (Stock paths with idempotence proof) / (Total stock paths) = 1.0
```

**Evidence Required:**
- [ ] All stock movement endpoints have idempotency key handling
- [ ] Duplicate event tests pass (see T-INV-003)
- [ ] Sync batch idempotence verified (T-SYNC-001)
- [ ] No stock path lacks idempotency declaration

**Owner:** Inventory Team / Platform Team  
**Verification Status:** Declared  
**Current Measurement:** ~30% (estimated)

---

### Criterion 3: Projection Trust (100% P2 Drift Detection)

**Requirement:** 100% of P2 projections have drift detection.

**P2 Projections (Operationally Critical):**
- stock_balances
- customer_balance_projections
- order_status_view
- delivery_queue
- credit_status_view
- available_stock_view
- sync_conflict_queue

**Evidence Required:**
- [ ] PROJECTION_TRUST_LEVELS.md shows P2 for all critical projections
- [ ] Drift detection jobs running and alerting
- [ ] Reconciliation queries operational
- [ ] RTO metrics meeting targets

**Owner:** Platform Team  
**Verification Status:** Declared  
**Current Measurement:** ~50% (estimated)

---

### Criterion 4: Replay Readiness (100% R3 Rebuild Procedures)

**Requirement:** 100% of R3 replays have documented and tested rebuild procedures.

**R3 Subsystems (Critical Rebuild):**
- stock_balances (R3)
- customer_balance_projections (R3)
- credit_reservations (R3)
- sync_conflict_queue (R3)
- order_status_view (R3 for validated orders)
- delivery_queue (R3)

**Evidence Required:**
- [ ] REPLAY_CLASSES.md has R3 procedures for all critical systems
- [ ] Rebuild procedures tested in staging
- [ ] Recovery Verified status achieved
- [ ] RTO < 30 minutes proven

**Owner:** Platform Team  
**Verification Status:** Declared  
**Current Measurement:** ~20% (estimated)

---

### Criterion 5: Dual-Write Elimination (Active Paths)

**Requirement:** All active dual-write paths have decommission plans.

**Active Dual-Write Paths (must be eliminated):**
- [ ] `article` → `articles` migration
- [ ] `article_mouvement` → `stock_moves` migration
- [ ] `balance_stock` → `stock_balances` migration
- [ ] Direct Eloquent stock updates bypassing aggregate
- [ ] Legacy order CRUD controller

**Evidence Required:**
- [ ] TRANSITION_KILL_CRITERIA.md shows kill condition for each path
- [ ] Zero writes to legacy tables for 30 days
- [ ] All reads migrated to canonical tables
- [ ] Deletion target dates met or exception granted by CTO

**Owner:** Architecture Board  
**Verification Status:** Declared  
**Current Measurement:** ~10% (paths still active)

---

### Criterion 6: Legacy Authority Elimination (Tier-1)

**Requirement:** Zero legacy-authoritative tables remain in Tier-1 domains.

**Legacy Tables to Eliminate or Reclassify:**
- [ ] `article` (reclassify to projection or delete)
- [ ] `article_mouvement` (reclassify to archive or delete)
- [ ] `balance_stock` (reclassify to B — Projection)
- [ ] `mouvevement` (delete)
- [ ] `mouvement_ligne` (delete)

**Evidence Required:**
- [ ] SCHEMA_AUTHORITY_REGISTER.md shows no C-class tables in Tier-1
- [ ] All Tier-1 tables are A or B classification
- [ ] Zero references to legacy tables in production code

**Owner:** Architecture Board  
**Verification Status:** Declared  
**Current Measurement:** ~30% (many C-class tables remain)

---

### Criterion 7: Sync Conflict Semantics (Explicit)

**Requirement:** Sync conflict semantics are explicit for all offline-writeable entities.

**Offline-Writeable Entities:**
- orders
- stock_movements (via sync)
- customer_updates
- mission_status
- delivery_confirmations

**Evidence Required:**
- [ ] SYNC_CONSISTENCY_MODEL.md has conflict policy for each entity
- [ ] No implicit "timestamp wins" logic in business-critical domains
- [ ] Conflict resolution registry complete
- [ ] Conflict queue monitoring operational

**Owner:** Platform Team  
**Verification Status:** Declared  
**Current Measurement:** ~40% (estimated)

---

### Criterion 8: Reconciliation Soak Period (Passed)

**Requirement:** Inventory and finance reconciliation pass for defined soak period.

**Soak Period:** 90 days without reconciliation failure

**Evidence Required:**
- [ ] Hourly stock reconciliation: 90 days zero drift
- [ ] Daily finance reconciliation: 90 days zero variance
- [ ] No manual stock corrections outside approved paths
- [ ] All reconciliations automated and monitored

**Owner:** Inventory Team / Finance Team  
**Verification Status:** Declared  
**Current Measurement:** ~0 days (reconciliation not fully operational)

---

### Criterion 9: Rollback-Tested Cutover (Every Boundary)

**Requirement:** Rollback-tested cutover exists for every remaining transition boundary.

**Transition Boundaries:**
- [ ] Article table cutover (legacy → canonical)
- [ ] Stock movement cutover (legacy → event-sourced)
- [ ] Stock balance cutover (dual-write → projection)
- [ ] Order API cutover (legacy → aggregate)
- [ ] Sync protocol cutover (v1 → v2)

**Evidence Required:**
- [ ] Each cutover has rollback procedure tested in staging
- [ ] Recovery Verified status for each boundary
- [ ] Rollback decision tree documented (when to rollback vs. forward-fix)
- [ ] Emergency rollback can execute in < 30 minutes

**Owner:** Architecture Board  
**Verification Status:** Declared  
**Current Measurement:** ~20% (most cutovers untested)

---

### Criterion 10: Invariant Compliance (CI/CD)

**Requirement:** All invariants pass in CI/CD pipeline.

**Invariant Test Suite:**
- [ ] INV-001 (Stock Balance Equivalence)
- [ ] INV-003 (Duplicate Immunity)
- [ ] INV-101 (Order Lifecycle)
- [ ] INV-201 (Journal Immutability)
- [ ] INV-301 (Idempotence Guarantee)
- [ ] INV-401 (Event Immutability)

**Evidence Required:**
- [ ] INVARIANT_REGISTRY.md tests integrated into CI/CD
- [ ] Invariant violations block merge
- [ ] Daily invariant test report published
- [ ] Chaos tests for critical invariants operational

**Owner:** Platform Team  
**Verification Status:** Declared  
**Current Measurement:** ~10% (tests not yet automated)

---

## Measurement Dashboard

### Current Status Summary

| Criterion | Target | Current | Status |
|-----------|--------|---------|--------|
| 1. Authority Coverage (Tier-1) | 100% | ~40% | 🔴 |
| 2. Idempotence Coverage (Stock) | 100% | ~30% | 🔴 |
| 3. P2 Projection Drift Detection | 100% | ~50% | 🟡 |
| 4. R3 Rebuild Procedures | 100% | ~20% | 🔴 |
| 5. Dual-Write Elimination | 100% | ~10% | 🔴 |
| 6. Legacy Authority Elimination | 0 legacy | ~30% | 🔴 |
| 7. Sync Conflict Semantics | 100% | ~40% | 🔴 |
| 8. Reconciliation Soak Period | 90 days | 0 days | 🔴 |
| 9. Rollback-Tested Cutover | 100% | ~20% | 🔴 |
| 10. Invariant Compliance | 100% | ~10% | 🔴 |

**Overall Progress:** ~25% estimated

**Projected Exit Date:** Not yet estimable (need detailed assessment)

---

## Exit Gate Process

### Stage 1: Criterion Assessment (Ongoing)

- Weekly measurement of each criterion
- Dashboard updated every Monday
- Deviations flagged for architecture board

### Stage 2: Criterion Verification (Per Criterion)

When team claims criterion met:

1. Evidence package submitted to Architecture Board
2. Independent verification performed
3. Verification Status promoted to "Recovery Verified"
4. Criterion signed off or returned for remediation

### Stage 3: Exit Gate Review (All Criteria)

When all criteria claim "met":

1. 30-day pre-exit soak period
2. Full architecture board review
3. External audit (optional but recommended)
4. Go/No-Go decision
5. System reclassification if approved

### Stage 4: Post-Exit Monitoring

After exit approval:

- Monthly invariant verification
- Quarterly authority audit
- Annual external review

---

## Risk Factors Preventing Exit

### Current Blockers

| Blocker | Impact | Mitigation |
|---------|--------|------------|
| Legacy warehouse system writes directly to `article_mouvement` | Blocks Criterion 5, 6 | Integration timeline Q2 2026 |
| 3rd party integration on legacy product schema | Blocks Criterion 6 | Negotiation in progress |
| Mobile app v2.1 reads `balance_stock` directly | Blocks Criterion 6 | App update scheduled Q2 2026 |
| 47 devices still on sync v1 | Blocks Criterion 7 | Remote upgrade logistics |
| Chaos testing not yet implemented | Blocks Criterion 4, 10 | Platform team Q2 priority |

---

## Pre-Exit Warnings

Do NOT claim "post-transition" status if:

- Any criterion is partially met (must be 100%)
- Any stop-ship condition is triggered
- Reconciliation has not passed 90-day soak
- Rollback procedures are untested
- Team cannot explain every stock number's derivation path

---

## Post-Exit Architecture Statement

Once exit criteria met, EPSILON may be described as:

> **EPSILON is a canonical event-driven operational platform with provable authority, deterministic replay, and invariant-preserving sync.**

That description is earned, not assumed.

---

**Document Version:** 1.0  
**Owner:** Architecture Board  
**Assessment Frequency:** Weekly  
**Next Assessment:** 2026-04-06  
**Target Exit Date:** TBD (pending detailed assessment)
