# Roadmap — EPSILON Kernel Foundation

## Overview

**Goal:** Transform kernel from semantic substrate to a production-grade Event Sourcing engine.
**Current Status:** Phase 2.5 (Runtime Spine) Complete. Write-side is operational.

---

## Milestone 1: Runtime Spine (COMPLETE ✅)
*The transition from semantic primitives to a runnable execution engine.*

- **Phase 1: Integration Test Harness** - Established PostgreSQL test infra.
- **Phase 2: Failing Test Suite** - Defined kernel truth tests.
- **Phase 3: AggregateRoot Base Class** - Implemented event-sourced state container.
- **Phase 4: DomainEvent Contracts** - Defined event envelopes and metadata.
- **Phase 5: EventStore Interface** - Defined persistence contracts.
- **Phase 6: PostgreSQL EventStore Implementation** - Hardened concurrency and persistence.
- **Phase 7: Tenant Isolation Enforcement** - Structural tenant boundary enforcement.
- **Phase 8: Spine Verification** - Full Load → Mutate → Save cycle proven.

---

## Milestone 2: The Read Side (Current Focus)
*Turning the event log into queryable state via CQRS.*

### Phase 5: Projection Infrastructure
- **Goal:** Enable the kernel to turn the event log into queryable state.
- **Key Tasks:**
    - Implement `IEventProjector` interface.
    - Create the `ProjectionEngine` to consume the event stream.
    - Implement first concrete Read Model (state table).
    - Establish "Event → Projector" dispatch loop.
- **Success Criteria:** Events in `EventStore` are automatically reflected in Read Model tables.

### Phase 6: CQRS Routing (Command/Query Bus)
- **Goal:** Establish a clean separation between mutation and retrieval.
- **Key Tasks:**
    - Implement `ICommandBus` and `IQueryBus`.
    - Create routing layer to dispatch queries to read models.
    - Implement `IdempotencyGuard` using `CorrelationId`.
- **Success Criteria:** Requests are routed as either Commands or Queries without overlapping logic.

### Phase 6.5: Offline Event Queue
- **Goal:** Enable offline-first mobile sync with local event queue and replay on reconnect.
- **Key Tasks:**
    - Implement `IMobileOfflineQueue` interface.
    - Create `PendingEvent` value object.
    - Create `PostgreSqlOfflineQueue` implementation with new tables.
    - Implement `QueueProcessor` for replay logic.
    - Create `ConflictResolver` with merge strategies (LastWriteWins, ServerWins, ClientWins, PromptUser).
- **Success Criteria:** Mobile clients can queue events offline and replay on reconnect with proper conflict handling.
- **Requirements:** OFFLINE-QUEUE-01, OFFLINE-QUEUE-02, OFFLINE-QUEUE-03

**Plans:**
- [x] 06.5-01-PLAN.md — Offline Event Queue Implementation (OPTIMAL: vector clock, DeviceId, SyncVersion)

---

## Milestone 3: Operational Maturity
*Ensuring scalability, evolvability, and distribution.*

### Phase 7: Event Evolution & Performance
- **Goal:** Ensure system scales and evolves without data loss.
- **Key Tasks:**
    - Implement `IEventUpgrader` pattern for schema evolution (Upcasting).
    - Implement `ISnapshotStore` to cap rehydration time.
    - Create `SnapshotManager` for automated snapshotting.
- **Success Criteria:** Old event versions load into new aggregates; load time remains constant.

### Phase 8: Distribution & Orchestration
- **Goal:** Connect the kernel to the outside world and coordinate complex flows.
- **Key Tasks:**
    - Implement **Outbox Pattern** for guaranteed external delivery.
    - Create `Saga` / `ProcessManager` base classes.
    - Implement structured `AuditLog` service.
- **Success Criteria:** Events reliably pushed to external brokers; multi-step Sagas can be tracked.

---
*Roadmap updated 2026-04-06*