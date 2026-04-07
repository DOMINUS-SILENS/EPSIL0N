# Architecture

**Analysis Date:** 2026-04-06

## Pattern Overview

**Overall:** DDD (Domain-Driven Design) + Event-Sourcing Native

**Current Maturity: "Write-Only" Runtime**
The kernel is currently a "Write-Only" system. It can atomically record and reconstitute state (Write Side), but it cannot yet project that state for efficient querying (Read Side) or evolve event schemas over time (Operational Maturity).

**Key Characteristics:**
- **Event-Sourced State:** State is never mutated directly; it is reconstructed from a chronological stream of events via `AggregateRoot::reconstituteFromEvents`.
- **Structural Multi-Tenancy:** `TenantId` is a mandatory, immutable property of every aggregate, ensuring absolute isolation at the architectural level.
- **Optimistic Concurrency:** Version-based conflict detection using `ExpectedVersion` during event append operations to prevent lost updates.
- **Explicit Failure Semantics:** Use of a `Result` monad for expected business failures, reserving exceptions for truly exceptional/unrecoverable infrastructure or programming errors.
- **Strict Dependency Inversion:** The Domain layer is the center of the system and has zero dependencies on external frameworks, databases, or transport layers.

## Event Sourcing Completeness Analysis

The kernel's implementation is measured against the three pillars of production-grade event sourcing:

### 1. The Write Side (Command/State) — 🟢 Operational
*   **Status:** High. Basic "Load $\to$ Mutate $\to$ Save" cycle is implemented.
*   **Core Components:** `AggregateRoot`, `IEventStore`, `PostgreSqlEventStore`.
*   **Governance:** Hardened optimistic concurrency via PostgreSQL UNIQUE constraints and structural `TenantId` isolation.

### 2. The Read Side (Query/Projection) — 🔴 Missing
*   **Status:** Non-existent. The system is currently a "black hole" (data enters but cannot be queried efficiently).
*   **Required Components:**
    *   **Projection Engine:** A mechanism to consume the event stream and update specialized read models.
    *   **Read Models:** Flattened, query-optimized tables separate from the event log.
    *   **Query Bus:** A routing layer to dispatch queries to read models.

### 3. Operational Maturity — 🔴 Missing
*   **Status:** Non-existent. The system is fragile to schema changes and scaling.
*   **Required Components:**
    *   **Event Evolution (Upcasting):** Logic to transform old event versions to new ones during replay.
    *   **Performance (Snapshots):** `ISnapshotStore` to prevent linear performance degradation as event streams grow.
    *   **Distribution (Outbox/Inbox):** Guaranteed at-least-once delivery of events to external systems.
    *   **Orchestration (Sagas):** Process managers to coordinate multi-aggregate workflows.

---

## Layers


**Domain Layer:**
- Purpose: The "business-law-neutral truth layer." Defines the core invariants and rules of the ERP substrate.
- Location: `packages/kernel/src/Domain/`
- Contains: `AggregateRoot`, `ValueObject`, `DomainEvent`, identity primitives (e.g., `TenantId`, `UserId`).
- Depends on: Nothing external.
- Used by: Application Layer.

**Application Layer:**
- Purpose: Orchestration layer. Coordinates use cases, manages transactions, and enforces authorization.
- Location: `packages/kernel/src/Application/`
- Contains: Command/Query contracts, Handlers, Sagas, and Execution Pipeline behaviors (Audit, Validation).
- Depends on: Domain Layer, Infrastructure Contracts.
- Used by: Infrastructure Layer (via delivery mechanisms).

**Infrastructure Layer:**
- Purpose: Concrete implementation of technical contracts.
- Location: `packages/kernel/src/Infrastructure/`
- Contains: `PostgreSqlEventStore`, Event Serializers, Spiral Bootloaders, Security Adapters.
- Depends on: Domain Layer, Application Layer, External Libraries (Spiral, PostgreSQL).
- Used by: Entry points (Controllers, Console, Queue).

**Diagnostics Layer:**
- Purpose: Verification of the "trust" properties of the kernel.
- Location: `packages/kernel/src/Diagnostics/`
- Contains: Replay verification, projection consistency checks, and compliance validation.
- Depends on: Domain, Infrastructure.
- Used by: Test harness and operational tooling.

**Support Layer:**
- Purpose: Truly generic utilities.
- Location: `packages/kernel/src/Support/`
- Contains: Base Exception hierarchy (`KernelException`), Collection utilities.
- Depends on: Nothing.
- Used by: All layers.

## Data Flow

**Command Execution Flow:**
1. **Entry Point:** Request received (HTTP/Console/Queue).
2. **Application Boundary:** Command created $\rightarrow$ Validated $\rightarrow$ Authorized (via `IAuthorizationService`).
3. **Aggregate Invocation:** Repository loads aggregate $\rightarrow$ Aggregate method called $\rightarrow$ Aggregate `raise()` events.
4. **Persistence:** Events persisted to `IEventStore` with `ExpectedVersion` check $\rightarrow$ Aggregate marked as committed.
5. **Outcome:** `Result` monad returned to caller.

**State Reconstruction Flow:**
1. **Load Request:** `IRepository::getById` called with `TenantId`.
2. **Event Retrieval:** `IEventStore::load` retrieves all events for the stream.
3. **Rehydration:** `AggregateRoot::reconstituteFromEvents` applies events chronologically via `apply()`.
4. **Ready State:** Aggregate is now in its current state and ready for mutation.

## Key Abstractions

**AggregateRoot:**
- Purpose: Event-sourced state container.
- Examples: `packages/kernel/src/Domain/Shared/Aggregate/AggregateRoot.php`
- Pattern: Mutations only via `raise()`, state reconstruction via `apply()`.

**Result<TData>:**
- Purpose: Explicit success/failure monad to avoid exception-driven business logic.
- Examples: `packages/kernel/src/Domain/Shared/Result/Result.php`
- Pattern: Factory methods `success()` and `failure()`, functional operators `map()`, `flatMap()`, `match()`.

**IEventStore:**
- Purpose: Append-only event log contract.
- Examples: `packages/kernel/src/Infrastructure/Contract/EventStore/IEventStore.php`
- Pattern: `append(TenantId, streamId, ExpectedVersion, events)`.

## Entry Points

**Spiral Bootloaders:**
- Location: `packages/kernel/src/Infrastructure/Spiral/Bootloader/`
- Responsibilities: Binding interfaces to implementations in the DI container.

**PostgreSQL Event Store:**
- Location: `packages/kernel/src/Infrastructure/Persistence/EventStore/PostgreSqlEventStore.php`
- Responsibilities: Physical storage of the event stream.

## Error Handling

**Strategy:** Split between "expected business outcomes" and "unexpected system failures."

**Patterns:**
- **Business Failures:** Returned as `Result::failure(ErrorDetail)`.
- **Domain Violations:** Thrown as `BusinessRuleViolationException`.
- **Concurrency Conflicts:** Thrown as `ConcurrencyConflictException` when `ExpectedVersion` mismatches.
- **Tenant Violations:** Thrown as `TenantIsolationViolationException` if cross-tenant access is attempted.

## Cross-Cutting Concerns

**Logging:** Handled by Infrastructure adapters via PSR-3.
**Validation:** Performed in the Application layer BEFORE domain invocation.
**Authentication:** Resolved at the Infrastructure edge and passed as `ActorId`/`TenantId` into the domain.
**Multi-Tenancy:** Enforced structurally; `TenantId` is a mandatory parameter for all repository and event store operations.

---

*Architecture analysis: 2026-04-06*
