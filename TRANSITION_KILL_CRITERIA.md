# Transition Kill Criteria

## Purpose

This is what most migration programs fail to define: explicit conditions under which a transitional path is deleted. Otherwise "temporary" architecture becomes permanent technical debt.

**Rule:** Every bridge / dual-write path / compatibility layer must have an owner, purpose, risk, entry date, kill condition, and deletion date target.

**If no kill condition exists, that path is not transitional. It is permanent technical debt.**

---

## Transition Component Register

### Component: Legacy Article Table (`article`)

| Attribute | Value |
|-----------|-------|
| **Type** | Legacy Transitional Table |
| **Purpose** | Support old product screens during canonical migration |
| **Owner** | Catalog Team |
| **Risk** | Dual-write drift, schema inconsistency |
| **Entry Date** | 2025-03-21 |
| **Target Deletion** | 2026-06-30 |

**Kill Condition:**
- [ ] All product reads moved to `articles` table
- [ ] Zero reconciliation drift between `article` and `articles` for 30 days
- [ ] No legacy writes detected (via audit log)
- [ ] All imports migrated to canonical path
- [ ] Rollback plan archived

**Current Status:** ⏳ IN PROGRESS

**Blockers:**
- Old reporting module still queries `article` directly
- 3rd party integration uses legacy schema

---

### Component: Legacy Stock Movement Table (`article_mouvement`)

| Attribute | Value |
|-----------|-------|
| **Type** | Legacy Transitional Table |
| **Purpose** | Support old inventory reports during movement migration |
| **Owner** | Inventory Team |
| **Risk** | Stock drift, dual-authority risk |
| **Entry Date** | 2025-03-21 |
| **Target Deletion** | 2026-05-31 |

**Kill Condition:**
- [ ] All stock movements write through `StockAggregate` only
- [ ] `stock_moves` table backfilled with historical data (2025-01-01 onward)
- [ ] Zero writes to `article_mouvement` for 30 days
- [ ] All reports migrated to `stock_moves`
- [ ] Inventory Consistency Contract invariants proven
- [ ] Rollback plan archived

**Current Status:** ⏳ IN PROGRESS

**Blockers:**
- Legacy warehouse management system writes directly
- Historical movement archive query performance

---

### Component: Legacy Balance Table (`balance_stock`)

| Attribute | Value |
|-----------|-------|
| **Type** | Legacy Transitional Projection |
| **Purpose** | Dual-write compatibility during projection migration |
| **Owner** | Inventory Team |
| **Risk** | Projection authority confusion |
| **Entry Date** | 2025-03-21 |
| **Target Deletion** | 2026-05-15 |

**Kill Condition:**
- [ ] `balance_stock` becomes read-only
- [ ] All writes go to `stock_balances` only
- [ ] Drift detection: `balance_stock` vs `stock_balances` = 0 for 30 days
- [ ] All queries migrated to `stock_balances`
- [ ] `balance_stock` deprecated in Schema Authority Register

**Current Status:** ⏳ IN PROGRESS

**Blockers:**
- Mobile app v2.1 still reads `balance_stock`
- 4 custom admin scripts update directly

---

### Component: Dual-Write Article Price Paths

| Attribute | Value |
|-----------|-------|
| **Type** | Dual-Write Path |
| **Purpose** | Maintain both `article_groupe_prix` and event-sourced prices |
| **Owner** | Pricing Team |
| **Risk** | Price drift, promotion calculation errors |
| **Entry Date** | 2025-04-01 |
| **Target Deletion** | 2026-04-30 |

**Kill Condition:**
- [ ] All price updates go through `ArticleAggregate` only
- [ ] `article_groupe_prix` maintained by projector only
- [ ] Zero direct writes for 30 days
- [ ] Price reconciliation drift = 0
- [ ] Promotion engine reads from event-sourced prices

**Current Status:** ⏳ IN PROGRESS

---

### Component: Legacy Order CRUD Controller

| Attribute | Value |
|-----------|-------|
| **Type** | Legacy Code Path |
| **Purpose** | Support old order entry UI |
| **Owner** | Sales Team |
| **Risk** | Bypasses event sourcing, breaks CQRS |
| **Entry Date** | 2025-03-01 |
| **Target Deletion** | 2026-04-15 |

**Kill Condition:**
- [ ] New order UI deployed to 100% users
- [ ] Zero API calls to `/legacy/orders` endpoint for 30 days
- [ ] All order modifications through `OrderAggregate`
- [ ] Admin panel migrated to new order management

**Current Status:** ⏳ IN PROGRESS

**Blockers:**
- 2 enterprise customers use legacy API
- Internal support team uses old admin panel

---

### Component: Direct Eloquent Stock Updates

| Attribute | Value |
|-----------|-------|
| **Type** | Anti-Pattern Path |
| **Purpose** | "Emergency" stock corrections |
| **Owner** | Platform Team |
| **Risk** | Silent corruption, audit failure |
| **Entry Date** | 2025-01-01 (inherited) |
| **Target Deletion** | 2026-03-31 |

**Kill Condition:**
- [ ] All stock mutations go through `StockAggregate`
- [ ] Runtime guard blocks direct updates (logs + alerts)
- [ ] Zero emergency scripts modifying stock for 60 days
- [ ] Reconciliation repair process established
- [ ] Team training completed

**Current Status:** ⏳ IN PROGRESS

**Blockers:**
- 3 emergency scripts still in use
- Support team not trained on reconciliation process

---

### Component: Legacy Sync Protocol (v1)

| Attribute | Value |
|-----------|-------|
| **Type** | Legacy API |
| **Purpose** | Support old mobile app versions |
| **Owner** | Mobile Team |
| **Risk** | Security, inconsistency |
| **Entry Date** | 2024-06-01 |
| **Target Deletion** | 2026-06-30 |

**Kill Condition:**
- [ ] Mobile app v3+ adoption > 95%
- [ ] Zero v1 protocol requests for 30 days
- [ ] All devices upgraded or decommissioned
- [ ] v1 endpoint deprecated with warning

**Current Status:** ⏳ IN PROGRESS

**Blockers:**
- 47 devices still on v1 (remote locations)

---

### Component: MySQL Direct Replication (Read Replicas)

| Attribute | Value |
|-----------|-------|
| **Type** | Legacy Infrastructure |
| **Purpose** | Reporting read scaling |
| **Owner** | Platform Team |
| **Risk** | Replication lag, stale data |
| **Entry Date** | 2024-01-01 |
| **Target Deletion** | 2026-07-31 |

**Kill Condition:**
- [ ] All reports use projection-based queries
- [ ] Event-driven analytics pipeline operational
- [ ] Read replicas have zero query traffic for 30 days
- [ ] Cost savings validated

**Current Status:** ⏳ IN PROGRESS

**Blockers:**
- 12 legacy reports not yet migrated
- BI team needs training

---

## Kill Criteria Enforcement

### Monthly Review Process

**Every first Monday:**

1. Architecture board reviews Transition Component Register
2. Each owner reports:
   - Progress toward kill condition
   - Blockers
   - Revised target date (if needed)
3. Components past target date escalated
4. New transitional components added

### Escalation Path

**Component Past Target Date:**
1. Yellow flag: 2 weeks past target → Owner + manager meeting
2. Red flag: 4 weeks past target → Architecture board exception required
3. Black flag: 8 weeks past target → CTO review, possible rollback decision

### Exception Process

If kill condition cannot be met:

1. Document technical or business reason
2. Architecture board evaluates
3. Options:
   - Extend target date (with new kill condition)
   - Accept as permanent (reclassify in Schema Authority Register)
   - Force kill (accept breakage)

---

## Transition Metrics

### Key Metrics

| Metric | Target | Alert |
|--------|--------|-------|
| Transitional components on schedule | > 80% | < 70% |
| Components past target date | 0 | > 2 |
| Avg time in transition | < 90 days | > 120 days |
| Forced permanent reclassifications | 0 | > 0 |

### Dashboard

**Transition Health:**
- Components by status (on track / at risk / past date)
- Time in transition histogram
- Blocker resolution velocity

---

## Post-Transition Cleanup

### After Kill Condition Met

1. **Archive**
   - Backup transitional tables (retain 90 days)
   - Document final state

2. **Delete**
   - Remove code paths
   - Drop tables (after retention period)
   - Remove feature flags

3. **Verify**
   - Confirm no references in codebase
   - Monitor error logs for 7 days
   - Update documentation

4. **Celebrate**
   - Log debt reduction
   - Team recognition

---

## Forbidden Patterns

### Never Do

1. **"Temporary" without kill condition**
   - If you can't define "done," don't start

2. **Extend repeatedly**
   - Max 1 extension per component
   - Second extension = reclassification review

3. **Silent permanent adoption**
   - Components past date must be explicitly addressed

4. **Skip cleanup**
   - Deleted components must be fully removed

---

## Amendment Log

| Date | Component | Change | Reason |
|------|-----------|--------|--------|
| 2026-03-30 | `article` | Target extended from 2026-05-15 to 2026-06-30 | 3rd party integration delay |
| 2026-03-15 | `legacy_sync_v1` | Exception granted | Remote device logistics |

---

## Ownership and Governance

| Field | Value |
|-------|-------|
| **Owner** | Architecture Board |
| **Approver** | CTO |
| **Last Verified Date** | 2026-03-30 |
| **Verification Method** | Weekly component review |
| **Production Criticality** | Tier 1 |
| **Rollback Impact** | Cannot roll back - migration boundaries defined |
| **Verification Status** | Declared |

---

**Document Version:** 1.1  
**Owner:** Architecture Board  
**Review Cycle:** Monthly  
**Next Review:** 2026-04-06
