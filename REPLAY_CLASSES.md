# Replay Classes

## Purpose

Not all replay is equal. This document defines formal replay categories (R1/R2/R3) with associated procedures and risk levels.

**Rule:** If your team says "just replay it" without replay classing, they are not operating a serious system.

---

## Replay Class Definitions

### R1 — Safe Full Rebuild

**Description:** Projection can be dropped and rebuilt from canonical truth without operational impact.

**Characteristics:**
- No active operational dependency
- Stale data acceptable during rebuild
- No coordination required
- Background rebuild acceptable

**Examples:**
- Reporting views
- Dashboard summaries
- Analytics cubes
- Historical aggregations
- Non-critical metrics

**Risk Level:** Low

---

### R2 — Controlled Rebuild

**Description:** Projection rebuild affects operations and must be gated, but operations can continue with degraded experience.

**Characteristics:**
- Operations can continue (possibly with stale data)
- Rebuild should be announced
- Prefer maintenance window
- Rollback procedure required

**Examples:**
- Order operational views
- Customer activity timelines
- Mission queues
- Sales pipeline views
- Performance dashboards

**Risk Level:** Medium

---

### R3 — Critical Rebuild

**Description:** Rebuild could affect stock, finance, or device convergence. Requires strict controls.

**Characteristics:**
- Must use checkpointing
- Requires reconciliation assertions
- Controlled execution window
- Post-rebuild validation required
- Operational coordination mandatory
- Rollback plan tested

**Examples:**
- Stock balances
- Payment state
- Sync conflict state
- Credit reservations
- Customer financial balances
- Delivery execution queues

**Risk Level:** High

---

## Replay Class Register

### R3 — Critical Rebuilds

| Projection/State | Domain | Rebuild Time | Checkpoint | Validation |
|------------------|--------|--------------|------------|------------|
| `stock_balances` | Inventory | 10 min | Every 10k events | Drift = 0 |
| `customer_balance_projections` | Finance | 15 min | Every 5k events | Reconciliation |
| `credit_reservations` | Finance | 5 min | Every 1k events | Sum validation |
| `sync_conflict_queue` | Platform | 2 min | End of batch | Queue depth |
| `order_status_view` (validated orders) | Sales | 5 min | Every 5k events | Count match |
| `delivery_queue` | Logistics | 3 min | Every 1k events | Active tours |
| `saga_state` | Orchestration | 5 min | Every state change | State consistency |

### R2 — Controlled Rebuilds

| Projection | Domain | Rebuild Time | Coordination |
|------------|--------|--------------|--------------|
| `order_timeline_view` | CRM | 8 min | Notify users |
| `customer_activity_summary` | CRM | 10 min | Best effort |
| `rep_performance_view` | Sales | 12 min | Report delay OK |
| `mission_board` | Field Ops | 5 min | Announce refresh |
| `vehicle_tracking_view` | Fleet | 3 min | Real-time gap OK |
| `inventory_availability` | Inventory | 7 min | Cache extension |
| `product_sales_summary` | Analytics | 15 min | Delay acceptable |

### R1 — Safe Full Rebuilds

| Projection | Domain | Rebuild Time | Notes |
|------------|--------|--------------|-------|
| `analytics_dashboards` | Analytics | 30 min | Background only |
| `executive_summary` | Analytics | 20 min | Daily refresh |
| `trending_products` | Marketing | 25 min | Recalculate |
| `geographic_heat_map` | Marketing | 15 min | Batch process |
| `reporting_cubes` | Analytics | 45 min | Overnight job |
| `mobile_cached_summaries` | Mobile | 10 min | On-demand refresh |

---

## Replay Procedures by Class

### R1 Procedure — Safe Full Rebuild

**Pre-rebuild:**
1. [ ] Verify event store accessible
2. [ ] Check disk space for projection

**Rebuild:**
1. [ ] Create new projection table (shadow)
2. [ ] Replay events from position 0
3. [ ] Atomic swap old/new projection
4. [ ] Drop old projection

**Post-rebuild:**
1. [ ] Log completion
2. [ ] No validation required

**Rollback:**
- Swap back to old projection

---

### R2 Procedure — Controlled Rebuild

**Pre-rebuild:**
1. [ ] Announce to operations team
2. [ ] Verify maintenance window (optional)
3. [ ] Create backup of current projection
4. [ ] Pause non-critical jobs

**Rebuild:**
1. [ ] Stop projector consumption
2. [ ] Mark projection as "rebuilding" (UI indicator)
3. [ ] Truncate or shadow-copy projection
4. [ ] Replay events from position 0
5. [ ] Basic validation (row counts, sample checks)
6. [ ] Resume projector from last replayed position

**Post-rebuild:**
1. [ ] Clear "rebuilding" indicator
2. [ ] Notify operations team
3. [ ] Monitor for 5 minutes

**Rollback:**
- Restore from backup
- Resume projector

---

### R3 Procedure — Critical Rebuild

**Pre-rebuild (Mandatory):**
1. [ ] Architecture board approval
2. [ ] Maintenance window scheduled
3. [ ] Operations team notified
4. [ ] Dependent systems notified
5. [ ] Full backup of projection
6. [ ] Event store integrity verified
7. [ ] Rollback procedure tested in staging

**Rebuild:**
1. [ ] Pause dependent operations
2. [ ] Stop projector consumption
3. [ ] Record current projection state (for validation)
4. [ ] Create shadow projection table
5. [ ] Replay with checkpointing:
   - Every N events: save checkpoint
   - On failure: resume from checkpoint
6. [ ] Validate at each checkpoint:
   - Drift = 0
   - Invariants hold
   - Counts match expected
7. [ ] Final validation:
   - Full reconciliation
   - Sample data verification
   - Performance test
8. [ ] Atomic cutover to new projection
9. [ ] Resume projector from new position
10. [ ] Resume dependent operations

**Post-rebuild:**
1. [ ] Monitor for 15 minutes
2. [ ] Reconciliation job runs
3. [ ] Drift detection verified
4. [ ] Archive old projection (retain 7 days)
5. [ ] Document rebuild metrics
6. [ ] Close maintenance window

**Rollback (if validation fails):**
1. [ ] Immediate cutover to backup
2. [ ] Resume projector from pre-rebuild position
3. [ ] Resume operations
4. [ ] Root cause analysis

---

## Checkpointing Strategy

### R3 Checkpoint Requirements

**Definition:** Save intermediate state during replay for failure recovery.

**Implementation:**
```php
class CheckpointService {
    public function saveCheckpoint(string $projection, int $eventPosition, array $state): void
    {
        DB::table('replay_checkpoints')->insert([
            'projection' => $projection,
            'event_position' => $eventPosition,
            'state_hash' => hash('sha256', json_encode($state)),
            'created_at' => now(),
        ]);
    }
    
    public function getLastCheckpoint(string $projection): ?array
    {
        return DB::table('replay_checkpoints')
            ->where('projection', $projection)
            ->orderBy('event_position', 'desc')
            ->first();
    }
}
```

**Checkpoint Frequency:**
- R3: Every 1k-10k events (configurable by projection)
- R2: Every 10k events
- R1: No checkpointing required

---

## Validation Requirements

### R3 Validation (Mandatory)

**Level 1 — Checkpoint Validation:**
```
At each checkpoint:
- State hash matches expected
- Row counts in range
- No constraint violations
```

**Level 2 — Final Validation:**
```
After complete replay:
- Full reconciliation with source events
- Drift = 0
- Invariant tests pass
- Sample queries return expected results
- Performance benchmarks met
```

**Level 3 — Operational Validation:**
```
After cutover:
- Dependent systems operational
- No error spikes
- Lag metrics normal
```

### Validation Tests

```php
class ProjectionValidationTest {
    public function testStockBalanceReconciliation(): void
    {
        $projected = StockBalance::sum('quantity');
        $fromEvents = StockMove::sum(DB::raw('quantity * direction'));
        
        $this->assertEqualsWithDelta(
            $fromEvents, 
            $projected, 
            0.001,
            'Stock balance drift detected'
        );
    }
}
```

---

## Replay Safety Checklist

Before any replay:

- [ ] Replay class identified (R1/R2/R3)
- [ ] Procedure documented
- [ ] For R2/R3: Maintenance window scheduled
- [ ] For R3: Architecture board approval
- [ ] Backup created (R2/R3)
- [ ] Rollback procedure tested (R3)
- [ ] Checkpoints configured (R3)
- [ ] Validation tests ready
- [ ] Monitoring dashboards open
- [ ] Operations team on standby (R3)

---

## Emergency Replay Protocol

### When Drift Detected in P2 Projection

**Immediate (0-5 minutes):**
1. Alert on-call engineer
2. Pause dependent writes (if drift > threshold)
3. Capture current state for analysis

**Assessment (5-15 minutes):**
1. Determine drift scope (single record vs systemic)
2. Identify root cause (event loss, bug, corruption)
3. Decide: patch vs rebuild

**If Rebuild Required:**
1. Escalate to architecture board
2. Schedule emergency maintenance
3. Follow R3 procedure
4. Post-incident review within 24 hours

---

## Metrics

### Replay Metrics to Track

| Metric | Description | Alert |
|--------|-------------|-------|
| `replay_duration_seconds` | Time to rebuild | R3 > 30 min |
| `replay_events_per_second` | Replay throughput | < 1000/s |
| `replay_checkpoint_lag` | Events since last checkpoint | R3 > 10k |
| `replay_validation_failures` | Validation errors | > 0 |
| `replay_rollback_count` | Rollbacks performed | > 0 (R3) |
| `replay_drift_detected` | Drift found post-rebuild | > 0 |

---

## Forbidden Phrases

In replay context, ban these without proof:

| Phrase | Required Proof |
|--------|--------------|
| "just replay it" | Replay class assigned, procedure documented |
| "we can rebuild anytime" | RTO proven, rollback tested |
| "replay is safe" | Validation suite passes |
| "it will converge" | Convergence test results |
| "no need for checkpoints" | R1 classification only |

---

## Failure Modes

### FM-REP-001: Checkpoint Corruption

**Scenario:** Checkpoint file corrupted. Cannot resume from last position.

**Detection:** Checksum mismatch on checkpoint read.

**Impact:** Replay must restart from position 0. Extended downtime.

**Recovery:** Replay from zero. Extended RTO.

**Prevention:** Multiple checkpoint copies. Checksum verification.

---

### FM-REP-002: Validation Failure Mid-Replay

**Scenario:** R3 replay at 50% completion fails validation. State partially applied.

**Detection:** Checkpoint validation fails.

**Impact:** Partial projection state. Must rollback and restart.

**Recovery:** Rollback to backup. Fix root cause. Restart replay.

---

### FM-REP-003: Event Store Unavailable During Replay

**Scenario:** Network partition or DB failure during replay. Event stream inaccessible.

**Detection:** Connection timeout. Replay stalls.

**Impact:** Replay cannot complete. Recovery blocked.

**Recovery:** Wait for connectivity. Resume from checkpoint. No data loss.

---

## Ownership and Governance

| Field | Value |
|-------|-------|
| **Owner** | Platform Team |
| **Approver** | Architecture Board |
| **Last Verified Date** | 2026-03-30 |
| **Verification Method** | Document review |
| **Production Criticality** | Tier 1 |
| **Rollback Impact** | Cannot roll back - replay procedures in use |
| **Verification Status** | Declared |

---

**Document Version:** 1.1  
**Owner:** Platform Team  
**Review Cycle:** Monthly
