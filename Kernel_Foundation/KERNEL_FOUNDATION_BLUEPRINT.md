# EPSILONE ERP KERNEL FOUNDATION BLUEPRINT

**Status:** Canonical Foundation Architecture
**Target Stack:** PHP 8.3+ | Spiral 3.x | RoadRunner | PostgreSQL
**Architecture:** DDD + OOD + Event-Sourcing Native
**Build Model:** Foundation-First, then bounded contexts

---

# SECTION 1 — KERNEL DOCTRINE

## 1.1 What is the EPSILONE Kernel?

The EPSILONE Kernel is the **non-negotiable governance substrate** upon which every ERP business module must execute.

It is not:
- Finance, Inventory, HR, CRM, or Procurement
- a framework abstraction layer
- ORM-mediated persistence
- a collection of "helpers" or utilities
- a way to make code "cleaner"

It **is**:
- the invariant business rule system that survives across all domains
- the tenant and organizational boundary enforcer
- the authority and audit substrate
- the event-native state persistence model
- the temporal governance arbiter
- the idempotency and consistency guarantor
- the deterministic replay and verification system

## 1.2 What the Kernel Owns

The Kernel is responsible for:

### State Integrity
- aggregate immutability guarantees
- event-sourced state reconstruction
- optimistic concurrency via version tokens
- invariant enforcement at aggregate boundary

### Authority Integrity
- actor / role / delegation distinctly modeled
- authorization decisions are auditable
- cross-tenant boundaries cannot be crossed
- delegated authority has expiration

### Temporal Integrity
- business calendar enforcement
- posting period legality checks
- effective-date / effective-to legality
- backdating policy decisions
- no state changes in closed periods without override

### Traceability Integrity
- immutable audit trail
- event stream is append-only
- causation and correlation preserved
- every material change is reconstructable

### Isolation Integrity
- tenant_id is mandatory everywhere
- no ambient global state that bypasses tenant ownership
- no cross-tenant reads except through explicit authorization
- schema is shared but enforcement is strict

## 1.3 What the Kernel Must Never Own

**Business domain workflows:**
- invoice line calculations
- stock allocation logic
- payroll rules
- recruitment pipeline
- customer segmentation

**Domain-specific policies:**
- credit limit rules (Finance domain owns)
- minimum stock thresholds (Inventory owns)
- salary band logic (HR owns)
- lead scoring (Sales owns)

**UI/Transport concerns:**
- REST endpoints
- GraphQL resolvers
- pages, dashboards, forms
- request/response serialization

**ORM configuration:**
- the Kernel does not impose an ORM
- the Kernel defines repository **contracts** only
- ORM is an implementation detail
- schema is defined by PostgreSQL + event store requirements

## 1.4 Why "Kernel First" Matters

**The foundation determines everything that becomes possible, safe, or impossible later.**

### Bad foundations cause:

1. **Tenant bleeding** — because tenant scope was optional at start, it becomes impossible to retrofit
2. **Hidden mutation** — because aggregates had public setters initially, state integrity is now confused
3. **Audit gaps** — because traceability was added later, historical data is incomplete
4. **Temporal violations** — because posted period checks were "admin overrides," legal compliance becomes suspect
5. **Authority creep** — because roles were strings, complex delegation is now impossible
6. **Async corruption** — because outbox was added late, race conditions now exist in production
7. **Replay failure** — because events weren't versioned, old data cannot be deterministically replayed
8. **Concurrency chaos** — because versioning was optional, updates corrupt silently

### Correct foundations enable:

1. **Multi-tenancy as structure** — not a feature flag, but a grain of sand in every aggregate
2. **Audit as automatic** — not a special case, but the default for all state mutating
3. **Temporal compliance** — not a workaround, but a kernel law
4. **Idempotency as free** — because command handlers are structured correctly
5. **Deterministic replay** — because events are versioned and deterministic from the start
6. **Concurrency safety** — because generational versioning is baked in
7. **Authority clarity** — because delegation is explicitly modeled vs. implicit string roles
8. **Scaling safety** — because modules inherit the same guarantees, not invent their own

## 1.5 How Bad Foundations Corrupt ERP Modules

**Example: The missing tenant_id**

If the Kernel allows aggregates to exist without explicit TenantId ownership:

1. A Finance module creates `Invoice` with optional tenant_id
2. Inventory module copies the pattern
3. Months later: a query in Dashboard forgets the tenant filter
4. A tenant's data leaks to another tenant
5. Legal liability, data breach notification, audit failure

**Correct: the Kernel forbids it**

```csharp
public abstract class AggregateRoot<TId>
{
    public TenantId TenantId { get; }  // IMMUTABLE, MANDATORY

    protected AggregateRoot(TId id, TenantId tenantId)
    {
        // No way to construct without TenantId
    }
}
```

Every module inherits this guarantee. The leak is architecturally impossible.

**Example: Missing event versioning**

If events are not versioned from day 1:

1. Year 1: `UserCreated` event has fields: name, email
2. Year 3: Product needs to add phone to user
3. Old events are replayed without phone
4. Projections are now corrupt
5. Audit trail is unreconstructable

**Correct: the Kernel requires versioning**

```csharp
public abstract record DomainEvent(
    // ...
    int SchemaVersion = 1
);
```

Every event upgrade path is explicit. Modules inherit this discipline.

## 1.6 Core Principles All Future Modules Must Obey

### Principle 1: State Lives Behind Aggregates Only

No module may mutate business state except through aggregate root methods.

No setters. No direct SQL updates. No ORM mass-update operations.

State changes are:
- intentful
- versioned
- traced
- auditable

### Principle 2: Identity is Immutable and Multi-Faceted

Every aggregate has:
- `TenantId` (immutable, mandatory) — isolation boundary
- `AggregateId` (immutable, mandatory) — identity within tenant
- `Version` (mutable internally) — concurrency token
- `EventId` when emitted (unique, immutable) — audit trace

### Principle 3: Events are Facts, Commands are Intentions

Events represent **what happened**. They are:
- immutable
- past-tense named
- causally linked
- audit-worthy

Commands represent **what was requested**. They are:
- validation-worthy
- authorization-worthy
- idempotency-worthy

### Principle 4: Authorization Belongs in Application, Not Domain

Domain aggregates do not perform authorization checks.

Authorization happens **before** the aggregate method is called.

```csharp
// WRONG
var invoice = await _invoiceRepo.GetAsync(invoiceId);
if (!_authService.CanApprove(actor, invoice)) throw new Exception();
invoice.Approve(actor);

// CORRECT
await _authService.AuthorizeAsync(new ApproveInvoiceRequirement(invoiceId), actor);
var invoice = await _invoiceRepo.GetAsync(invoiceId);
invoice.Approve(actor);  // Authorization already verified
```

### Principle 5: Temporal Legality is Kernel-Enforced, Not Delegated

No module may decide whether a period is closed.

The Kernel defines:
- business calendar
- posting legality
- override policy

Modules consume it:

```csharp
if (!await _calendar.CanPostAsync(businessDate, tenantId))
    throw new ClosedPeriodViolationException();
```

### Principle 6: Audit is Automatic, Not Opt-In

Every command that mutates state results in an audit entry.

This is handled by the command pipeline, not by individual aggregates.

Modules inherit this for free.

### Principle 7: Idempotency is Built-In, Not Bolted On

Every mutating command handler supports idempotency keys.

If the same command is retried with the same key, the result is replayed, not re-executed.

This is a Kernel guarantee, enforced by the pipeline.

### Principle 8: Tenant Scope is Ambient at Infrastructure Edge, Explicit in Domain

Middleware / interceptors may resolve tenant from HTTP context, JWT claims, or queue metadata.

But once execution enters the domain, tenant ownership is explicit:

```csharp
var tenant = _ambientContext.ResolveTenant();
var aggregate = await _repo.GetAsync(id, tenant);  // tenant is explicit
```

No silent cross-tenant reads.

### Principle 9: Events are Versioned, Deterministic, Replayable

Every event type has a schema version.

Events are serialized deterministically (sorted keys, no random ordering).

Projections are rebuildable because replay is deterministic.

This is a Kernel contract all modules inherit.

### Principle 10: Concurrency is Optimistic, Versioned, Non-Silent

No pessimistic locks.

All aggregates have a `Version` field.

Updates fail with `ConcurrencyConflictException` if version mismatches.

Callers must handle retries. No silent overwrites.

---

## Summary of Doctrine

**The EPSILONE Kernel is the governance substrate that:**
- makes tenant isolation structural, not optional
- makes audit trail automatic, not special
- makes idempotency free, not expensive
- makes temporal compliance non-negotiable
- makes concurrency safe, not scary
- makes authority explicit, not implicit
- makes events reproducible, not mysterious
- makes state integrity verifiable, not hoped-for

Every bounded context that consumes the Kernel inherits these guarantees without re-implementing them.

This is how ERP systems become trustworthy at scale.

Correct. That is the **architectural thesis** of the Kernel.

Now sharpen it into a **non-poetic governing doctrine**, so it can drive implementation instead of just sounding right.

Use this as the **Kernel Doctrine / Charter**.

---

## EPSILONE Kernel Doctrine

The **EPSILONE Kernel** is not a utility library, framework wrapper, or shared helpers package.

It is the **governing execution substrate** that defines the non-negotiable operational laws under which every bounded context must exist.

Its purpose is not to provide convenience.

Its purpose is to provide **structural correctness**.

A module built outside these rules is not “another implementation style.”
It is **architecturally invalid**.

---

## What the Kernel Exists to Guarantee

The Kernel exists to make critical enterprise properties **systemic** instead of optional.

### 1. Tenant Isolation Is Structural, Not Conventional

No domain object, event, command, repository operation, or persistence boundary may exist without tenant ownership being explicit or enforced.

This prevents:

* cross-tenant data bleed
* accidental shared-state corruption
* ambiguous authority boundaries
* unsafe replay and unsafe async execution

Tenant isolation is not a repository filter.
It is a **system law**.

---

### 2. Auditability Is Native, Not Added Later

The system must produce a defensible, attributable history of:

* who acted
* under what authority
* against what aggregate or process
* at what business and system time
* with what outcome

Audit is not a logging concern.
It is a **governance concern**.

If an action cannot be reconstructed, it should be treated as **untrustworthy**.

---

### 3. Idempotency Must Be Cheap by Default

Distributed ERP workflows will always experience:

* retries
* duplicate delivery
* timeout ambiguity
* replay
* partial failure

Therefore, the Kernel must make idempotent execution the **default operational path**, not a per-feature burden.

Anything that requires every feature team to “remember” idempotency will fail.

---

### 4. Temporal Legality Is a First-Class Constraint

ERP systems are not merely stateful systems.
They are **time-governed systems**.

The Kernel must distinguish:

* system time
* business time
* effective time
* posting period legality
* retroactivity legality
* closure legality

Without this, modules become operationally functional but **financially and legally invalid**.

---

### 5. Concurrency Must Be Safe by Construction

ERP systems involve simultaneous mutation of:

* stock
* balances
* approvals
* identities
* entitlements
* commitments

The Kernel must therefore make concurrency safety a structural property through:

* versioning
* expected-version checks
* append-only event streams
* deterministic conflict semantics
* transaction boundary discipline

If concurrency safety is left to “careful coding,” corruption is guaranteed.

---

### 6. Authority Must Be Explicit and Traceable

No state transition should be accepted unless the system can answer:

* who is attempting this
* under what role or delegated authority
* against what tenant
* with what policy justification

Authority must never be inferred from convenience-layer assumptions.

It must be **represented, validated, and attributable**.

---

### 7. Events Must Be Reproducible and Deterministic

If the same event history cannot reproduce the same state, the system is not reliable.

Therefore the Kernel must enforce:

* canonical event serialization
* immutable event metadata
* replay-safe aggregate reconstruction
* deterministic projection behavior
* upgrade-safe schema evolution

Event history is not “just integration.”
It is the **recoverable memory of the enterprise**.

---

### 8. Integrity Must Be Verifiable, Not Assumed

The system must not rely on “probably correct.”

It must support direct verification of:

* event ordering
* aggregate version consistency
* replay determinism
* projection consistency
* idempotency correctness
* authority attribution
* temporal legality

Trust in ERP is not a UX property.

It is a **verification property**.

---

## What the Kernel Owns

The Kernel owns only the things that must be **uniform across all bounded contexts**.

That includes:

### Domain base abstractions

* `AggregateRoot`
* `Entity`
* `ValueObject`
* `DomainEvent`

### Identity and traceability primitives

* `TenantId`
* `UserId`
* `ActorId`
* `EventId`
* `CorrelationId`
* `CausationId`

### Temporal primitives

* `BusinessDate`
* `Timestamp`
* `EffectiveDateRange`
* `BusinessPeriod`

### Financial and quantity primitives

* `Money`
* `Quantity`
* `Rate`
* `Percentage`
* `CurrencyCode`
* `UnitOfMeasure`

### Boundary contracts

* commands
* queries
* handlers
* validators
* result/error model

### Persistence abstractions

* repository contracts
* unit of work
* event store
* snapshot store
* outbox/inbox
* idempotency store

### Governance abstractions

* security context
* authorization contracts
* approval primitives
* workflow/lifecycle primitives
* business calendar contracts

### Diagnostic and trust infrastructure

* audit contracts
* telemetry contracts
* replay verification contracts
* deterministic serialization contracts

If a concern must behave identically across modules, it belongs in the Kernel.

If not, it probably does not.

---

## What the Kernel Must Never Own

The Kernel must not become a dumping ground.

It must never own:

* finance-specific rules
* HR-specific policies
* inventory-specific stock logic
* procurement workflows
* invoice numbering semantics
* payroll calculations
* customer lifecycle rules
* UI or API formatting
* transport concerns
* controller logic
* module-specific projections
* ORM-driven schema shortcuts

If the logic is not universally foundational, it does **not** belong in the Kernel.

A fat kernel is just a distributed monolith in denial.

---

## Kernel Design Law: No Feature-Led Foundation

The Kernel must never be shaped by the first module implemented.

This is one of the most common ERP design failures.

Example corruption patterns:

* “Inventory needed quantity first, so now quantity is inventory-shaped”
* “Finance needed money first, so money now assumes ledger semantics”
* “HR needed approval first, so approval now assumes manager hierarchy”
* “The first API needed pagination, so repository abstractions now leak query transport concerns”

That is architectural contamination.

Kernel primitives must be designed as **domain-independent laws**, not extracted from the first feature that happened to need them.

---

## Kernel Design Law: No Framework-Led Domain

Frameworks must adapt to the Kernel.

The Kernel must not adapt to framework convenience.

Therefore:

* Spiral container lifecycle must adapt to tenant and correlation context
* persistence implementations must adapt to aggregate contracts
* event store infrastructure must adapt to canonical event semantics
* transport adapters must adapt to command/query contracts

Never let:

* ORM shape aggregates
* controller ergonomics shape contracts
* queue adapters shape event semantics
* request lifecycle shape domain authority

Framework-first architecture always becomes implementation-first architecture.
Implementation-first architecture always decays.

---

## Kernel Design Law: Every Mutation Must Be Explainable

Any state change in EPSILONE must be reconstructible as:

1. **an authorized intention**
2. **executed within a tenant**
3. **at a legal business time**
4. **against a valid prior version**
5. **producing immutable facts**
6. **resulting in verifiable state**

If any mutation cannot be explained through that chain, it is **architecturally suspect**.

That is the standard.

---

## Kernel Design Law: Every Future Module Must Inherit Safety

A future module should not have to “remember” how to be safe.

It should become safe by being built on the Kernel.

That means a bounded context should inherit by default:

* tenant isolation
* audit attribution
* event metadata
* replayability
* idempotency
* concurrency protection
* authority discipline
* temporal legality hooks
* diagnostics and tracing

If teams must rebuild these from scratch in each module, then the Kernel has failed.

---

## Final Kernel Definition

**The EPSILONE Kernel is the mandatory architectural substrate that converts enterprise correctness from developer discipline into system law.**

It exists so that every future bounded context operates under the same guarantees of:

* isolation
* authority
* traceability
* legality
* reproducibility
* consistency
* verifiability

Without it, you do not have an ERP platform.

You have a collection of features pretending to be one.

---

## Correct Architectural Consequence

This doctrine means your build order must begin with:

1. **identity and value primitives**
2. **error/result semantics**
3. **event and aggregate laws**
4. **tenant/authority/temporal contracts**
5. **repository and unit-of-work contracts**
6. **event store and outbox contracts**
7. **application boundary contracts**
8. **audit/observability/replay contracts**
9. **Spiral bootloaders and infrastructure bindings**

Anything else is premature.

Anything feature-first is contamination.

Anything transport-first is drift.

Anything ORM-first is collapse.


---

# SECTION 2 — CANONICAL PACKAGE DIRECTORY STRUCTURE

## 2.1 Corrected EPSILONE Kernel Physical Layout

```text
/packages/kernel/
├── src/
│   ├── Domain/
│   │   ├── Shared/
│   │   │   ├── Aggregate/
│   │   │   ├── Entity/
│   │   │   ├── ValueObject/
│   │   │   ├── Event/
│   │   │   ├── Result/
│   │   │   ├── Error/
│   │   │   ├── Specification/
│   │   │   └── Contract/
│   │   │
│   │   ├── Identity/
│   │   ├── Tenancy/
│   │   ├── Authorization/
│   │   ├── Temporal/
│   │   ├── Workflow/
│   │   ├── Approval/
│   │   ├── DocumentIdentity/
│   │   ├── Audit/
│   │   ├── Observability/
│   │   └── Serialization/
│   │
│   ├── Application/
│   │   ├── Contract/
│   │   │   ├── Command/
│   │   │   ├── Query/
│   │   │   ├── Handler/
│   │   │   ├── Validation/
│   │   │   ├── Authorization/
│   │   │   ├── Idempotency/
│   │   │   ├── Transaction/
│   │   │   └── Bus/
│   │   │
│   │   ├── Behavior/
│   │   │   ├── Validation/
│   │   │   ├── Authorization/
│   │   │   ├── Idempotency/
│   │   │   ├── Transaction/
│   │   │   ├── Audit/
│   │   │   └── Telemetry/
│   │   │
│   │   ├── Policy/
│   │   ├── Saga/
│   │   └── Service/
│   │
│   ├── Infrastructure/
│   │   ├── Contract/
│   │   │   ├── Persistence/
│   │   │   ├── EventStore/
│   │   │   ├── Eventing/
│   │   │   ├── Security/
│   │   │   ├── Clock/
│   │   │   ├── Serialization/
│   │   │   ├── Audit/
│   │   │   ├── Observability/
│   │   │   └── Diagnostics/
│   │   │
│   │   ├── Persistence/
│   │   │   ├── Doctrine/
│   │   │   ├── EventStore/
│   │   │   ├── SnapshotStore/
│   │   │   ├── Repository/
│   │   │   ├── UnitOfWork/
│   │   │   ├── Projection/
│   │   │   └── Idempotency/
│   │   │
│   │   ├── Eventing/
│   │   │   ├── Dispatcher/
│   │   │   ├── Outbox/
│   │   │   ├── Inbox/
│   │   │   ├── Upgrader/
│   │   │   └── Router/
│   │   │
│   │   ├── Security/
│   │   │   ├── Context/
│   │   │   ├── Authorization/
│   │   │   └── TenantResolution/
│   │   │
│   │   ├── Serialization/
│   │   │   ├── Event/
│   │   │   ├── ValueObject/
│   │   │   └── CanonicalJson/
│   │   │
│   │   ├── Audit/
│   │   ├── Observability/
│   │   │   ├── Tracing/
│   │   │   ├── Metrics/
│   │   │   └── Logging/
│   │   │
│   │   ├── Clock/
│   │   └── Spiral/
│   │       ├── Bootloader/
│   │       ├── Interceptor/
│   │       ├── Middleware/
│   │       ├── Queue/
│   │       ├── Console/
│   │       └── Reset/
│   │
│   ├── Diagnostics/
│   │   ├── Replay/
│   │   ├── Verification/
│   │   ├── Compliance/
│   │   └── Projection/
│   │
│   ├── Support/
│   │   ├── Exception/
│   │   ├── Collection/
│   │   ├── Utility/
│   │   └── TestKit/
│   │
│   └── Kernel.php
│
├── tests/
│   ├── Unit/
│   │   ├── Domain/
│   │   ├── Application/
│   │   └── Infrastructure/
│   │
│   ├── Integration/
│   │   └── EventSourcing/
│   │
│   ├── Fixtures/
│   │   ├── EventStore/
│   │   └── Aggregates/
│   │
│   └── KernelTestCase.php
│
├── composer.json
├── phpstan.neon
├── phpunit.xml
├── rector.php
└── README.md
```

## 2.2 Namespace Mapping

```
Spiral\Kernel\Domain\Shared\Aggregate\*
Spiral\Kernel\Domain\Shared\Entity\*
Spiral\Kernel\Domain\Shared\ValueObject\*
Spiral\Kernel\Domain\Shared\Event\*
Spiral\Kernel\Domain\Shared\Result\*
Spiral\Kernel\Domain\Shared\Error\*
Spiral\Kernel\Domain\Identity\*
Spiral\Kernel\Domain\Tenancy\*
Spiral\Kernel\Domain\Authorization\*
Spiral\Kernel\Domain\Temporal\*
Spiral\Kernel\Domain\Workflow\*
Spiral\Kernel\Domain\Approval\*
Spiral\Kernel\Domain\DocumentIdentity\*
Spiral\Kernel\Domain\Audit\*
Spiral\Kernel\Domain\Observability\*

Spiral\Kernel\Application\Contract\*
Spiral\Kernel\Application\Behavior\*
Spiral\Kernel\Application\Policy\*
Spiral\Kernel\Application\Saga\*
Spiral\Kernel\Application\Service\*

Spiral\Kernel\Infrastructure\Contract\*
Spiral\Kernel\Infrastructure\Persistence\*
Spiral\Kernel\Infrastructure\Eventing\*
Spiral\Kernel\Infrastructure\Security\*
Spiral\Kernel\Infrastructure\Serialization\*
Spiral\Kernel\Infrastructure\Observability\*
Spiral\Kernel\Infrastructure\Clock\*
Spiral\Kernel\Infrastructure\Spiral\*

Spiral\Kernel\Diagnostics\*
Spiral\Kernel\Support\*
```

## 2.3 Correct Responsibility by Top-Level Folder

### `Domain/`

This is the **business-law-neutral truth layer**.

It defines:

* invariants
* domain primitives
* identity
* authority semantics
* event laws
* temporal legality primitives
* workflow legality primitives

**Forbidden in `Domain/`:**

* DB access
* HTTP
* queue code
* serializers tied to infra
* framework annotations
* ORM mappings
* logging
* metrics
* transport DTOs

If a file in `Domain/` imports Spiral, Doctrine DBAL, or RoadRunner-specific concerns, it is in the wrong place.

### `Application/`

This is the **orchestration layer**.

It defines:

* commands
* queries
* handlers
* policies
* sagas
* execution pipeline behaviors

**It answers:** "What happens when the system is asked to do something?"

**Forbidden in `Application/`:**

* persistence implementation
* direct SQL
* framework controller assumptions
* serialization mechanics
* transport response formatting

### `Infrastructure/`

This is the **implementation layer**.

It provides:

* event store persistence
* outbox/inbox implementation
* snapshot store
* repository implementations
* Spiral bootstrapping
* authorization adapters
* tenant resolution adapters
* tracing/logging/metrics adapters

**It answers:** "How is the contract fulfilled concretely?"

**Forbidden in `Infrastructure/`:**

* domain rules
* business invariants
* aggregate decision logic
* feature-specific workflow logic

### `Diagnostics/`

This folder must exist from the beginning because your kernel promises:

* determinism
* replayability
* integrity verification
* compliance validation

Without diagnostics, those are just slogans.

### `Support/`

This must stay **small and boring**.

Good candidates:

* generic exceptions
* small collections
* low-level utilities
* test helpers

**Forbidden in `Support/`:** Anything people are too lazy to classify. If a class has meaning, it belongs in Domain, Application, Infrastructure, or Diagnostics.

## 2.4 Correct First Physical Build Order

Do not create the entire tree and then wander. Create only what is needed **in dependency order**.

### Phase 1 — Skeleton only

Create:

```text
packages/kernel/
  src/
    Domain/
      Shared/
      Identity/
      Tenancy/
      Temporal/
    Support/
      Exception/
  tests/
    Unit/
  composer.json
  phpstan.neon
  phpunit.xml
  README.md
```

Nothing more yet. Reason: if you create the whole forest now, you will start filling random folders with premature abstractions.

### Phase 2 — Core primitives first

Then create only:

```text
Domain/Shared/ValueObject/
Domain/Shared/Result/
Domain/Shared/Error/
Domain/Shared/Event/
Domain/Shared/Aggregate/
Domain/Shared/Entity/
Domain/Identity/
Domain/Tenancy/
Domain/Temporal/
Support/Exception/
```

This is where the Kernel truly begins.

### Phase 3 — Boundary contracts

Then create:

```text
Application/Contract/
Infrastructure/Contract/
```

Only after domain primitives exist.

### Phase 4 — Persistence/eventing infrastructure

Then create:

```text
Infrastructure/Persistence/
Infrastructure/Eventing/
Infrastructure/Serialization/
Infrastructure/Clock/
```

### Phase 5 — Pipeline and governance

Then create:

```text
Application/Behavior/
Application/Policy/
Application/Saga/
Domain/Authorization/
Domain/Workflow/
Domain/Approval/
Domain/Audit/
```

### Phase 6 — Diagnostics and Spiral integration

Only then create:

```text
Diagnostics/
Infrastructure/Spiral/
Infrastructure/Observability/
Infrastructure/Audit/
```

This sequence prevents architectural drift.

## 2.5 Key Corrections from Original Layout

| Original | Corrected | Reason |
|----------|-----------|--------|
| `Domain/Organization/` | Removed | Too close to business modeling, belongs in `/packages/organization/` |
| `Domain/Events/` | `Domain/Shared/Event/` | Events are fundamental, not a generic domain folder |
| `Application/Commands/` | `Application/Contract/Command/` | Separate contracts from behaviors |
| `Application/Queries/` | `Application/Contract/Query/` | Separate contracts from behaviors |
| `Application/Handlers/` | `Application/Contract/Handler/` | Separate contracts from behaviors |
| `Infrastructure/Abstractions/` | `Infrastructure/Contract/` | More precise naming |
| `src/Bootloader/` | `Infrastructure/Spiral/Bootloader/` | Bootloaders are framework adapters |
| `Kernel.php` | Optional, non-authoritative | Must not become a service locator |

## 2.6 Final Verdict

Your layout should optimize for this rule:

> **A future bounded context must be able to depend on the Kernel without inheriting framework pollution, feature contamination, or persistence leakage.**

That is the real test. If you pass that, the Kernel is viable. If not, it is just a cleaner monolith.

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

**Deliverables:** `packages/kernel/`, `src/`, `tests/`, `composer.json`, `phpstan.neon`, `phpunit.xml`, `README.md`

**Why first:** You need namespace boundaries before code. Nothing can exist before physical structure.

---

## Phase 1 — Core Failure & Result Semantics

**Deliverables:** `KernelException`, `DomainException`, `ValidationException`, `ConcurrencyConflictException`, `AuthorizationException`, `BusinessRuleViolationException`, `NotFoundException`, `Result<TData, TError>`, `ErrorCode`, `ErrorDetail`

**Why first:** Before any object can be trusted, the kernel must define what is invalid, exceptional, a business failure, and a programming failure.

**Consequence:** This prevents ad hoc exception chaos later.

---

## Phase 2 — Fundamental Value Object Base + Identity/Traceability Primitives

**Deliverables:** `ValueObject`, `TenantId`, `UserId`, `ActorId`, `EventId`, `CorrelationId`, `CausationId`, `DocumentId`, `EmailAddress`, `TenantSlug`, `ResourceReference`

**Why first:** Everything else depends on trusted primitives.

**Consequence:** This kills primitive obsession before it spreads.

---

## Phase 3 — Temporal & Numeric Primitives

**Deliverables:** `Timestamp`, `BusinessDate`, `BusinessPeriod`, `EffectiveDateRange`, `Money`, `CurrencyCode`, `Quantity`, `UnitOfMeasure`, `Percentage`, `Rate`

**Why here:** These underpin compliance, posting legality, workflow timing, and financial safety.

**Consequence:** This prevents later module-specific corruption of time and arithmetic.

---

## Phase 4 — Event Law

**Deliverables:** `DomainEvent`, `EventMetadata`, `EventEnvelope`, `EventSchemaVersion`, `IEventUpgradable`

**Why here:** Aggregates cannot exist before the event contract exists.

**Consequence:** This ensures every future aggregate emits structurally valid facts.

---

## Phase 5 — Aggregate Law

**Deliverables:** `Entity<TId>`, `AggregateRoot<TId>`, `IHasDomainEvents`, aggregate versioning contract, `Raise()`, `When()`, `ClearEvents()`, `Rehydrate()/Reconstitute()`, lifecycle hooks

**Why here:** Only now can you define how state evolves.

**Consequence:** This is where the write model becomes real.

---

## Phase 6 — Domain Contracts (Pure Kernel Law Interfaces)

**Deliverables:**
- Persistence: `IRepository<TAggregate, TId>`, `ISpecification<T>`, `ISpecificationRepository<T>`, `IUnitOfWork`
- Security: `ISecurityContext`, `IAuthorizationService`, `IActionRequirement`
- Temporal: `IBusinessCalendar`, `IClock`
- Audit/Diagnostics: `IAuditTrail`, `IReplayVerifier`, `ITracer`, `IMetrics`, `ILogger`

**Why here:** Now that the domain laws exist, define what the rest of the system is allowed to depend on.

**Consequence:** This locks the kernel's dependency direction before infrastructure starts contaminating it.

---

## Phase 7 — Application Boundary Contracts

**Deliverables:** `ICommand<TResult>`, `IQuery<TResult>`, `ICommandHandler<TCommand, TResult>`, `IQueryHandler<TQuery, TResult>`, `IValidator<T>`, `ValidationResult`, `ICommandBus`, `IQueryBus`

**Why here:** Only after domain law and infrastructure-facing contracts exist should orchestration contracts be defined.

**Consequence:** This creates the stable service boundary for all future modules.

---

## Phase 8 — Delivery Safety Contracts

**Deliverables:** `IEventStore`, `ISnapshotStore`, `IEventSerializer`, `IOutboxStore`, `IProcessedMessageStore`, `IIdempotencyStore`

**Why here:** Once commands/events exist, you can now define safe execution semantics.

**Consequence:** This is where the kernel becomes operationally safe in distributed conditions.

---

## Phase 9 — Kernel-Owned Governance Models

**Deliverables:** `Tenant` or `TenantContextPolicy` model, `ApprovalRequest` (if universally required), `LifecycleState`, `TransitionRule`, `ApprovalDecision`, `RetentionPolicy`, `FeatureFlag`

**Important restriction:** Do **not** dump "interesting business concepts" here. Only universal governance objects belong.

**Consequence:** This prevents feature contamination inside the kernel.

---

## Phase 10 — Application Behaviors / Policies / Sagas

**Deliverables:** validation behavior, authorization behavior, idempotency behavior, transaction behavior, audit behavior, telemetry behavior, base `Policy`, base `Saga`

**Why here:** Now the kernel can define orchestration mechanics safely.

**Consequence:** This gives all future modules a safe execution pipeline.

---

## Phase 11 — Diagnostics Contracts and Verification Logic

**Deliverables:** replay verification contracts, projection hash verification model, compliance verification model, deterministic replay rules

**Why here:** Now that the execution model exists, define how correctness is verified.

**Consequence:** This is what turns "event-sourced" from a belief into a testable property.

---

## Phase 12 — PostgreSQL Infrastructure Implementations

**Deliverables:** PostgreSQL event store, snapshot store, repository implementations, UoW implementation, outbox implementation, inbox implementation, idempotency implementation, audit persistence, projection checkpoint persistence

**Why here:** Now you can finally implement storage without forcing storage concerns into the domain.

**Consequence:** This is where the kernel becomes physically runnable.

---

## Phase 13 — Spiral Integration / Bootloaders / Runtime Wiring

**Deliverables:** Spiral bootloaders, service bindings, RoadRunner-safe scoped context reset, queue consumer registration, console integration, middleware/interceptors for tenant resolution, correlation, security context, audit attribution

**Why here:** Framework wiring must bind already-defined contracts to already-existing implementations.

**Consequence:** This is where the kernel becomes executable inside Spiral without letting Spiral define it.

---

## Phase 14 — Diagnostics Implementations & Deterministic Test Harness

**Deliverables:** replay test harness, projection determinism tests, event hash verification tests, concurrency race tests, idempotency duplication tests, tenant leakage tests, temporal legality tests

**Why last:** Verification is only meaningful once the system exists concretely.

**Consequence:** This is where the kernel earns trust.

---

# Corrected Build Order Table

| Phase | What | Why First | Why Not Before |
| ----- | ----------------------------------------- | ----------------------------------------------------- | --------------------------------- |
| 0 | Package Skeleton | Physical boundary comes first | No code exists yet |
| 1 | Failure & Result Semantics | All validation and failure paths depend on this | Nothing to define earlier |
| 2 | Identity & Base VOs | Everything depends on trusted primitives | Needs failure semantics |
| 3 | Temporal & Numeric Primitives | Time and arithmetic laws must be universal | Needs VO base |
| 4 | Event Law | Aggregates cannot exist before events | Needs IDs and timestamps |
| 5 | Aggregate Law | State evolution depends on event law | Needs events and primitives |
| 6 | Domain Contracts | Clean dependency boundaries must exist before app/infra | Needs stable domain law |
| 7 | Application Contracts | Orchestration depends on domain shape | Needs domain + contracts |
| 8 | Delivery Safety Contracts | Outbox/inbox/idempotency depend on commands/events | Needs app + infra contracts |
| 9 | Kernel-Owned Governance Models | Only now can universal kernel models be created safely | Needs laws and contracts first |
| 10 | Behaviors / Policies / Sagas | Execution mechanics need all prior contracts | Too arbitrary earlier |
| 11 | Diagnostics Contracts | Verification model depends on execution semantics | Needs replayable architecture |
| 12 | PostgreSQL Implementations | Concrete persistence must come after contracts | Otherwise domain leaks to storage |
| 13 | Spiral Wiring / Bootloaders | Bindings require real implementations | Otherwise DI is empty |
| 14 | Diagnostic Implementations / Test Harness | Trust must be verified after system exists | Nothing to verify earlier |

---

# The Key Correction

The correct kernel sequence is:

> **law first → primitive first → contract first → model later → infrastructure later → framework last**

That is the right order. Anything else creates hidden authority drift and persistence leakage.

---

# The Rule to Keep in Your Head

When unsure what to build next, ask:

> **Does this define a law, a primitive, a contract, or an implementation?**

Then obey this precedence:

```text
Law → Primitive → Contract → Governance Model → Implementation → Framework Wiring → Verification
```

That is the build discipline that keeps the kernel clean.
