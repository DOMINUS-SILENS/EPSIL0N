# Inventory Consistency Contract

## Purpose

Stock/Inventory is the **consistency proving ground** for EPSILON. This contract defines the mathematical guarantees required for inventory correctness.

**Architectural Law:** If you cannot prove stock convergence, you cannot claim event-sourced maturity.

---

## A. Canonical Stock Truth

### Definition

> **The ordered set of accepted stock-affecting events**

Stock balances are **derived**, not authoritative.

The only authoritative source is the complete, ordered event stream of stock movements.

### Event Types That Affect Stock

| Event Type | Description | Balance Impact |
|------------|-------------|----------------|
| `StockReceived` | Goods received into warehouse | `+quantity` |
| `StockIssued` | Goods issued from warehouse | `-quantity` |
| `StockTransferred` | Movement between warehouses | `-qty` from source, `+qty` to destination |
| `StockAdjusted` | Inventory correction/adjustment | `+/- quantity` with reason code |
| `StockReturned` | Customer return to stock | `+quantity` |
| `StockReversed` | Compensation for prior error | Opposite of original movement |

### Non-Stock-Affecting Events

These events must NOT alter stock balances:

- `StockReserved` (affects availability, not balance)
- `StockReservationReleased` (affects availability)
- `StockCounted` (audit event, no balance change unless followed by `StockAdjusted`)
- `StockMovementPlanned` (intent, not execution)

---

## B. Stock Invariants (Non-Negotiable)

These invariants must hold at all times. Each must be testable and tested.

### Invariant 1: Balance Equivalence

```
For all (article_id, depot_id):
  projected_balance = Σ(accepted_stock_events.quantity)
  stored_balance = stock_balances.quantity
  
  Assert: projected_balance == stored_balance
```

**Test:** Hourly reconciliation job compares projection to stored balance. Mismatch triggers CRITICAL alert.

### Invariant 2: Replay Determinism

```
Given: Same ordered set of events
Then:  Same final balance (always)
```

**Test:** Daily replay test: rebuild stock projection from event zero, verify match with current projection.

### Invariant 3: Duplicate Immunity

```
Given: Same event ingested twice (same idempotency key)
Then:  Balance unchanged on second ingestion
```

**Test:** Idempotency test suite runs on each deployment.

### Invariant 4: Out-of-Order Convergence

```
Given: Events arrive out of order
Then:  Final state converges to same balance as if ordered
```

**Test:** Chaos test: shuffle event order, verify convergence.

**Note:** This requires commutative operations or causal tracking. If operations don't commute, ordering must be enforced.

### Invariant 5: Compensating Event Preservation

```
Given: Original event + Compensating event
Then:  Net balance effect = 0
And:   Audit trail shows both events (no silent deletion)
```

**Test:** All compensating events validated against original event reference.

### Invariant 6: Projection Non-Authority

```
Given: Projection table `stock_balances`
Then:  No code may treat it as source of truth for writes
```

**Test:** Static analysis scan for `stock_balances` in UPDATE/INSERT statements outside `StockProjector`.

### Invariant 7: No Negative Stock (Configurable)

```
For all (article_id, depot_id):
  If stock_tracking_enabled:
    Assert: balance >= 0
```

**Configuration:** Some warehouses allow negative stock (backorders). This must be explicit per `depot.allows_negative_stock`.

---

## C. Allowed Stock Mutations

### Permitted Mutation Paths

1. **Receipt Flow**
   ```
   PurchaseOrderReceived → StockAggregate::receive() → StockReceived event → StockProjector → stock_balances
   ```

2. **Issue Flow**
   ```
   OrderValidated → ReservationCheck → StockAggregate::issue() → StockIssued event → StockProjector
   ```

3. **Transfer Flow**
   ```
   TransferRequest → StockAggregate::transfer() → StockTransferred event (atomic source+dest) → StockProjector
   ```

4. **Adjustment Flow**
   ```
   PhysicalCount → VarianceDetected → ApprovalWorkflow → StockAggregate::adjust() → StockAdjusted event → StockProjector
   ```

5. **Reversal Flow**
   ```
   ErrorDetected → Investigation → StockAggregate::reverse(originalEventId) → StockReversed event → StockProjector
   ```

### Forbidden Mutations

| Forbidden Action | Why | Detection |
|-----------------|-----|-----------|
| `UPDATE stock_balances SET quantity = X` | Violates event sourcing | Static analysis |
| `INSERT INTO article_mouvement` (bypassing aggregate) | Legacy dual-write | Code review |
| `DB::table('balance_stock')->increment()` | Direct mutation | Runtime logging |
| Admin panel "quick fix" | Silent corruption | Audit log scan |
| SQL repair script | Untraceable mutation | Change management block |

---

## D. Stock Convergence Testing

### Test Categories

#### T1: Unit Convergence Tests
- Each movement type: apply event, verify balance change
- Each compensating event: verify net-zero effect
- Edge cases: zero quantity, max quantity, fractional

#### T2: Integration Convergence Tests
- Event sequence replay: 1000 random events → verify final balance
- Concurrent event handling: verify ordering/locking
- Partition recovery: simulate network split → verify convergence

#### T3: Chaos Convergence Tests
- Out-of-order ingestion
- Duplicate event flood
- Random event loss and recovery
- Clock skew handling

#### T4: Production Drift Detection
- Hourly reconciliation: projected vs stored balance
- Alert threshold: 0.001 unit tolerance
- Auto-remediation: trigger projection rebuild on drift

### Convergence Proof Checklist

- [ ] Replay 10M events deterministically in < 5 minutes
- [ ] Rebuild all projections from event zero without data loss
- [ ] Survive 1000 duplicate events without balance change
- [ ] Handle out-of-order arrival within 5 seconds
- [ ] Detect balance drift within 60 seconds
- [ ] Recover from projector crash at arbitrary offset
- [ ] Prove no stock drift under out-of-order arrival (mathematical proof or exhaustive test)

---

## E. Conflict Resolution for Stock

### Conflict Scenarios

#### Scenario 1: Same Stock Issued Twice (Double-Spend)

**Detection:** Idempotency key `(order_id, article_id, depot_id)`

**Resolution:** Reject duplicate with 409 Conflict. Second issue blocked.

#### Scenario 2: Concurrent Issue and Receipt (Race)

**Prevention:** Optimistic locking on `stock_balances` row (or equivalent)

**Resolution:** One wins, other retries. Event order determines outcome.

#### Scenario 3: Offline Device Issues Same Stock

**Detection:** Sync conflict detection via vector clocks or timestamps

**Resolution:** 
1. Detect conflict in `sync_conflicts` queue
2. Apply domain policy: "earliest timestamp wins" OR "smaller quantity wins" OR "manual resolution"
3. Generate compensating event if needed
4. Never silent merge

#### Scenario 4: Adjustment vs. Movement Conflict

**Resolution:** Adjustment always wins (represents physical truth). Movement compensated.

### Conflict Resolution Policy Registry

| Conflict Type | Policy | Auto/Manual |
|--------------|--------|-------------|
| Duplicate issue | Reject duplicate | Auto |
| Negative stock (if prohibited) | Reject issue | Auto |
| Offline concurrent edit | Timestamp wins | Auto |
| Adjustment vs. movement | Adjustment wins | Auto |
| Complex multi-device conflict | Queue for manual review | Manual |

---

## F. Stock Projection Trust Level

**Classification:** P2 — Operationally Critical

Requirements for P2 projections:

- [x] Replay validation exists
- [x] Drift monitoring active
- [x] Rebuild procedure documented
- [x] Reconciliation jobs running

### Drift Detection

```sql
-- Hourly reconciliation query
SELECT 
  sb.article_id,
  sb.depot_id,
  sb.quantity as stored_balance,
  SUM(sm.quantity * sm.direction) as projected_balance,
  ABS(sb.quantity - SUM(sm.quantity * sm.direction)) as drift
FROM stock_balances sb
LEFT JOIN stock_moves sm 
  ON sb.article_id = sm.article_id 
  AND sb.depot_id = sm.depot_id
GROUP BY sb.article_id, sb.depot_id
HAVING drift > 0.001;
```

**Alert:** Any drift > 0.001 units triggers CRITICAL alert to #stock-ops channel.

### Rebuild Procedure

1. Pause `StockProjector` consumption
2. Truncate `stock_balances` (or create shadow table)
3. Replay all `stock_moves` events in order
4. Verify drift = 0
5. Switch read traffic to rebuilt projection
6. Resume `StockProjector`

**RTO:** 10 minutes for full rebuild (1M movements)  
**RPO:** Zero (events are source of truth)

---

## G. Stock-Specific API Rules

### Endpoint: `POST /erp/movements`

**Request Format:**
```json
{
  "idempotency_key": "device-123:batch-456:seq-789",
  "movements": [
    {
      "type": "issue",
      "article_id": "ABC-123",
      "depot_id": "WH-001",
      "quantity": 10.5,
      "unit_id": "EA",
      "reference": "order-789-line-1",
      "reason_code": "customer_order"
    }
  ]
}
```

**Response Guarantees:**
- 200 OK: Events committed, stock will converge
- 409 Conflict: Duplicate idempotency key, no change
- 422 Unprocessable: Invalid (negative stock if prohibited, invalid article, etc.)

**No Partial Success:** All movements in batch succeed or none.

### Endpoint: `GET /erp/stock/history/{article_id}`

**Returns:** Ordered event list (source of truth), not balance snapshot.

---

## H. Stock Audit Requirements

### Audit Log Entries

Every stock-affecting event generates:

- Event ID (UUID)
- Event type
- Aggregate ID (article_id + depot_id)
- Previous balance
- Change amount
- New balance
- User/device attribution
- Timestamp (wall clock + logical)
- Idempotency key
- Correlation ID (for distributed tracing)

### Compliance Queries

```sql
-- Reconstruct any balance at any point in time
SELECT SUM(quantity * direction) 
FROM stock_moves 
WHERE article_id = ? 
  AND depot_id = ?
  AND created_at <= ?;

-- Full audit trail for balance
SELECT * FROM stock_moves 
WHERE article_id = ? AND depot_id = ?
ORDER BY created_at, sequence;
```

---

## I. Enforcement

### Code Review Checklist for Stock Changes

- [ ] No direct `stock_balances` mutations
- [ ] All stock changes go through `StockAggregate`
- [ ] Event emitted for every mutation
- [ ] Idempotency key present
- [ ] Reason code provided
- [ ] Compensating event logic for reversals

### Runtime Guards

```php
// Runtime check (dev/staging)
if (Str::contains($query, 'stock_balances') && 
    Str::contains($query, ['UPDATE', 'INSERT', 'DELETE'])) {
    Log::critical('Direct stock balance mutation detected', [
        'trace' => debug_backtrace(),
        'user' => auth()->id(),
    ]);
    throw new \Exception('Direct stock mutation forbidden');
}
```

### Metrics

- `stock_projection_drift`: Gauge, should be 0
- `stock_rebuild_duration`: Histogram, target < 10min
- `stock_convergence_test_failures`: Counter, should be 0
- `stock_conflict_queue_depth`: Gauge, alert if > 10

---

## J. Stock Migration Status

### Current State: In Transition

| Component | Legacy | Canonical | Status |
|-----------|--------|-------------|--------|
| Movement events | `article_mouvement` | `stock_moves` + event_store | MIGRATING |
| Balance projection | `balance_stock` | `stock_balances` | MIGRATING |
| Write path | Direct table | `StockAggregate` | MIGRATING |
| Read path | Legacy queries | Projection views | PARTIAL |

### Kill Criteria for Legacy Tables

- [ ] Zero writes to `article_mouvement` for 30 days
- [ ] Zero writes to `balance_stock` for 30 days
- [ ] All queries migrated to `stock_moves` / `stock_balances`
- [ ] Reconciliation drift = 0 for 30 days
- [ ] Rollback plan archived

**Target Date:** ___________

---

## K. Failure Modes

This section documents how the inventory system fails. Every serious architecture must include explicit failure modes.

### K.1: Duplicate Device Sync Batch After Network Timeout

**Scenario:** Device submits stock movement batch. Network times out before acknowledgment received. Device retries, creating duplicate batch.

**Detection:** Idempotency key collision `(device_id, batch_id)`.

**Impact:** Without idempotency: duplicate stock issue → oversell → customer shortage.

**Mitigation:** Idempotency service rejects duplicate. Device receives "already processed" response.

**Recovery:** None required (invariant preserved).

**Test:** T-SYNC-001 (duplicate batch test).

---

### K.2: Out-of-Order Stock Transfer Events Across Depots

**Scenario:** Transfer from Depot A to Depot B. Transfer-out event arrives before transfer-in due to network partition.

**Detection:** Causal tracking (vector clocks) or sequence gap detection.

**Impact:** Temporary negative stock at Depot B until transfer-in arrives. If negative stock prohibited: transfer-out rejected pending transfer-in.

**Mitigation:** Causal ordering enforcement. If out-of-order detected: queue dependent event until prerequisite arrives.

**Recovery:** Events apply automatically once ordering restored. Manual intervention only if timeout exceeded (30 min).

**Test:** T-INV-004 (chaos ordering test).

---

### K.3: Projection Crash After Side Effect But Before Checkpoint Commit

**Scenario:** StockProjector processes event, issues DB update, crashes before committing checkpoint position.

**Detection:** Next startup: checkpoint position < last processed event.

**Impact:** Event reprocessed (idempotent if projector implemented correctly). No drift if idempotence correct.

**Mitigation:** Idempotent projector logic. Duplicate event application must produce same result.

**Recovery:** Automatic replay from checkpoint. Verify no drift post-recovery.

**Test:** T-PROJ-001 (crash recovery test).

---

### K.4: Stale Dual-Write Bridge Continuing After Cutover

**Scenario:** Legacy `balance_stock` table continues receiving writes after cutover to `stock_balances`.

**Detection:** Reconciliation shows drift between `balance_stock` and `stock_balances`. Alert fires.

**Impact:** State divergence. Reads from wrong table show incorrect stock.

**Mitigation:** Runtime guards blocking direct `balance_stock` writes. Monitoring alerts on any write.

**Recovery:**
1. Identify source of stale writes
2. Fix code path
3. Reconcile tables
4. If divergence significant: rebuild `stock_balances` from events

**Prevention:** Kill criteria enforcement. Zero-tolerance for dual-write after cutover.

---

### K.5: Operator Attempts Manual Stock Correction Outside Command Path

**Scenario:** Support team runs SQL update to "fix" stock discrepancy: `UPDATE balance_stock SET quantity = 100 WHERE id = 123`.

**Detection:** 
- Runtime guard logs CRITICAL alert
- Static analysis flags in next build
- Drift detection shows unexplained change

**Impact:** Event truth violated. Future replays will not reconstruct manual fix. Permanent divergence.

**Mitigation:**
- Runtime guards block and alert
- Production DB permissions (read-only for support)
- All "corrections" must use `StockAggregate::adjust()` with full audit

**Recovery:**
1. Immediate: Revert manual change (if caught quickly)
2. Apply proper `StockAdjusted` event with reason code
3. Investigate why discrepancy existed (root cause)
4. Team retraining

**Escalation:** CTO notification for any manual production stock correction.

---

### K.6: Competing Offline Stock Issues on Same Item

**Scenario:** Two field reps offline. Both issue stock for same article/depot. Both think sufficient stock available.

**Detection:** When sync arrives: second issue causes negative stock (if prohibited) or reservation conflict.

**Impact:** Potential oversell if both issues accepted.

**Mitigation:**
- Reservation system holds stock for pending orders
- Sync conflict detection flags concurrent issues
- Domain policy: first sync wins, second queued for resolution

**Resolution:**
1. If sufficient stock: accept both
2. If insufficient: reject second, notify rep
3. Offer alternatives: partial issue, backorder, different depot

**Test:** T-SYNC-005 (concurrent offline issue test).

---

### K.7: Event Store Corruption (Single Event)

**Scenario:** Disk corruption or bug causes single event in `event_store` to have incorrect payload.

**Detection:**
- Merkle root verification fails
- Checksum mismatch on read
- Projection shows impossible state

**Impact:** Replay produces incorrect state. All projections affected.

**Mitigation:**
- Merkle tree integrity checks
- Event signing (cryptographic)
- Backup event store (delayed replica)

**Recovery:**
1. Identify corrupted event(s)
2. Determine correct payload from application logs or backup
3. Issue compensating events (never edit event_store)
4. If corruption extensive: restore from replica, replay

**Classification:** Catastrophic. Requires incident commander. 24-hour recovery SLA.

---

### K.8: Stock Movement Event Loss (Not Persisted)

**Scenario:** Application crash between event creation and persistence. Event acknowledged to client but not committed.

**Detection:**
- Client has acknowledgment but event not in store
- Gap in sequence detected
- Client retry with same idempotency key

**Impact:** Lost movement = stock drift.

**Mitigation:**
- Idempotency service: duplicate key detection tells client "already processed"
- But if event truly lost: idempotency service has no record
- Client retry creates new event (acceptable)

**Recovery:** None (client retry succeeds on second attempt).

**Prevention:** Transactional outbox pattern. Event persistence in same transaction as acknowledgment.

---

### K.9: Misconfigured Depot Allows Negative Stock

**Scenario:** Depot A should prohibit negative stock (`allows_negative_stock = false`). Configuration error sets to `true`. Multiple oversells occur.

**Detection:** 
- Inventory audit shows negative balances
- Customer complaints about unfulfilled orders
- Financial discrepancy

**Impact:** Fulfillment failure, customer impact, financial loss.

**Mitigation:**
- Depot configuration change requires approval workflow
- Alert on depot configuration modification
- Daily audit: negative stock check

**Recovery:**
1. Fix configuration
2. Identify affected orders
3. Emergency procurement or customer negotiation
4. Financial reconciliation

---

### K.10: Projection Rebuild During Active Trading Hours

**Scenario:** R3 rebuild of `stock_balances` initiated during business hours without maintenance window.

**Detection:** Lag spike, performance degradation, potential for stale reads.

**Impact:**
- Orders issued based on stale stock = oversell
- Customer-facing availability incorrect
- Trading decisions based on bad data

**Mitigation:**
- R3 rebuilds require maintenance window
- Automated check: "Are trading hours?" blocks rebuild
- Approval workflow for emergency rebuilds

**Recovery:**
1. Immediate: Announce data quality issue
2. Fast-track rebuild completion
3. Identify affected transactions
4. Compensating actions as needed

**Policy:** Emergency R3 rebuild during trading hours requires CTO approval.

---

## L. Ownership and Governance

| Field | Value |
|-------|-------|
| **Owner** | Inventory Team |
| **Approver** | Architecture Board |
| **Last Verified Date** | 2026-03-30 |
| **Verification Method** | Document review, partial test review |
| **Production Criticality** | Tier 1 |
| **Rollback Impact** | Stock operations halt, customer impact |
| **Verification Status** | Declared |

---

**Contract Version:** 1.1  
**Enforcement:** Architecture Board + Automated Tests  
**Review Cycle:** Weekly during migration  
**Next Review:** 2026-04-06
