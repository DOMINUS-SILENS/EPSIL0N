# Phase 2.5 Runtime Spine — Planning Summary

**Date:** 2026-04-07
**Status:** PLANNED (ready for execution)
**Complexity:** High (type system fixes + event hierarchy unification + integration testing)
**Estimated Duration:** 2-3 hours Claude execution time

## Executive Summary

Phase 2.5 transforms the EPSILON kernel from **semantic substrate** (primitives with no execution) to **working event-sourcing runtime** (aggregates that actually persist and reload).

Current state:
- 341 PHPStan errors (type safety broken)
- 12 test failures (runtime type errors)
- 256 test skips (blocked by event interface issues)
- No integration tests running (persistence layer untested)

Target state:
- 0 PHPStan errors (level 9 compliance)
- All 268 tests passing
- Full Load → Mutate → Save → Reload cycle proven
- Optimistic concurrency + tenant isolation verified end-to-end

## The Core Problem

Two incompatible event hierarchies cause cascading failures:

### Simple Domain Events (Tests)
```php
class CustomerCreated {
    public function __construct(
        public readonly string $aggregate_id,
        public readonly string $name,
    ) {}
}
```
- Minimal, test-focused
- Do NOT implement DomainEvent interface
- Pass around in tests, applied to aggregates

### Event-Sourced Events (Persistence)
```php
class CustomerRegistered implements DomainEvent {
    private EventId $eventId;
    private TenantId $tenantId;
    // ... full DomainEvent interface
}
```
- Full metadata: EventId, TenantId, CorrelationId, CausationId, timestamp, schemaVersion
- Implement DomainEvent interface
- Persist to PostgreSQL, replay from storage

### The Failure Point
```php
// In AggregateRoot.php
protected function raise(DomainEvent $event): void { ... }

// In Customer.php apply() method
match (true) {
    $event instanceof CustomerCreated => ...,          // FAILS: not DomainEvent
    $event instanceof CustomerRegistered => ...,       // OK: implements DomainEvent
}
```

Result: Simple events fail instanceof checks → runtime TypeError → tests fail → cascade of failures across all test suites.

## The Solution: DomainEventContract Base Class

Create an abstract base class that **both event types inherit from**:

```
DomainEventContract (new abstract base, implements DomainEvent)
  ├── Simple events (CustomerCreated, CustomerRenamed)
  └── Event-sourced events (CustomerRegistered, CustomerEmailVerified, etc.)
```

Why base class (not interface or trait):
1. **instanceof checks work** — `$event instanceof DomainEvent` returns true for all events
2. **Default implementations** — getClassName(), getEventType(), getSyncMetadata() have sensible defaults
3. **Backward compatible** — Existing event implementations continue working unchanged
4. **Type safety** — AggregateRoot::apply(DomainEvent) now accepts all events

Why NOT other approaches:
- **Interface only**: instanceof checks would fail for events that only implement the interface
- **Trait**: Can't enforce that events implement DomainEvent
- **Union types**: Would lose type safety and IDE support

## Task Breakdown (11 Tasks, ~2-3 hours)

### Phase 1: Type Safety Fixes (Tasks 1-2)
- Fix EventMetadata/SyncMetadata array type specs (Task 1)
- Create DomainEventContract base class (Task 2)

**Outcome:** Foundation for event hierarchy unification.

### Phase 2: Event Hierarchy Unification (Task 3)
- Make simple events inherit from DomainEventContract
- Add required abstract method implementations (metadata getters)

**Outcome:** All events pass isinstance DomainEvent checks.

### Phase 3: Template Covariance (Task 4)
- Fix Result<TData> template to be covariant
- Resolve PHPStan generic type errors

**Outcome:** Zero template-related PHPStan errors.

### Phase 4: Type Safety Verification (Task 5)
- Confirm AggregateRoot::apply() now accepts all event types
- Verify isinstance checks in Customer.php no longer error

**Outcome:** Type system is consistent end-to-end.

### Phase 5: Test Suite (Tasks 6-7)
- Run full test suite, fix remaining failures
- Run PHPStan level 9 analysis
- Achieve 0 errors in both

**Outcome:** Kernel is type-safe and behaviorally correct.

### Phase 6: Acceptance Criteria Verification (Tasks 8-11)
- Load → Mutate → Save → Reload cycle
- Optimistic concurrency enforcement
- Tenant isolation
- Event replay determinism

**Outcome:** Phase 2.5 Runtime Spine is COMPLETE.

## Key Design Decisions

| Decision | Rationale | Impact |
|----------|-----------|--------|
| **DomainEventContract as base class** | Allows instanceof checks to work uniformly | All events are now valid DomainEvent |
| **Simple events inherit DomainEventContract** | Minimal changes, maximum compatibility | Test events now have real event metadata |
| **Keep AggregateRoot::apply(DomainEvent)** | No behavior change, maximum backward compatibility | Existing code continues working |
| **Make Result<TData> covariant** | Standard PHP generic pattern | Matches PHPStan expectations |
| **Write integration tests** | Proof that runtime actually works | Acceptance criteria verified end-to-end |

## Risk Mitigation

| Risk | Mitigation |
|------|-----------|
| DomainEventContract breaks existing events | Backward compatible: inherit from it, don't require implementation changes |
| Simple events need metadata but don't have sources | Generate metadata on-the-fly (EventId::generate(), new DateTimeImmutable()) |
| Covariance fix breaks other code | Only affects Result monad, which is isolated and tested |
| Integration tests fail due to database setup | Check phpunit.xml, ensure test database exists, run migrations |
| PHPStan still has errors after fixes | Likely in array access or return types, fixable with @var assertions and @return docblocks |

## Success Metrics

After Phase 2.5 completion:

1. **Type Safety**: `./vendor/bin/phpstan analyse` = 0 errors
2. **Test Coverage**: `./vendor/bin/phpunit` = all passing
3. **Integration**: Customer aggregate provably persists and reloads
4. **Concurrency**: Optimistic locking prevents lost updates
5. **Isolation**: TenantId structurally enforces multi-tenancy
6. **Reliability**: Events replay identically every time

The kernel transitions from **semantic substrate** to **production-grade event-sourcing runtime**.

## Next Phase: Phase 5 (Projection Infrastructure)

After Phase 2.5 completes:
- The kernel can write events and load aggregates ✓
- The kernel cannot yet query state efficiently ✗
- Phase 5 will add: ProjectionEngine, ReadModels, CQRS bus

Path: Write Side (Phase 2.5) → Read Side (Phase 5) → Operational Maturity (Phase 7-8)

---

**Plan created:** 2026-04-07 by Claude Planner
**Location:** `.planning/phases/2.5-runtime-spine/02.5-PLAN.md`
**Ready for execution:** Yes
