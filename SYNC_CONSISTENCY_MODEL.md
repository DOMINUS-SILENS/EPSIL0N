# Sync Consistency Model

## Purpose

Offline sync must be treated as a **distributed systems boundary**, not an API feature. This document governs the distributed consistency model for mobile/field device synchronization.

**Core Principle:** The client must never invent domain truth.

---

## A. Unit of Client Intent

### Preferred: Commands / Domain Intents

The device captures **intent**, not final state.

**Example — Good:**
```json
{
  "intent_type": "place_order",
  "idempotency_key": "device-123:batch-456:intent-789",
  "payload": {
    "customer_id": "CUST-001",
    "lines": [
      {
        "article_id": "PROD-123",
        "quantity": 5,
        "unit_id": "EA"
      }
    ],
    "requested_delivery_date": "2026-04-15"
  },
  "timestamp": "2026-03-30T10:30:00Z",
  "vector_clock": {
    "device-123": 145
  }
}
```

### Forbidden: Raw Final State Patches

**Example — Bad:**
```json
{
  "action": "update",
  "table": "orders",
  "record_id": "ORDER-789",
  "fields": {
    "status": "validated",
    "total": 150.00
  }
}
```

**Why:** Patches destroy causality. The server cannot reconstruct intent or enforce invariants.

---

## B. Idempotency Unit

### What Exactly Is Deduplicated?

**Answer:** `(device_id, batch_sequence, intent_sequence)`

**Idempotency Key Format:**
```
{device_id}:{batch_id}:{intent_id}:{operation_type}
```

**Example:** `dev-abc123:batch-45:intent-12:place_order`

### Deduplication Scope

| Level | Key Components | Deduplication Window | Storage |
|-------|----------------|---------------------|---------|
| Batch | `device_id`, `batch_id` | 7 days | `api_idempotency_keys` |
| Intent | `device_id`, `batch_id`, `intent_id` | 7 days | `api_idempotency_keys` |
| Operation | Full key including `operation_type` | 24 hours | In-memory cache |

### Deduplication Logic

```php
class IdempotencyService {
    public function isDuplicate(string $idempotencyKey): bool
    {
        // Check redis (fast path)
        if (Redis::exists("idempotency:$idempotencyKey")) {
            return true;
        }
        
        // Check database (source of truth)
        $exists = DB::table('api_idempotency_keys')
            ->where('key', $idempotencyKey)
            ->where('created_at', '>', now()->subDays(7))
            ->exists();
            
        if ($exists) {
            // Cache for fast path
            Redis::setex("idempotency:$idempotencyKey", 3600, '1');
            return true;
        }
        
        return false;
    }
    
    public function recordIntent(string $idempotencyKey, string $result): void
    {
        DB::table('api_idempotency_keys')->insert([
            'key' => $idempotencyKey,
            'result' => $result,
            'created_at' => now(),
        ]);
        
        Redis::setex("idempotency:$idempotencyKey", 3600, $result);
    }
}
```

---

## C. Ordering Semantics

### What Must Remain Ordered?

**Sequential Processing Required:**

1. **Same Aggregate Events**
   - Same `order_id`: Order creation → Line addition → Validation
   - Same `article_id` + `depot_id`: Stock movements
   - Same `customer_id`: Profile updates

2. **Same-Device Dependent Commands**
   - Device creates order → Device adds payment
   - Device checks in → Device completes delivery

3. **Causal Dependencies**
   ```
   StockReservation → StockIssue (reservation must exist first)
   OrderValidation → DeliveryAssignment (order must be validated)
   ```

### What May Arrive Unordered?

**Parallel Processing Acceptable:**

1. **Unrelated Customer Updates**
   - Customer A update || Customer B update (no dependency)

2. **Dashboard Telemetry**
   - App usage stats, performance metrics

3. **Non-Causal Metadata**
   - GPS location history, device status

4. **Independent Orders**
   - Order 1 processing || Order 2 processing (different customers)

### Ordering Enforcement

**Strategy:** Per-aggregate ordering, cross-aggregate parallelism.

```php
class SyncBatchProcessor {
    public function process(SyncBatch $batch): void
    {
        // Group by aggregate (for ordering)
        $byAggregate = $batch->intents->groupBy(function ($intent) {
            return $intent->getAggregateId(); // e.g., "order:123"
        });
        
        // Process aggregates in parallel
        $byAggregate->each(function ($intents, $aggregateId) {
            // Process intents for same aggregate sequentially
            foreach ($intents->sortBy('sequence') as $intent) {
                $this->processIntent($intent);
            }
        });
    }
}
```

---

## D. Conflict Semantics

### Conflict Handling Must Be Explicit Per Domain

**Rule:** Generic `updated_at` winner logic is corruption with a timestamp.

### Domain-Specific Conflict Policies

#### 1. Order Conflicts

**Scenario:** Same order line edited on two devices offline.

**Policy:** Domain merge with business rules.

```
Device A: Line 1 quantity = 5 (timestamp 10:00)
Device B: Line 1 quantity = 8 (timestamp 10:05)

Resolution:
1. Both are valid intents
2. System applies both: quantity = 8 (last edit wins for independent field)
3. If quantity exceeded stock: split to backorder
4. Generate merged event with both references
```

**Conflict Record:**
```json
{
  "conflict_type": "concurrent_edit",
  "domain": "order",
  "aggregate_id": "order:123",
  "intents": ["intent-A", "intent-B"],
  "resolution": "merge_with_backorder",
  "result_event": "OrderLineMerged"
}
```

#### 2. Stock Movement Conflicts

**Scenario:** Same stock issued twice (double-spend attempt).

**Policy:** Reject duplicate.

```
Device A: Issue 10 units (idempotency key: dev-A:batch-1:issue-1)
Device B: Issue 10 units (idempotency key: dev-B:batch-2:issue-1)

Resolution:
1. Check available stock
2. If sufficient: Accept both (different orders)
3. If insufficient: Reject second with "insufficient_stock"
4. No silent reduction
```

**Key:** Idempotency prevents true duplicate. Different orders for same stock = business conflict resolved by availability.

#### 3. Customer Note Conflicts

**Scenario:** Same customer note added twice.

**Policy:** Deduplicate or append.

```
Resolution: 
- If exact text match: Deduplicate (idempotency)
- If different text: Append both notes with timestamps
```

#### 4. Stock Issue + Offline Receipt Conflict

**Scenario:** Device issues stock that was already issued by another device.

**Policy:** Reject with compensation options.

```
Resolution:
1. Detect conflict: requested issue > available
2. Options:
   a) Reject issue (default)
   b) Partial issue (remaining quantity)
   c) Backorder remainder
3. Client must acknowledge resolution
```

### Conflict Resolution Registry

| Conflict Type | Domain | Policy | Auto/Manual |
|--------------|--------|--------|-------------|
| Concurrent order edit | Order | Merge with rules | Auto |
| Duplicate idempotency | All | Reject duplicate | Auto |
| Stock double-spend | Inventory | Reject if insufficient | Auto |
| Same note added | CRM | Deduplicate/append | Auto |
| Stock oversell | Inventory | Reject/partial/backorder | Auto with choice |
| Price mismatch | Order | Use server price | Auto |
| Customer merge collision | CRM | Queue for manual | Manual |
| Complex multi-entity | Various | Queue for review | Manual |

### Conflict Queue

**Unresolved conflicts go to `sync_conflicts` table:**

```sql
CREATE TABLE sync_conflicts (
  id BIGINT PRIMARY KEY,
  device_id VARCHAR(50),
  batch_id VARCHAR(50),
  intent_id VARCHAR(50),
  conflict_type VARCHAR(50),
  aggregate_type VARCHAR(50),
  aggregate_id VARCHAR(50),
  conflicting_intents JSON,
  detected_at TIMESTAMP,
  resolved_at TIMESTAMP NULL,
  resolution VARCHAR(50) NULL,
  resolution_event_id BIGINT NULL,
  requires_manual BOOLEAN DEFAULT FALSE,
  assigned_to VARCHAR(50) NULL
);
```

**Monitoring:**
- Alert if `requires_manual = true` count > 10
- SLA: Manual resolution within 4 hours

---

## E. Sync API Contract

### Endpoint: `POST /sync/ingest`

**Request:**
```json
{
  "device_id": "dev-abc123",
  "batch_id": "batch-456",
  "batch_sequence": 45,
  "previous_batch_id": "batch-444",
  "vector_clock": {
    "dev-abc123": 145,
    "server": 2300
  },
  "intents": [
    {
      "intent_id": "intent-789",
      "intent_sequence": 1,
      "idempotency_key": "dev-abc123:batch-456:intent-789:place_order",
      "intent_type": "place_order",
      "timestamp": "2026-03-30T10:30:00Z",
      "payload": { ... },
      "dependencies": [] // intent_ids this depends on
    }
  ],
  "checksum": "sha256:abc123..."
}
```

**Response:**
```json
{
  "batch_accepted": true,
  "batch_id": "batch-456",
  "processed_intents": 10,
  "accepted_intents": 9,
  "rejected_intents": 1,
  "rejections": [
    {
      "intent_id": "intent-790",
      "reason": "insufficient_stock",
      "can_retry": false,
      "alternative": {
        "type": "partial_issue",
        "available_quantity": 3
      }
    }
  ],
  "conflicts_queued": 0,
  "new_vector_clock": {
    "dev-abc123": 145,
    "server": 2310
  },
  "next_expected_batch": 46
}
```

### Endpoint: `GET /sync/delta`

**Query:**
```
GET /sync/delta?since=2300&device_id=dev-abc123&limit=100
```

**Response:**
```json
{
  "events": [
    {
      "event_id": "evt-5001",
      "sequence": 2301,
      "event_type": "OrderValidated",
      "aggregate_type": "order",
      "aggregate_id": "order:123",
      "payload": { ... },
      "server_timestamp": "2026-03-30T10:35:00Z"
    }
  ],
  "has_more": true,
  "next_sequence": 2350,
  "server_time": "2026-03-30T10:40:00Z"
}
```

### Endpoint: `GET /sync/status`

**Response:**
```json
{
  "device_id": "dev-abc123",
  "last_sync_at": "2026-03-30T10:30:00Z",
  "server_sequence": 2310,
  "device_sequence": 145,
  "pending_uploads": 0,
  "pending_downloads": 42,
  "conflicts_pending": 0,
  "sync_health": "healthy",
  "lag_seconds": 35
}
```

---

## F. Client Responsibilities

### The Client Must Never:

1. **Invent domain truth**
   - Never calculate authoritative balances
   - Never enforce business invariants independently
   - Never resolve conflicts by heuristic guesswork

2. **Mutate canonical state assumptions locally**
   - Local state is always provisional
   - Server confirmation required for truth

3. **Skip sync for "important" operations**
   - All operations queue for sync
   - No direct API calls that bypass sync architecture

### The Client Must:

1. **Stage intent**
   - Capture user intent clearly
   - Queue locally with idempotency key

2. **Display optimistic state**
   - Show expected outcome immediately
   - Indicate "pending sync" status

3. **Reconcile later**
   - Apply server resolution on download
   - Handle conflicts per server direction
   - Update UI with confirmed state

---

## G. Server Responsibilities

### The Server Must:

1. **Accept intents, not patches**
   - Validate intent semantics
   - Enforce invariants
   - Emit domain events

2. **Provide clear conflict resolution**
   - Specific rejection reasons
   - Alternative options when applicable
   - Consistent ordering guarantees

3. **Maintain causality tracking**
   - Vector clocks or equivalent
   - Dependency resolution
   - Causal consistency across devices

4. **Never lose client intent**
   - Accepted batches durable within 1 second
   - Acknowledged intents eventually processed
   - Retry transparency for client

---

## H. Sync Health Metrics

### Key Metrics

| Metric | Type | Alert | Target |
|--------|------|-------|--------|
| `sync_lag_seconds` | Gauge | > 300 | < 60 |
| `sync_conflict_rate` | Rate | > 0.05 | < 0.01 |
| `sync_rejection_rate` | Rate | > 0.10 | < 0.05 |
| `sync_batch_size_avg` | Gauge | > 100 | < 50 |
| `sync_intent_processing_seconds` | Histogram | > 5 | < 1 |
| `unresolved_conflicts_count` | Gauge | > 10 | 0 |
| `device_sync_freshness` | Gauge | > 600 | < 120 |

---

## I. Failure Modes

### Scenario: Device Offline > 24 Hours

**Behavior:**
1. Queue grows locally (Dexie.js)
2. On reconnect: Resume sync with `/sync/resume`
3. Server provides delta since last successful batch
4. Large upload may be batched
5. Conflicts may accumulate

**Recovery:**
- Incremental sync prioritizes recent intents
- Background processing for historical data

### Scenario: Server Rejection

**Behavior:**
1. Intent rejected with specific reason
2. Client must handle (retry, modify, or abandon)
3. User notified of rejection
4. Optimistic UI rolled back

**Example:**
```javascript
// Client-side rejection handler
sync.on('intent_rejected', (intent, reason) => {
  if (reason.code === 'insufficient_stock') {
    showAlternativeOptions(reason.alternative);
  } else if (reason.can_retry) {
    queueForRetry(intent);
  } else {
    notifyUserAndRemove(intent);
  }
});
```

### Scenario: Conflict Detected

**Behavior:**
1. Server queues conflict
2. If auto-resolvable: Resolution applied, event emitted
3. If manual required: Conflict appears in admin queue
4. Client receives resolution on next sync

---

**Document Version:** 1.0  
**Owner:** Architecture Board  
**Review Cycle:** Monthly

---

## J. Failure Modes

### FM-SYNC-001: Intent Loss During Network Partition

**Scenario:** Device submits intent, network drops during transmission, server receives partial data.

**Detection:** Idempotency key missing from acknowledgment. Client retry with same key.

**Impact:** Duplicate risk if not handled correctly. Idempotency service must handle gracefully.

**Recovery:** Client retry with exponential backoff.

---

### FM-SYNC-002: Vector Clock Divergence

**Scenario:** Device and server clocks drift significantly. Vector clock ordering becomes ambiguous.

**Detection:** Clock skew > 5 minutes detected. Causal ordering compromised.

**Impact:** Events may be processed in incorrect order. State inconsistency.

**Recovery:** Server authoritative timestamp override. Device clock synchronization required.

---

### FM-SYNC-003: Conflict Queue Overflow

**Scenario:** Rapid offline edits from many devices create thousands of conflicts.

**Detection:** `unresolved_conflicts_count` > 100. Alert fires.

**Impact:** Manual resolution backlog. SLA breach. Customer impact.

**Recovery:** Emergency auto-resolution rules (risky). Additional support staff. Batch resolution tools.

---

## K. Ownership and Governance

| Field | Value |
|-------|-------|
| **Owner** | Platform Team |
| **Approver** | Architecture Board |
| **Last Verified Date** | 2026-03-30 |
| **Verification Method** | Document review |
| **Production Criticality** | Tier 1 |
| **Rollback Impact** | Sync operations fail, field operations halt |
| **Verification Status** | Declared |
