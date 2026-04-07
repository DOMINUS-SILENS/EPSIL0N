# EPSILON Kernel Deployment Specification

This document defines the operational requirements and deployment constraints for the EPSILON ERP Kernel. The kernel is an event-sourced runtime; any deviation from these specifications will result in a loss of deterministic state and traceability.

## 1. Runtime Architecture

The kernel implements a **Symmetric Feed Architecture (SFA)**. The flow of truth is strictly unidirectional:
`Intent (Command)` $\rightarrow$ `Decision (Aggregate)` $\rightarrow$ `Truth (Event Store)` $\rightarrow$ `Realization (Projection)` $\rightarrow$ `Sync (Mobile Feed)`

### Critical Components
- **PostgreSQL Event Store**: The immutable append-only log.
- **RoadRunner**: High-performance PHP worker pool for orchestration.
- **PostgreSQL Read Models**: Materialized views of the event stream.
- **Redis**: High-speed cache for device synchronization offsets.

---

## 2. Infrastructure Provisioning

### A. PostgreSQL Configuration (Write-Optimized)
The Event Store is write-heavy. The following `postgresql.conf` overrides are **mandatory** to prevent WAL bottlenecks during event bursts:

```ini
# Write-Ahead Log (WAL) Optimization
wal_buffers = 16MB
max_wal_size = 2GB
min_wal_size = 512MB
checkpoint_completion_target = 0.9

# Storage & I/O
random_page_cost = 1.1  # Assumes SSD/NVMe storage
effective_io_concurrency = 200

# Memory Allocation
shared_buffers = 4GB     # 25% of total system RAM
work_mem = 16MB
```

### B. Redis Cluster Settings
Used exclusively for `device_offsets` and `recent_delta` caching.
- **Max Memory**: 16GB
- **Eviction Policy**: `allkeys-lru`
- **Persistence**: `appendonly yes` (fsync every second)

### C. RoadRunner Worker Pool
```yaml
jobs:
  pool:
    num_workers: 32       # Baseline; scale to 128 during burst
    max_worker_memory: 128 # MB
    exec_ttl: 60s
```

---

## 3. Database Initialization Sequence

The database must be initialized in the following strict order to maintain relational integrity:

1. **`001_create_event_store.sql`**: Basic event store structure.
2. **`002_runtime_spine.sql`**: Event streams table and optimistic concurrency constraints.
3. **`003_projections.sql`**: Read models and Mobile Sync Feed tables.

---

## 4. Operational Guardrails (The Laws)

### Law I: Immutability of the Log
**Prohibited**: `UPDATE` or `DELETE` operations on the `domain_events` table.
**Reason**: Any modification to the event log destroys the audit trail and breaks the ability to reconstruct state.
**Enforcement**: Database-level permissions should restrict the application user to `INSERT` and `SELECT` only on this table.

### Law II: Symmetric Sync
**Constraint**: Devices must acknowledge sync IDs within 30 days.
**Action**: Data older than 30 days in `mobile_sync_feed` is archived to S3. Devices that fall behind this window must trigger a full state replay from the Event Store.

### Law III: Concurrency Recovery
**Behavior**: The kernel uses optimistic concurrency. When a `ConcurrencyConflictException` occurs:
1. The application must catch the exception.
2. Reload the aggregate to the latest version.
3. Re-apply the logic in `decide()`.
4. Attempt the `append()` again.

---

## 5. Scaling Matrix

| Component | Baseline (10k Tenants) | Peak | Burst |
|----------|-----------------------|------|-------|
| **Total vCPU** | 20 Cores | 40 Cores | 76 Cores |
| **Total RAM** | 40 GB | 76 GB | 152 GB |
| **Disk IOPS** | 1k Read / 500 Write | 10k Read / 5k Write | 30k Read / 15k Write |
| **Storage** | 650 GB | 750 GB | 900 GB |
| **Est. Cost** | ~$3,300 / mo | ~$4,100 / mo | ~$5,700 / mo |

---

## 6. Post-Deployment Verification

After deployment, run the following smoke test to verify the runtime spine:

```bash
cd packages/kernel && ./vendor/bin/phpunit tests/Integration/CustomerOnboardingEndToEndTest.php
```

**Success Criteria**:
- [ ] All 11 scenarios in `testFullCustomerLifecycle` pass.
- [ ] `ConcurrencyConflictException` is successfully triggered and handled.
- [ ] `TenantId` isolation prevents cross-tenant event loading.
- [ ] Mobile sync deltas are fetched and acknowledged.
