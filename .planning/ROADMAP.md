# Roadmap — Phase 2.5: Runtime Spine Completion

## Overview

**Goal:** Transform kernel from semantic substrate to runnable infrastructure.

**Approach:** Test-first — write 7 failing integration tests, then implement to make them pass.

---

## Phase 1: Integration Test Harness

**Goal:** Create test infrastructure that can actually run against PostgreSQL.

**Requirements:** None (infrastructure phase)

**Success Criteria:**
- Test database connection established
- Schema migrations run per test suite
- Integration test base class functional
- Tests fail cleanly (not with infrastructure errors)

---

## Phase 2: Failing Test Suite

**Goal:** Write all 7 kernel truth tests. All must fail for legitimate reasons.

**Requirements:** TEST-01 through TEST-07

**Success Criteria:**
- All 7 tests exist in `tests/Integration/EventStore/`
- All 7 tests fail with "implementation missing" type errors
- Test fixture aggregate exists
- Test domain events exist

---

## Phase 3: AggregateRoot Base Class

**Goal:** Implement only what tests require. Nothing more.

**Requirements:** AGG-01 through AGG-05

**Success Criteria:**
- `record()` appends to pending buffer
- `apply()` dispatches to handler
- `replay()` reconstructs from history without re-recording
- Version tracking works
- `pullUncommittedEvents()` returns pending events

---

## Phase 4: DomainEvent Contracts

**Goal:** Define event envelope and metadata structure.

**Requirements:** EVT-01 through EVT-04

**Success Criteria:**
- Event envelope has required fields
- Metadata structure is stable
- Serialization boundary defined
- Tests reference concrete event types

---

## Phase 5: EventStore Interface

**Goal:** Define minimal persistence contract.

**Requirements:** ES-01, ES-02

**Success Criteria:**
- `append()` signature defined
- `load()` signature defined with tenant scoping
- Interface has no extra methods beyond test requirements

---

## Phase 6: PostgreSQL EventStore Implementation

**Goal:** Make tests pass. Kernel becomes real.

**Requirements:** PG-01 through PG-05

**Success Criteria:**
- All 7 integration tests pass
- Unique (streamId, version) constraint enforced
- Optimistic concurrency rejects stale writes
- Tenant scoping prevents cross-tenant access
- Event ordering is guaranteed

---

## Phase 7: Tenant Isolation Enforcement

**Goal:** Make cross-tenant access impossible through normal repository usage.

**Requirements:** TEN-01 through TEN-04

**Success Criteria:**
- `TenantIsolationViolationException` exists
- Repository load requires tenantId
- Event append includes tenant metadata
- TEST-05 passes (cross-tenant rejection)

---

## Phase 8: Spine Verification

**Goal:** Prove kernel is runnable end-to-end.

**Requirements:** All requirements

**Success Criteria:**
- `vendor/bin/phpunit tests/Integration/EventStore` passes
- No skipped tests
- Test coverage shows AggregateRoot, EventStore, and TenantIsolation exercised
- Manual verification: create aggregate, persist, reload, verify state

---

## Milestone Completion

After Phase 8, the kernel graduates from "semantic substrate" to "runtime infrastructure."

Next milestone would be:
- Temporal/Financial VOs (as needed)
- Repository templates
- Snapshot strategy
- Outbox pattern
- Projection infrastructure

---
*Roadmap generated 2026-04-04*
