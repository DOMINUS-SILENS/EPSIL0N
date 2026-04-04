# EPSILONE ERP KERNEL FOUNDATION BLUEPRINT — PART 2

---

# SECTION 3 — BUILD ORDER (IMPLEMENTATION SEQUENCE)

This is the most critical section. **The order of construction determines the viability of the entire Kernel.**

## 3.1 Why Build Order Matters

**Wrong order:**

1. Create PostgreSQL schema first → schema shapes the domain model → domain becomes anemic CRUD
2. Create Spiral controllers first → HTTP shapes commands → commands leak request/response logic
3. Create ORM models first → ORM limitations shape aggregates → aggregates become getters/setters
4. Create repositories first → repository patterns shape aggregate design → aggregates become data bags

**Result:** The "framework" becomes the "architecture."

**Correct order:**

1. Pure domain first (VOs, Aggregates, Events) → domain is framework-agnostic
2. Application contracts next → orchestration layer is stable before infrastructure
3. Infrastructure abstractions after → domain doesn't depend on infrastructure
4. Infrastructure implementations last → PostgreSQL is pluggable
5. Spiral bindings last → framework is just the connector

**Result:** The architecture drives the framework, not vice versa.

---

# Corrected Canonical Sequence

## Phase 0 — Package Skeleton

### Why first

You need namespace boundaries before code.

### Why not before

Nothing can exist before physical structure.

### Deliverables

```text
packages/kernel/
src/
tests/
composer.json
phpstan.neon
phpunit.xml
README.md
```

---

## Phase 1 — Core Failure & Result Semantics

### Why first

Before any object can be trusted, the kernel must define:

* what is invalid
* what is exceptional
* what is a business failure
* what is a programming failure

This determines whether:

* VOs throw
* handlers return `Result`
* repositories signal concurrency failure consistently

### Why not before

No code exists yet.

### Deliverables

* `KernelException`
* `DomainException`
* `ValidationException`
* `ConcurrencyConflictException`
* `AuthorizationException`
* `BusinessRuleViolationException`
* `NotFoundException`
* `Result<TData, TError>`
* `ErrorCode`
* `ErrorDetail`

### Consequence

This prevents ad hoc exception chaos later.

---

## Phase 2 — Fundamental Value Object Base + Identity/Traceability Primitives

### Why first

Everything else depends on trusted primitives.

### Why not before

They need consistent error/validation semantics.

### Deliverables

#### Base

* `ValueObject`

#### Identity / Traceability

* `TenantId`
* `UserId`
* `ActorId`
* `EventId`
* `CorrelationId`
* `CausationId`
* `DocumentId`

#### Common foundational primitives

* `EmailAddress`
* `TenantSlug`
* `ResourceReference`

### Consequence

This kills primitive obsession before it spreads.

---

## Phase 3 — Temporal & Numeric Primitives

### Why here

These are still primitive laws, but slightly more semantically loaded than identity.

They underpin:

* compliance
* posting legality
* workflow timing
* financial safety

### Why not before

They depend on the VO base and stable exception semantics.

### Deliverables

#### Temporal

* `Timestamp`
* `BusinessDate`
* `BusinessPeriod`
* `EffectiveDateRange`

#### Numeric / financial

* `Money`
* `CurrencyCode`
* `Quantity`
* `UnitOfMeasure`
* `Percentage`
* `Rate`

### Consequence

This prevents later module-specific corruption of time and arithmetic.

---

## Phase 4 — Event Law

### Why here

Aggregates cannot exist before the event contract exists.

The kernel must first define:

* what an event is
* what metadata is mandatory
* how causation/correlation work

### Why not before

Events depend on identity and temporal primitives.

### Deliverables

* `DomainEvent`
* `EventMetadata`
* `EventEnvelope`
* `EventSchemaVersion`
* `IEventUpgradable` or upgrader contract marker
* canonical event payload rules

### Consequence

This ensures every future aggregate emits structurally valid facts.

---

## Phase 5 — Aggregate Law

### Why here

Only now can you define how state evolves.

### Why not before

Aggregates depend on:

* VO base
* event law
* result/error semantics
* identity primitives

### Deliverables

* `Entity<TId>`
* `AggregateRoot<TId>`
* `IHasDomainEvents`
* aggregate versioning contract
* `Raise()`
* `When()`
* `ClearEvents()`
* `Rehydrate()/Reconstitute()`
* lifecycle hooks:

  * `OnBeforePersist()`
  * `OnAfterRehydrate()`

### Consequence

This is where the write model becomes real.

---

## Phase 6 — Domain Contracts (Pure Kernel Law Interfaces)

### Why here

Now that the domain laws exist, define what the rest of the system is allowed to depend on.

### Why not before

You cannot define clean contracts until the objects they operate on are stable.

### Deliverables

#### Persistence / storage-facing contracts

* `IRepository<TAggregate, TId>`
* `ISpecification<T>`
* `ISpecificationRepository<T>`
* `IUnitOfWork`

#### Security / authority contracts

* `ISecurityContext`
* `IAuthorizationService`
* `IActionRequirement`

#### Temporal contracts

* `IBusinessCalendar`
* `IClock`

#### Audit / diagnostics contracts

* `IAuditTrail`
* `IReplayVerifier`
* `ITracer`
* `IMetrics`
* `ILogger`

### Consequence

This locks the kernel's dependency direction before infrastructure starts contaminating it.

---

## Phase 7 — Application Boundary Contracts

### Why here

Only after domain law and infrastructure-facing contracts exist should orchestration contracts be defined.

### Why not before

Otherwise commands and handlers will guess at domain shape.

### Deliverables

* `ICommand<TResult>`
* `IQuery<TResult>`
* `ICommandHandler<TCommand, TResult>`
* `IQueryHandler<TQuery, TResult>`
* `IValidator<T>`
* `ValidationResult`
* `ICommandBus`
* `IQueryBus`

### Consequence

This creates the stable service boundary for all future modules.

---

## Phase 8 — Delivery Safety Contracts

### Why here

Once commands/events exist, you can now define safe execution semantics.

### Why not before

Idempotency/outbox/inbox make no sense before commands/events/repositories exist.

### Deliverables

* `IEventStore`
* `ISnapshotStore`
* `IEventSerializer`
* `IOutboxStore`
* `IProcessedMessageStore`
* `IIdempotencyStore`

### Consequence

This is where the kernel becomes operationally safe in distributed conditions.

---

## Phase 9 — Kernel-Owned Governance Models

### Why here

Only now should you create **actual kernel-owned domain objects**, and only the ones that are truly universal.

### Why not before

Before this point, you would just be inventing models without stable law.

### Deliverables

Only if truly kernel-owned:

* `Tenant` or `TenantContextPolicy` model
* `ApprovalRequest` primitive/aggregate if universally required
* `LifecycleState`
* `TransitionRule`
* `ApprovalDecision`
* `RetentionPolicy`
* `FeatureFlag`
* `MfaConfig`

### Important restriction

Do **not** dump "interesting business concepts" here.

Only universal governance objects belong.

### Consequence

This prevents feature contamination inside the kernel.

---

## Phase 10 — Application Behaviors / Policies / Sagas

### Why here

Now the kernel can define orchestration mechanics safely.

### Why not before

Before contracts and governance objects exist, policies and sagas become arbitrary.

### Deliverables

* validation behavior
* authorization behavior
* idempotency behavior
* transaction behavior
* audit behavior
* telemetry behavior
* base `Policy`
* base `Saga`

### Consequence

This gives all future modules a safe execution pipeline.

---

## Phase 11 — Diagnostics Contracts and Verification Logic

### Why here

Now that the execution model exists, define how correctness is verified.

### Why not before

You cannot verify replay or projection determinism before event and persistence semantics exist.

### Deliverables

* replay verification contracts
* projection hash verification model
* compliance verification model
* deterministic replay rules

### Consequence

This is what turns "event-sourced" from a belief into a testable property.

---

## Phase 12 — PostgreSQL Infrastructure Implementations

### Why here

Now you can finally implement storage without forcing storage concerns into the domain.

### Why not before

If implemented earlier, PostgreSQL/ORM assumptions will leak upward and deform the kernel.

### Deliverables

* PostgreSQL event store
* snapshot store
* repository implementations
* UoW implementation
* outbox implementation
* inbox implementation
* idempotency implementation
* audit persistence
* projection checkpoint persistence

### Consequence

This is where the kernel becomes physically runnable.

---

## Phase 13 — Spiral Integration / Bootloaders / Runtime Wiring

### Why here

Framework wiring must bind already-defined contracts to already-existing implementations.

### Why not before

A bootloader before implementations is just empty ceremony.

### Deliverables

* Spiral bootloaders
* service bindings
* RoadRunner-safe scoped context reset
* queue consumer registration
* console integration
* middleware/interceptors for:

  * tenant resolution
  * correlation
  * security context
  * audit attribution

### Consequence

This is where the kernel becomes executable inside Spiral without letting Spiral define it.

---

## Phase 14 — Diagnostics Implementations & Deterministic Test Harness

### Why last

Verification is only meaningful once the system exists concretely.

### Why not before

You cannot verify infrastructure that is not implemented.

### Deliverables

* replay test harness
* projection determinism tests
* event hash verification tests
* concurrency race tests
* idempotency duplication tests
* tenant leakage tests
* temporal legality tests

### Consequence

This is where the kernel earns trust.

---

# Corrected Table

| Phase | What                                      | Why First                                               | Why Not Before                    |
| ----- | ----------------------------------------- | ------------------------------------------------------- | --------------------------------- |
| 0     | Package Skeleton                          | Physical boundary comes first                           | No code exists yet                |
| 1     | Failure & Result Semantics                | All validation and failure paths depend on this         | Nothing to define earlier         |
| 2     | Identity & Base VOs                       | Everything depends on trusted primitives                | Needs failure semantics           |
| 3     | Temporal & Numeric Primitives             | Time and arithmetic laws must be universal              | Needs VO base                     |
| 4     | Event Law                                 | Aggregates cannot exist before events                   | Needs IDs and timestamps          |
| 5     | Aggregate Law                             | State evolution depends on event law                    | Needs events and primitives       |
| 6     | Domain Contracts                          | Clean dependency boundaries must exist before app/infra | Needs stable domain law           |
| 7     | Application Contracts                     | Orchestration depends on domain shape                   | Needs domain + contracts          |
| 8     | Delivery Safety Contracts                 | Outbox/inbox/idempotency depend on commands/events      | Needs app + infra contracts       |
| 9     | Kernel-Owned Governance Models            | Only now can universal kernel models be created safely  | Needs laws and contracts first    |
| 10    | Behaviors / Policies / Sagas              | Execution mechanics need all prior contracts            | Too arbitrary earlier             |
| 11    | Diagnostics Contracts                     | Verification model depends on execution semantics       | Needs replayable architecture     |
| 12    | PostgreSQL Implementations                | Concrete persistence must come after contracts          | Otherwise domain leaks to storage |
| 13    | Spiral Wiring / Bootloaders               | Bindings require real implementations                   | Otherwise DI is empty             |
| 14    | Diagnostic Implementations / Test Harness | Trust must be verified after system exists              | Nothing to verify earlier         |

---

# The key correction

The correct kernel sequence is:

> **law first → primitive first → contract first → model later → infrastructure later → framework last**

That is the right order.

Anything else creates hidden authority drift and persistence leakage.

---

# The rule to keep in your head

When unsure what to build next, ask:

> **Does this define a law, a primitive, a contract, or an implementation?**

Then obey this precedence:

```text
Law
→ Primitive
→ Contract
→ Governance Model
→ Implementation
→ Framework Wiring
→ Verification
```

That is the build discipline that keeps the kernel clean.