# Architecture

**Analysis Date:** 2026-04-04

## Pattern Overview

**Overall:** Domain-Driven Design (DDD) with Event-Sourcing Native

The EPSILONE Kernel is a **governance substrate** - not a utility library or framework wrapper. It provides structural correctness guarantees for ERP bounded contexts through strict architectural laws.

**Key Characteristics:**
- Domain-centric dependency inversion (Domain depends on nothing external)
- Event-sourced aggregates with mandatory versioning
- Multi-tenancy as structural law (TenantId required on every aggregate)
- Optimistic concurrency with explicit version tokens
- Result monad for explicit error handling
- Idempotency built into command pipeline
- Audit trail automatic and non-negotiable

## Layers

### Domain Layer
- **Purpose:** Business-law-neutral truth layer. Defines invariants, domain primitives, identity, authority semantics, event laws, temporal legality.
- **Location:** `packages/kernel/src/Domain/`
- **Contains:** Value objects, entities, aggregates, domain events, domain contracts, domain services
- **Depends on:** Nothing external (no framework, no ORM, no infrastructure)
- **Used by:** Application layer

**Critical Rule:** Domain imports MUST NOT include Spiral, Doctrine, RoadRunner, or any infrastructure concern.

### Application Layer
- **Purpose:** Orchestration layer. Coordinates use cases, handles commands/queries, enforces authorization, manages transactions.
- **Location:** `packages/kernel/src/Application/`
- **Contains:** Commands, queries, handlers, validators, policies, sagas, behaviors
- **Depends on:** Domain layer, Infrastructure contracts
- **Used by:** Infrastructure layer (via handlers)

**Critical Rule:** No business logic in Application layer - only orchestration and authorization checks.

### Infrastructure Layer
- **Purpose:** Implementation layer. Provides concrete implementations of domain/application contracts.
- **Location:** `packages/kernel/src/Infrastructure/`
- **Contains:** Event store, repositories, persistence, serialization, Spiral bootloaders, security adapters
- **Depends on:** Domain contracts, Application contracts, external packages (Spiral, PostgreSQL, RoadRunner)
- **Used by:** Entry points (controllers, console commands, queue consumers)

**Critical Rule:** Infrastructure implements contracts - it does not define domain concepts.

### Diagnostics Layer
- **Purpose:** Verification and compliance checking. Ensures event-sourcing correctness, replay determinism, audit verification.
- **Location:** `packages/kernel/src/Diagnostics/`
- **Contains:** Replay verification, projection consistency, compliance checks
- **Depends on:** Domain, Infrastructure
- **Used by:** Test harness, operational tooling

### Support Layer
- **Purpose:** Cross-cutting utilities that are truly generic (no domain meaning).
- **Location:** `packages/kernel/src/Support/`
- **Contains:** Exception hierarchy, collection utilities, test kit
- **Depends on:** Nothing
- **Used by:** All layers

**Critical Rule:** Support must stay small and boring. If a class has domain meaning, it does NOT belong here.

## Data Flow

### Command Execution Flow

1. **Input Validation** - Validate command structure (fail fast on malformed input)
2. **Authorization Check** - Verify actor has permission (do not waste resources for unauthorized)
3. **Idempotency Check** - If idempotency key provided, check for existing result
4. **Begin Transaction** - Start atomic unit of work
5. **Audit Attribution** - Set actor context for audit trail
6. **Execute Handler** - Load aggregate, invoke domain method, collect events
7. **Persist** - Save aggregate state, uncommitted events, outbox messages, audit entries (all atomic)
8. **Commit Transaction** - All or nothing
9. **Emit Telemetry** - Record metrics for successful operations only
10. **Return Result** - Handler completed, transaction committed

### Event Flow

```
Aggregate.raise(DomainEvent)
    ↓
Aggregate collects events internally
    ↓
Repository.save(aggregate)
    ↓
UnitOfWork persists:
    - Aggregate state (INSERT/UPDATE)
    - Domain events (append-only)
    - Outbox messages (for distribution)
    - Audit entries
    ↓
Transaction commits atomically
    ↓
Event dispatcher publishes from outbox
    ↓
Projections update read models
```

### State Reconstruction Flow

```
Repository.getById(aggregateId, tenantId)
    ↓
EventStore.getStream(aggregateId, fromVersion=0)
    ↓
Optional: SnapshotStore.load(aggregateId) for performance
    ↓
Aggregate.reconstituteFrom(events)
    ↓
Each event.applyTo(aggregate) via When() method
    ↓
Return hydrated aggregate with correct version
```

## Key Abstractions

### AggregateRoot<TId>
- **Purpose:** Event-sourced state container. The only place where business state mutations occur.
- **Examples:** `packages/kernel/src/Domain/Shared/Aggregate/` (contracts)
- **Pattern:** All state changes via `raise()` method, state reconstruction via `When()` method
- **Invariants:**
  - Immutable TenantId
  - Version tracked for optimistic concurrency
  - No public setters
  - Events collected internally, not externally appended

### ValueObject
- **Purpose:** Immutable, self-validating primitives representing domain concepts.
- **Examples:**
  - `packages/kernel/src/Domain/Identity/TenantId.php`
  - `packages/kernel/src/Domain/Identity/UserId.php`
  - `packages/kernel/src/Domain/Tenancy/EmailAddress.php`
- **Pattern:** Private constructor, factory methods (`fromString()`, `generate()`), value equality

### DomainEvent
- **Purpose:** Append-only facts representing what happened in the domain.
- **Examples:** `packages/kernel/src/Domain/Shared/Event/` (contracts)
- **Pattern:** Immutable record with mandatory metadata (EventId, TenantId, CorrelationId, CausationId, Timestamp, SchemaVersion)
- **Invariants:** Past-tense naming, no mutable references, deterministic serialization

### Result<TData>
- **Purpose:** Explicit success/failure monad for application layer returns.
- **Examples:** `packages/kernel/src/Domain/Shared/Result/Result.php`
- **Pattern:** `Result::success($value)` or `Result::failure($error)`, with `map()`, `flatMap()`, `match()` operations
- **Usage:** Command handlers return Result, never throw for business failures

### IRepository<TAggregate, TId>
- **Purpose:** Aggregate persistence contract. Loads and saves aggregates by ID.
- **Examples:** `packages/kernel/src/Infrastructure/Contract/Persistence/` (interface)
- **Pattern:** `getById($id, TenantId)`, `add($aggregate)`, `save($aggregate)`, `remove($aggregate)`
- **Invariants:** TenantId is mandatory, never optional

### IEventStore
- **Purpose:** Append-only event log contract. Persists and retrieves domain events.
- **Examples:** `packages/kernel/src/Infrastructure/Contract/EventStore/` (interface)
- **Pattern:** `append(TenantId, streamId, expectedVersion, events)`, `getStream(TenantId, streamId, fromVersion)`
- **Invariants:** Concurrency conflict throws `ConcurrencyConflictException`

### ICommand<TResult>
- **Purpose:** Intent to mutate state. Represents what was requested.
- **Examples:** `packages/kernel/src/Application/Contract/Command/` (interface)
- **Pattern:** Data carrier with optional `getIdempotencyKey()`
- **Invariants:** No business logic in command, just data

### IAuthorizationService
- **Purpose:** Verify actor has permission before aggregate invocation.
- **Examples:** `packages/kernel/src/Infrastructure/Contract/Security/` (interface)
- **Pattern:** `authorize(IActionRequirement, ISecurityContext)` throws `AuthorizationException` if denied
- **Invariants:** Authorization BEFORE aggregate method call, never inside aggregate

## Entry Points

### HTTP Controllers (Framework Layer)
- **Location:** Bounded context packages (not in kernel)
- **Purpose:** Convert HTTP requests to commands/queries
- **Triggers:** RoadRunner workers via Spiral HTTP middleware
- **Responsibilities:** Request parsing, command construction, response formatting

### Console Commands
- **Location:** `packages/kernel/src/Infrastructure/Spiral/Console/`
- **Purpose:** CLI operations, maintenance tasks, diagnostics
- **Triggers:** `php spiral command:name`
- **Responsibilities:** Bootstrap kernel, invoke command handlers, output results

### Queue Consumers
- **Location:** `packages/kernel/src/Infrastructure/Spiral/Queue/`
- **Purpose:** Async event processing, saga orchestration
- **Triggers:** RoadRunner queue workers
- **Responsibilities:** Deserialize message, invoke handler, acknowledge/retry

### Kernel Bootloader
- **Location:** `packages/kernel/src/Infrastructure/Spiral/Bootloader/`
- **Purpose:** Service registration, dependency injection container setup
- **Triggers:** Spiral application bootstrap
- **Responsibilities:** Bind interfaces to implementations, configure services

## Error Handling

**Strategy:** Exception hierarchy with semantic distinction between failure types.

**Patterns:**
- `KernelException` - Infrastructure/structural failures (thrown)
- `DomainException` - Business rule violations (thrown for programming errors)
- `Result<T>` - Expected business failures (returned, not thrown)
- `ValidationException` - Input validation failures (thrown at boundary)
- `ConcurrencyConflictException` - Optimistic concurrency version mismatch (thrown)
- `AuthorizationException` - Permission denied (thrown)
- `BusinessRuleViolationException` - Domain invariant violations (thrown)
- `NotFoundException` - Resource not found (thrown)

**Exception Location:** `packages/kernel/src/Support/Exception/`

## Cross-Cutting Concerns

### Logging
- **Approach:** Via Spiral/Psr\Log\LoggerInterface
- **Location:** Infrastructure layer adapters
- **Pattern:** Domain defines no logging, Infrastructure provides implementations

### Validation
- **Approach:** IValidator interface, ValidationResult for errors
- **Location:** Application layer (`Application/Contract/Validation/`)
- **Pattern:** Validate BEFORE command execution, fail fast on invalid input

### Authentication/Authorization
- **Approach:** ISecurityContext for actor context, IAuthorizationService for permission checks
- **Location:** Infrastructure layer (`Infrastructure/Contract/Security/`, `Infrastructure/Security/`)
- **Pattern:** Authorization in Application layer BEFORE aggregate invocation, never inside Domain

### Multi-Tenancy
- **Approach:** TenantId is mandatory on every aggregate and repository operation
- **Location:** Domain layer (`Domain/Identity/TenantId.php`)
- **Pattern:** No ambient global tenant state, explicit tenant on every operation
- **Enforcement:** Repository contracts require TenantId parameter

### Concurrency
- **Approach:** Optimistic versioning on all aggregates
- **Location:** Domain layer (AggregateRoot version tracking)
- **Pattern:** Version mismatch throws `ConcurrencyConflictException`
- **No silent overwrites:** Callers must handle version conflicts explicitly

### Audit
- **Approach:** Automatic on every mutating command
- **Location:** Infrastructure layer (`Infrastructure/Audit/`)
- **Pattern:** Every state change produces audit entry with actor, timestamp, correlation

---

*Architecture analysis: 2026-04-04*