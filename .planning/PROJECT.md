# EPSILON Kernel Foundation

## What This Is

A DDD + Event-Sourcing governance substrate for ERP business modules. Currently at a critical transition point:

- **Phases 0-2 complete:** Semantic primitives (Value Objects, Exceptions, Result monad)
- **Current state:** DDD vocabulary exists, but the system does not execute
- **Goal:** Make the kernel runnable, not broader

## Core Value

**Transform from semantic substrate → actual ERP kernel**

The kernel must prove it can:
- Create an aggregate
- Persist domain events
- Reload aggregate from event stream
- Enforce optimistic concurrency
- Reject cross-tenant access
- Survive serialization roundtrips

Until these pass real integration tests, the kernel is aspirational, not operational.

## Architecture

**Pattern:** DDD + Event-Sourcing with strict layer separation

**Stack:**
- PHP 8.3+ with strict typing
- Spiral Framework 3.x + RoadRunner
- PostgreSQL event store
- PHPStan level 9

**Critical Law:**
```
Application ───▶ Domain
Infrastructure ──▶ Domain

Domain ──✗──▶ Infrastructure
Domain ──✗──▶ Application
```

Domain has zero framework dependencies.

## Scope

### Phase 2.5 — Runtime Spine Completion (CURRENT)

A focused implementation containing only:
- AggregateRoot base class
- DomainEvent contracts
- EventStore abstraction
- PostgreSQL Event Store
- Tenant isolation enforcement
- Integration test activation

### Approach: Test-First

Write failing integration tests first, then implement to make them pass.

**7 Minimum Kernel Truth Tests:**
1. `it_persists_a_new_aggregate_event_stream()`
2. `it_rehydrates_an_aggregate_from_persisted_events()`
3. `it_appends_new_events_to_existing_stream_with_correct_version()`
4. `it_rejects_stale_expected_version_writes()`
5. `it_rejects_cross_tenant_aggregate_access()`
6. `it_roundtrips_event_payload_and_metadata_without_loss()`
7. `it_returns_empty_when_stream_does_not_exist()`

### Implementation Order

1. Write the 7 failing tests first
2. Implement AggregateRoot (only what tests require)
3. Implement event contracts
4. Implement EventStore interface
5. Implement PostgreSQL Event Store
6. Add tenant enforcement

### Scope Files

```
packages/kernel/
├── tests/Integration/EventStore/*     # 7 kernel truth tests
├── tests/Fixture/Aggregate/*          # Test aggregate fixture
├── src/Domain/
│   ├── Aggregate/AggregateRoot.php
│   ├── Event/*
│   └── Exception/TenantIsolationViolationException.php
└── src/Infrastructure/EventStore/*
```

### Success Criterion

```bash
cd packages/kernel && vendor/bin/phpunit tests/Integration/EventStore
```

Passes **without skips**.

## Requirements

### Validated

(None yet — ship to validate)

### Active

- [ ] 7 integration tests written and failing
- [ ] AggregateRoot implements record/apply/replay/version
- [ ] DomainEvent contract with metadata
- [ ] EventStore interface (append/load only)
- [ ] PostgreSQL EventStore implementation
- [ ] Tenant isolation enforcement
- [ ] All 7 tests pass

### Out of Scope (for Phase 2.5)

- UI/UX — Not until kernel is runnable
- Projections/read models — Downstream of event store
- HTTP adapters — Downstream of runtime spine
- Saga orchestration — Requires working aggregates
- Outbox workers — Requires working event store
- Temporal/Financial VOs — Only as needed by tests

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| Test-first approach | Prevents architectural self-deception; forces honest validation | — Pending |
| 7 minimum tests | Defines kernel truth without over-engineering | — Pending |
| Single vertical spine | Shortest path from theory to execution | — Pending |

## Evolution

This document evolves at phase transitions and milestone boundaries.

**After each phase transition:**
1. Requirements invalidated? → Move to Out of Scope with reason
2. Requirements validated? → Move to Validated with phase reference
3. New requirements emerged? → Add to Active
4. Decisions to log? → Add to Key Decisions

---
*Last updated: 2026-04-04 after initialization*
