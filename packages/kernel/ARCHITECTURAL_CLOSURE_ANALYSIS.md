# EPSILON Kernel — Architectural Closure Analysis

**Date:** 2026-04-03
**Status:** Phase 1-2 Complete (Primitive Semantics), Phase 3 Is Mandatory For Closure
**Critical Insight:** Current phases produce *substrate*, not *runtime kernel*

---

## Executive Summary

**What We Have:** Semantic nucleus (exceptions, VOs, Result monad) — technically sound but structurally incomplete.

**What We're Missing:** Event-sourcing machinery that makes primitives *consumable by bounded contexts*.

**The Gap:** Phases 1-2 answer "what primitives?" but Phase 3 must answer "how do aggregates actually persist and reconstitute?"

**Build Order Correction:** UI/UX is premature. Phase 3 (DomainEvent + AggregateRoot) is architecturally mandatory before any application layer.

---

## The Corrected Architectural Relationship Map

### Strict Dependency Direction (Non-Negotiable)

```text
Application    Domain    Infrastructure
     ↓            ↑              ↑
     └────────────┘              │
              ↓                   │
     Application Logic     (must not depend on above)
              ↓
     Domain Contracts
              ↓
     Domain Core (THIS IS INVIOLABLE)
                                  │
                                  └─────────────────────┘
```

**The First Law of Kernel Architecture:**

```text
Domain Layer Dependency Direction
═════════════════════════════════════════════════════

Domain NEVER depends on:
  ✗ Application
  ✗ Infrastructure
  ✗ Framework (Laravel, Spiral, etc.)
  ✗ Database
  ✗ Transport Layer

Domain MAY depend on:
  ✓ PHP standard library
  ✓ PSR interfaces (PSR-3 Logger, PSR-11 Container)
  ✓ External primitives (Ramsey UUID, DateTimeImmutable)

Application MAY depend on:
  ✓ Domain
  ✗ Infrastructure (only via contracts, never implementation details)

Infrastructure MAY depend on:
  ✓ Domain
  ✓ Application (optional, for orchestration)
```

---

## What Phases 1-2 Actually Delivered

### Layer 1: Exception Model ✓
```
KernelException (abstract)
├─ DomainException (abstract)
│  ├─ ValidationException
│  ├─ BusinessRuleViolationException
│  └─ NotFoundException
├─ ConcurrencyConflictException
└─ AuthorizationException
```
**Status:** Semantically complete for this phase. ✓

### Layer 2: Primitive Value Objects ✓
```
ValueObject (abstract base)
├─ ErrorCode, ErrorDetail
├─ TenantId, UserId, ActorId, EventId, CorrelationId, CausationId, DocumentId
└─ TenantSlug, EmailAddress, ResourceReference
```
**Status:** Semantically complete for this phase. ✓

### Layer 3: Functional Semantics ✓
```
Result<T> monad
├─ Success<T>
└─ Failure(ErrorDetail)
```
**Status:** Semantically complete for this phase. ✓

---

## What's Missing: The Event-Sourcing Core

### Phase 3 Mandatory Components

These are not enhancements. They are structural prerequisites.

```text
┌─────────────────────────────────────────────────────────────┐
│              DOMAIN EVENT-SOURCING CORE (Phase 3)            │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  [A] DomainEvent (abstract base class)                      │
│      • Every domain event extends this                      │
│      • Carries: EventId, TenantId, metadata, occurred_at   │
│      • Immutable value object semantics                     │
│      • Provides: schemaVersion() for replay safety          │
│                                                             │
│  [B] AggregateRoot<TId> (abstract base class)              │
│      • All aggregates must extend this                      │
│      • Carries: id (TId), version, tenant_id               │
│      • Protected raise(DomainEvent) method                  │
│      • Protected reconstituteFromEvents(array) method      │
│      • Invariant enforcement before raising events         │
│                                                             │
│  [C] EventEnvelope (immutable VO)                           │
│      • Wraps DomainEvent for storage                        │
│      • Carries: aggregateId, streamName, version,          │
│        occurred_at, eventId, causationId, correlationId    │
│      • Used by: EventStore implementations                  │
│                                                             │
│  [D] RecordsEvents trait / EventRecorder interface          │
│      • Manages uncommitted events buffer                    │
│      • Provides: recordEvent(), getRecordedEvents()        │
│      • Mixable into AggregateRoot                          │
│                                                             │
│  [E] AppliesEvents mechanism / Reconstitution               │
│      • Replays events to reconstruct state                  │
│      • Deterministic: same event sequence = same state      │
│      • Time-series aware: EventId v7 ordered                │
│      • Concurrency-safe: Version field validation           │
└─────────────────────────────────────────────────────────────┘
```

### Phase 3 Domain Contracts

These remain contracts (interfaces) for now, implementations come in Phase 5.

```text
┌─────────────────────────────────────────────────────────────┐
│        DOMAIN CONTRACT SURFACE (Phase 3 Definition Only)     │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  [Contract 1] IEventStore<T>                               │
│    • append(EventEnvelope): void                           │
│    • getFromVersion(streamName, version): EventEnvelope[] │
│    • getCurrentVersion(streamName): int                    │
│    • (Implementation deferred to Phase 5: PostgreSQL)      │
│                                                             │
│  [Contract 2] IAggregateRepository<T, TId>                 │
│    • save(T aggregate): void                              │
│    • load(TId id): T                                       │
│    • (Implementation deferred to Phase 5: EventStore)      │
│                                                             │
│  [Contract 3] IClock                                       │
│    • now(): DateTimeInterface                              │
│    • (Implementation deferred to Phase 5: System)          │
│                                                             │
│  [Contract 4] IUuidGenerator                               │
│    • generate(): UuidInterface (v4)                        │
│    • generateEventId(): UuidInterface (v7)                 │
│    • (Implementation deferred to Phase 5: Ramsey)          │
│                                                             │
│  [Contract 5] IEventSerializer                             │
│    • serialize(DomainEvent): array                         │
│    • deserialize(array, className): DomainEvent            │
│    • (Implementation deferred to Phase 5: JSON+Reflection) │
└─────────────────────────────────────────────────────────────┘
```

---

## Corrected Macro Architecture

### Before Phase 3 (Current)

```text
EPSILON Kernel — INCOMPLETE
┌─────────────────────────────────────────────────────┐
│         DOMAIN PRIMITIVES                           │
├─────────────────────────────────────────────────────┤
│ • Exceptions (7 classes)                           │
│ • Value Objects (12 classes)                       │
│ • Result Monad                                      │
│ • ERROR STATE: Cannot yet make decisions            │
│ • ERROR STATE: Cannot yet store decisions           │
│ • ERROR STATE: Cannot yet replay history            │
└─────────────────────────────────────────────────────┘

MISSING: Aggregate machinery
MISSING: Event machinery
MISSING: Persistence contracts

Status: SUBSTRATE ONLY (not consumable by bounded contexts)
```

### After Phase 3 (Goal)

```text
EPSILON Kernel — ARCHITECTURALLY CLOSED
┌──────────────────────────────────────────────────────┐
│            DOMAIN EVENT-SOURCING CORE                │
├──────────────────────────────────────────────────────┤
│                                                      │
│  [DECISIONS] AggregateRoot + DomainEvent             │
│  ├─ Aggregates enforce invariants                    │
│  ├─ Emit immutable domain events                     │
│  └─ Maintain optimistic version field                │
│                                                      │
│  [PERSISTENCE] EventEnvelope + EventStore contract  │
│  ├─ Events recorded with metadata                    │
│  ├─ Version checks for concurrency                   │
│  └─ Audit trail immutable                            │
│                                                      │
│  [REPLAY] Reconstitution logic                       │
│  ├─ Deterministic replay from event history          │
│  ├─ Time-ordered via EventId v7                      │
│  └─ Causation chain via CausationId                  │
│                                                      │
│  [SEMANTICS] Result<T> for application responses     │
│  ├─ Explicit Success/Failure branches                │
│  ├─ No framework noise                               │
│  └─ Deterministic decision outcomes                  │
│                                                      │
└──────────────────────────────────────────────────────┘

                       ↓

┌──────────────────────────────────────────────────────┐
│         DOMAIN CONTRACT SURFACE                      │
├──────────────────────────────────────────────────────┤
│ • IEventStore<T> (append, getFromVersion, etc.)     │
│ • IAggregateRepository<T, TId> (save, load)         │
│ • IClock (now)                                       │
│ • IUuidGenerator (generate, generateEventId)        │
│ • IEventSerializer (serialize, deserialize)         │
└──────────────────────────────────────────────────────┘

                       ↓

┌──────────────────────────────────────────────────────┐
│      INFRASTRUCTURE IMPLEMENTATIONS (Phase 5+)       │
├──────────────────────────────────────────────────────┤
│ • PostgreSQL Event Store                             │
│ • Aggregate Repository implementation                │
│ • Event Serialization (JSON + reflection)            │
│ • System Clock                                       │
│ • Ramsey UUID wrapper                                │
└──────────────────────────────────────────────────────┘

Status: COMPLETE EVENT-SOURCING KERNEL ✓ (consumable by bounded contexts)
```

---

## The Five Core Relationships That Matter

### Relationship 1: Aggregate ↔ DomainEvent

**Pattern:**
```
Business Command
       ↓
Aggregate Method Invocation
       ↓
Invariant Check (throws BusinessRuleViolationException)
       ↓
raise(new OrderPlaced(...))
       ↓
Event Recorded in Uncommitted Buffer
```

**Example:** Placing an order
```php
class Order extends AggregateRoot {
    public function placeOrder(Money $amount, CustomerId $customerId): void {
        if ($amount->isNegative()) {
            throw new BusinessRuleViolationException(
                'INVALID_ORDER_AMOUNT',
                'Order amount must be positive'
            );
        }
        $this->raise(new OrderPlaced(
            $this->aggregateId,
            $customerId,
            $amount,
            $this->tenantId
        ));
    }

    protected function onOrderPlaced(OrderPlaced $event): void {
        $this->amount = $event->amount();
        $this->customerId = $event->customerId();
        $this->recordedAt = $event->occurredAt();
    }
}
```

### Relationship 2: Aggregate ↔ Version

**Pattern:**
```
load stream @ Version N
       ↓
attempt append with expectedVersion = N
       ↓
IF actualVersion ≠ N:
    throw ConcurrencyConflictException
ELSE:
    append event, increment version → N+1
```

**Why It Matters:**
- Prevents lost updates in optimistic locking
- Already have the `Version` VO ready
- ConcurrencyConflictException already defined
- Just needs to integrate into aggregate/repository

### Relationship 3: DomainEvent ↔ Metadata

**Pattern:**
```
Every DomainEvent MUST carry:
├─ correlationId (trace the entire request)
├─ causationId (what caused this event?)
├─ actorId (who/what triggered it?)
├─ tenantId (tenant isolation)
├─ occurredAt (monotonic timestamp)
└─ schemaVersion (for safe replay)
```

**Why It Matters:**
- Enables distributed tracing
- Establishes causality chain
- Supports audit logs
- Already have all these VOs (CorrelationId, CausationId, ActorId, etc.)

### Relationship 4: Aggregate ↔ StreamName

**Pattern:**
```
Every aggregate instance = one stream

StreamName deterministically maps aggregateId:

    product-{uuid}
    order-{uuid}
    warehouse-{uuid}
    customer-{uuid}
```

**Why It Matters:**
- Persistence identity
- Event stream is the "source of truth"
- Enables event store to check optimistic concurrency by stream
- Enables parallel aggregate processing

### Relationship 5: Application ↔ Result

**Pattern:**
```
Command Handler returns Result<TResponse>

Result::success(CommandResponse)
    OR
Result::failure(ErrorDetail)

NO exceptions escape from handler.
```

**Why It Matters:**
- Deterministic outcomes
- No framework noise (no Laravel response object leaking)
- Supports replay/retry logic
- Already have the Result monad fully implemented

---

## Phase 3 Implementation Map

### File Structure After Phase 3

```
src/Domain/
├── ... (existing Phases 1-2)
│
├── EventSourcing/
│   ├── DomainEvent.php (abstract base)
│   ├── AggregateRoot.php (abstract base, generic <TId>)
│   ├── EventRecorder.php (trait for uncommitted buffer)
│   └── EventEnvelope.php (immutable storage envelope)
│
└── Contract/
    ├── IEventStore.php (interface, no implementation yet)
    ├── IAggregateRepository.php (interface)
    ├── IClock.php (interface)
    ├── IUuidGenerator.php (interface)
    └── IEventSerializer.php (interface)
```

### Input from Phase 1-2 That Phase 3 Uses

```
AggregateRoot will use:
  ✓ TenantId (structural boundary)
  ✓ EventId (event identification)
  ✓ Version (optimistic concurrency)
  ✓ Result monad (command handler response)
  ✓ DomainException (invariant violations)
  ✓ ConcurrencyConflictException (version conflict)

DomainEvent will use:
  ✓ EventId (UUID v7 for time-ordering)
  ✓ CorrelationId (request tracing)
  ✓ CausationId (causation chain)
  ✓ ActorId (who triggered it)
  ✓ TenantId (tenant isolation)
  ✓ Timestamp (occurred_at)
  ✓ EventName (event type identifier)
  ✓ Metadata (key-value context)

EventEnvelope will use:
  ✓ All of DomainEvent
  ✓ Plus: streamName, version, sequence
```

---

## Why UI/UX Now Would Be Backwards

### The Trap

Building UI before Phase 3 commits you to:

1. UI layer makes assumptions about aggregate behavior
2. Aggregate layer hasn't proven it can persist/replay
3. You end up designing screens for unproven domain decisions
4. When persistence adds constraints, UI becomes wrong
5. Architectural debt accumulates

### Costly Examples

**Bad Flow:**
```
UI assumes: "Order has a Price field I can edit"
       ↓
Controller maps: POST /orders/{id} → Order.setPrice()
       ↓
Aggregate built with: public setPrice(Money) method
       ↓
Later discovery: Prices can only change via OrderPriceAdjusted events
       ↓
UI now wrong, controller now wrong, aggregate now wrong
       ↓
Complete architectural rework required
```

**Good Flow:**
```
Phase 3: Define OrderPriceAdjusted event as only path to price changes
       ↓
Phase 4: EventStore handles event replay with concurrency
       ↓
Phase 5: Application layer orchestrates price adjustment as saga
       ↓
Phase 6: Only NOW do you know what UI should show
```

---

## Semantic Tests Required (Not UI Tests)

### Phase 3 Test Coverage

Tests should verify **kernel closure**, not UI behavior.

```php
// Test 1: Aggregates record events
class AggregateRootTest extends KernelTestCase {
    public function test_raise_records_event_in_uncommitted_buffer(): void {
        $order = Order::create($orderId, $customerId, $amount, $tenantId);
        $order->placeOrder();

        $recorded = $order->getRecordedEvents();

        $this->assertCount(1, $recorded);
        $this->assertInstanceOf(OrderPlaced::class, $recorded[0]);
    }
}

// Test 2: Aggregates apply events for replay
class ReconstitutionTest extends KernelTestCase {
    public function test_aggregate_reconstitutes_from_event_history(): void {
        $events = [
            new OrderPlaced($orderId, $customerId, $amount, $tenantId),
            new OrderValidated($orderId, $tenantId),
        ];

        $order = Order::fromEventHistory($events);

        $this->assertTrue($order->isPlaced());
        $this->assertTrue($order->isValidated());
        $this->assertEquals($order->customerId(), $customerId);
    }
}

// Test 3: Concurrency is enforced
class ConcurrencyTest extends KernelTestCase {
    public function test_version_mismatch_throws_concurrency_exception(): void {
        $order = $this->loadOrder($orderId);
        // Simulate: another process incrementedversion to 5
        $order->version = 3; // expected=3
        // Actual in DB = 5

        $this->expectException(ConcurrencyConflictException::class);
        $order->placeLineItem(...);
    }
}

// Test 4: Causation chain is maintained
class MetadataTest extends KernelTestCase {
    public function test_event_carries_correlation_and_causation(): void {
        $correlationId = CorrelationId::generate();
        $causation = CausationId::generate();

        $order = Order::create(...);
        $order->placeOrder($correlationId, $causation);

        $event = $order->getRecordedEvents()[0];

        $this->assertEquals($correlationId, $event->correlationId());
        $this->assertEquals($causation, $event->causationId());
    }
}
```

---

## What Correct Completion Looks Like

### Phases 1-2: ✓ Complete
- Primitive semantics stable
- All exceptions properly scoped
- All VOs immutable and self-validating
- Result monad functional
- **Verdict:** Ready to build on

### Phase 3: Mandatory
- Aggregates can be created and mutated
- Events are recorded in memory
- Reconstitution works deterministically
- Concurrency version field enforced
- EventEnvelope structure ready
- Contracts defined (not yet implemented)
- **Verdict:** Kernel is now *architecturally closed*

### Phase 4: Optional Enhancement
- EventStore contract implemented in PostgreSQL
- Repository layer built
- Outbox pattern optional
- Projections optional
- **Verdict:** Kernel becomes *production-deployable*

### Phase 5+: Bounded Context Verticles
- Only now do you write business domains
- Only now do you design APIs
- Only now do you build UI

---

## The Bottom Line

**What you have now:** Excellent primitive foundation. ✓

**What you're missing:** The fabric that makes primitives *work together*. ✗

**What happens if you skip Phase 3:** You have beautiful primitives but no way for bounded contexts to use them.

**What Phase 3 delivers:** A complete, self-contained, event-sourcing kernel that any bounded context can extend.

**Why this matters:** DDD + event-sourcing is not a nice-to-have pattern; it is the structural guarantee that your ERP can scale horizontally, replay partial history, and coordinate across tenants safely.

---

## Correct Next Step

**Not:** UI prototype
**Not:** Database schema
**Not:** API endpoints

**Is:** Phase 3 implementation following this sequence:

1. `DomainEvent` (abstract base class)
2. `AggregateRoot<TId>` (abstract base class with generic aggregate ID)
3. `EventRecorder` (trait for uncommitted events)
4. `EventEnvelope` (immutable storage wrapper)
5. Integrate all five relationships above
6. Write reconstitution + replay logic
7. Define contract interfaces
8. Write comprehensive semantic tests

Then you have a real kernel.

---

**Status:** Ready to proceed with Phase 3 Event-Sourcing Core.
**Build Confidence:** High. Primitives are rock-solid; Phase 3 integrates them naturally.
**Estimated Scope:** 8-12 classes, 200-300 lines per class, ~2,500-3,000 SLOC Phase 3 total.
