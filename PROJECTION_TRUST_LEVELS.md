# Projection Trust Levels

## Purpose

Not all read models are equally valid. This document classifies projections into trust levels (P0/P1/P2) with associated requirements.

**Rule:** No P2 projection may be trusted unless drift detection exists.

---

## Trust Level Definitions

### P0 — Non-Authoritative Convenience

**Description:** Dashboards, widgets, mobile summaries. If wrong temporarily, operations continue.

**Requirements:**
- Rebuildable from source events
- No operational dependency
- Stale data acceptable up to 1 hour

**Recovery:** Simple rebuild, no operational impact

---

### P1 — Operationally Important but Reconstructible

**Description:** Order status views, customer timelines, mission queues. If stale, operations degrade but can recover.

**Requirements:**
- Rebuildable from source events
- Lag detection (alert if > 5 minutes)
- Operations can continue with stale data
- Manual recovery procedure documented

**Recovery:** Rebuild with possible temporary UI inconsistency

---

### P2 — Operationally Critical

**Description:** Stock balances, receivable status, delivery execution queues. These require strict guarantees.

**Requirements:**
- [ ] Replay validation exists
- [ ] Drift monitoring active
- [ ] Rebuild procedures documented
- [ ] Reconciliation jobs running
- [ ] Lag detection (alert if > 30 seconds)
- [ ] Recovery RTO defined

**Recovery:** Controlled rebuild with operational coordination

---

## Projection Register

### P2 — Operationally Critical

| Projection | Source Events | Drift Detection | Rebuild Time | Owner |
|------------|---------------|-----------------|--------------|-------|
| `stock_balances` | `stock_moves` | Hourly reconciliation | 10 min | Inventory Team |
| `customer_balance_projections` | `journal_entries`, `payments` | Daily reconciliation | 15 min | Finance Team |
| `order_status_view` | `orders`, `order_lines` | Real-time lag check | 5 min | Sales Team |
| `delivery_queue` | `delivery_tours`, `delivery_stops` | 5-minute lag check | 3 min | Logistics Team |
| `credit_status_view` | `credit_reservations`, `payments` | Real-time | 2 min | Finance Team |
| `available_stock_view` | `stock_balances` + `stock_reservations` | Real-time calculation | N/A (computed) | Inventory Team |
| `sync_conflict_queue` | `sync_conflicts` | Real-time depth check | N/A | Platform Team |

### P1 — Operationally Important

| Projection | Source Events | Lag Alert | Owner |
|------------|---------------|-----------|-------|
| `order_timeline_view` | `orders` + related events | 5 min | CRM Team |
| `customer_activity_summary` | Multiple event types | 10 min | CRM Team |
| `rep_performance_view` | `missions`, `orders` | 15 min | Sales Team |
| `product_sales_summary` | `order_lines` | 30 min | Analytics |
| `inventory_turnover_view` | `stock_moves`, `orders` | 1 hour | Inventory Team |
| `mission_board` | `missions` | 5 min | Field Ops |
| `vehicle_location_history` | `vehicle_locations` | 1 min | Fleet Team |

### P0 — Non-Authoritative Convenience

| Projection | Source Events | Refresh | Owner |
|------------|---------------|---------|-------|
| `dashboard_widgets` | Various | 1 hour | Analytics |
| `executive_summary_view` | Aggregated metrics | 4 hours | Analytics |
| `mobile_home_summary` | Cached aggregates | 30 min | Mobile Team |
| `reporting_cubes` | Event aggregates | Daily | Analytics |
| `analytics_metrics` | Multiple streams | 1 hour | Analytics |
| `trending_products_view` | `order_lines` | 2 hours | Marketing |
| `geographic_heat_map` | `orders`, `customers` | 4 hours | Marketing |

---

## Drift Detection Requirements by Level

### P2 Drift Detection (Mandatory)

**Definition:**
```
drift = | projection_value - source_of_truth_value |
acceptable_drift = 0 (for quantities) or tolerance defined by domain
```

**Implementation:**
- Continuous reconciliation job
- Alert on any mismatch
- Auto-rebuild trigger
- Incident escalation

**Example — Stock Balances:**
```sql
-- Hourly reconciliation
SELECT 
  article_id, 
  depot_id,
  ABS(stored_balance - projected_balance) as drift
FROM reconciliation_view
WHERE drift > 0.001;
```

### P1 Lag Detection (Mandatory)

**Definition:**
```
lag = now() - last_projected_event_timestamp
acceptable_lag = 5 minutes (configurable by projection)
```

**Implementation:**
- Lag gauge metric
- Alert if lag > threshold
- No auto-action (operations can continue)

### P0 No Detection Required

**Policy:** Best effort, rebuild on complaint.

---

## Rebuild Procedures

### R3 — Critical Rebuild (P2 Projections)

Applies to: P2 projections (stock, finance, critical operations)

**Procedure:**

1. **Pre-rebuild**
   - [ ] Announce maintenance window
   - [ ] Pause dependent operations (if required)
   - [ ] Create backup of current projection
   - [ ] Verify event store accessibility

2. **Rebuild**
   - [ ] Stop projector consumption
   - [ ] Truncate or shadow-copy projection table
   - [ ] Replay events from position 0
   - [ ] Verify drift = 0
   - [ ] Performance check (query time < 100ms)

3. **Cutover**
   - [ ] Switch read traffic to rebuilt projection
   - [ ] Resume projector consumption from new position
   - [ ] Monitor lag for 5 minutes
   - [ ] Verify no errors

4. **Post-rebuild**
   - [ ] Archive old projection
   - [ ] Log rebuild metrics (duration, event count, drift)
   - [ ] Close maintenance window

**RTO Target:** 10 minutes for 1M events

### R2 — Controlled Rebuild (P1 Projections)

**Procedure:**
- Stop projector
- Truncate and replay
- Resume
- No maintenance window required

**RTO Target:** 5 minutes

### R1 — Safe Rebuild (P0 Projections)

**Procedure:**
- Background rebuild
- Atomic swap
- No coordination required

**RTO Target:** N/A (best effort)

---

## Trust Level Escalation

### When to Promote (P0 → P1 → P2)

A projection may be promoted when:

- [ ] Drift detection implemented and proven
- [ ] Rebuild procedure tested in staging
- [ ] Recovery RTO meets requirements
- [ ] Operations team trained on procedures
- [ ] Monitoring dashboards configured
- [ ] Runbook documented

### When to Demote (P2 → P1 → P0)

A projection may be demoted when:

- [ ] No longer operationally critical
- [ ] Drift detection cannot be maintained
- [ ] Rebuild SLA cannot be met
- [ ] Alternative projection available

---

## Forbidden Practices

### Never Do

1. **Use P2 projection as write source**
   ```php
   // FORBIDDEN
   $balance = StockBalance::find($id);
   $balance->quantity -= 10;  // NEVER
   $balance->save();
   ```

2. **Manual SQL patch of projection**
   ```sql
   -- FORBIDDEN
   UPDATE stock_balances SET quantity = 100 WHERE id = 123;
   ```

3. **Assume projection is real-time without verification**
   - Always check lag metric
   - Never assume P2 projection is current

4. **Skip drift detection for P2**
   - Every P2 MUST have drift detection
   - No exceptions

---

## Metrics and Monitoring

### Required Metrics

| Metric | Type | Alert | All Levels |
|--------|------|-------|------------|
| `projection_lag_seconds` | Gauge | P2: >30s, P1: >5min | P1, P2 |
| `projection_drift_count` | Counter | >0 | P2 only |
| `projection_rebuild_duration` | Histogram | >10min | P2 only |
| `projection_rebuild_failures` | Counter | >0 | P2 only |
| `projection_query_p99` | Histogram | >100ms | P2 only |

### Dashboard Requirements

**P2 Projections:**
- Real-time lag chart
- Drift alert panel
- Last rebuild timestamp
- Rebuild job status

**P1 Projections:**
- Lag chart (5-minute granularity)
- Rebuild availability indicator

**P0 Projections:**
- Last refresh timestamp
- No alerting required

---

## Code Review Checklist

For any new projection:

- [ ] Trust level assigned (P0/P1/P2)
- [ ] Source events documented
- [ ] If P2: Drift detection implemented
- [ ] If P2: Rebuild procedure documented
- [ ] If P1/P2: Lag detection implemented
- [ ] Metrics exposed
- [ ] Owner assigned
- [ ] Runbook entry created

---

## Enforcement

**Architecture Board Responsibilities:**
- Review all new projections for trust level appropriateness
- Audit P2 projections quarterly
- Verify drift detection is operational
- Approve trust level changes

**Automated Enforcement:**
- Static analysis: Flag direct projection mutations
- Runtime: Alert on drift > 0
- CI: Block builds with untrusted P2 projections

---

## Failure Modes

### FM-PROJ-001: Drift Detection False Positive

**Scenario:** Reconciliation query bug causes false drift alert.

**Detection:** Drift alert fires. Investigation shows query error.

**Impact:** Unnecessary rebuild. Operational overhead. Alert fatigue.

**Recovery:** Fix reconciliation query. Recalculate. Cancel rebuild.

---

### FM-PROJ-002: Projection Lag Cascade

**Scenario:** Slow downstream consumer causes projection lag. P2 projection stale for hours.

**Detection:** Lag metric > threshold. Alert fires.

**Impact:** Decisions based on stale data. Stock oversell. Financial errors.

**Recovery:**
- Kill slow consumer
- Queue for investigation
- Rebuild projection if needed

---

### FM-PROJ-003: Drift Detection Silent Failure

**Scenario:** Drift detection job fails silently. Drift accumulates undetected.

**Detection:** Manual audit finds discrepancy. Alert system failed.

**Impact:** Significant drift. Complex recovery. Customer impact.

**Recovery:**
- Fix monitoring
- Full rebuild
- Post-incident review

**Prevention:** Monitor the monitor. Heartbeat checks on drift detection.

---

## Ownership and Governance

| Field | Value |
|-------|-------|
| **Owner** | Platform Team |
| **Approver** | Architecture Board |
| **Last Verified Date** | 2026-03-30 |
| **Verification Method** | Document review |
| **Production Criticality** | Tier 1 |
| **Rollback Impact** | Cannot roll back - trust levels in use |
| **Verification Status** | Declared |

---

**Document Version:** 1.1  
**Owner:** Platform Team  
**Review Cycle:** Monthly
