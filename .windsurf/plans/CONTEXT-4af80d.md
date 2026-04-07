# CONTEXT.md — Phase 2.5 Runtime Spine

**Purpose:** Lock in technical decisions for the critical transition from semantic substrate to functional ERP kernel.

**Authority:** Derived from KERNEL_FOUNDATION_BLUEPRINT (Parts 1-5) and existing codebase analysis.

---

## 1. AggregateRoot<TId> Implementation

### Decision: Generic Type Parameter

The Blueprint specifies `AggregateRoot<TId>` but the current implementation uses `string $id`. 

**Decision:** Keep `string $id` for simplicity. PHP generics are docblock-only. The type safety benefit is minimal vs. complexity.

### Current Implementation (Verified Working)

```php
abstract class AggregateRoot
{
    private string $id;
    private readonly TenantId $tenantId;
    private int $version = 0;
    private int $streamVersion = 0;
    private array $uncommittedEvents = [];
    
    protected function raise(object $event): void { ... }
    abstract protected function apply(object $event): void;
    public function reconstituteFromEvents(array $events, int $streamVersion = 0): void { ... }
    public function popUncommittedEvents(): array { ... }
    public function markAsNew(): void { ... }
    public function markCommitted(int $streamVersion): void { ... }
}
```

**Critical Fix Required:** `apply(object $event)` — NOT `apply(DomainEvent $event)`. This is the blocking bug.

### Invariants Enforced

- **TenantId is mandatory** — Constructor requires it
- **Version tracking** — Both `version` (including uncommitted) and `streamVersion` (committed only)
- **Event sourcing** — All mutations via `raise()`, replay via `reconstituteFromEvents()`

---

## 2. IEventStore Contract

### Canonical Contract (from Blueprint Part 3, Section 6.3)

```php
interface IEventStore
{
    public function append(
        TenantId $tenantId,
        string $streamId,
        ExpectedVersion $expectedVersion,
        DomainEvent ...$events
    ): int;

    public function load(
        TenantId $tenantId,
        string $streamId,
        int $fromVersion = 0,
        ?int $maxCount = null
    ): array;

    public function getStreamVersion(TenantId $tenantId, string $streamId): int;
    public function streamExists(TenantId $tenantId, string $streamId): bool;
    public function deleteStream(TenantId $tenantId, string $streamId): void;
}
```

### ExpectedVersion Modes

| Mode | Behavior |
|------|----------|
| `noStream()` | Append only if stream doesn't exist (new aggregate) |
| `exact(int)` | Append only if stream is at exact version |
| `any()` | Append regardless of current version (unsafe, for migrations) |

### Current Implementation Status

- `PostgreSqlEventStore` exists and implements `IEventStore`
- Uses `event_store` table (single table, not Blueprint's dual-table design)
- **Schema mismatch identified** — see Section 3

---

## 3. PostgreSQL Storage Schema

### Blueprint Canonical Schema (Part 3, Section 6.6)

```sql
-- Stream metadata
CREATE TABLE domain_streams (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    stream_id VARCHAR(255) NOT NULL,
    version BIGINT NOT NULL DEFAULT 0,
    aggregate_type VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE(tenant_id, stream_id)
);

-- Events
CREATE TABLE domain_events (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    stream_id VARCHAR(255) NOT NULL,
    event_id UUID NOT NULL UNIQUE,
    aggregate_id VARCHAR(255) NOT NULL,
    correlation_id UUID NOT NULL,
    causation_id VARCHAR(255),
    event_type VARCHAR(255) NOT NULL,
    event_payload JSONB NOT NULL,
    schema_version INT NOT NULL DEFAULT 1,
    occurred_at TIMESTAMP NOT NULL,
    stored_at TIMESTAMP NOT NULL DEFAULT NOW(),
    sequence_number BIGSERIAL NOT NULL UNIQUE,
    FOREIGN KEY(tenant_id, stream_id) REFERENCES domain_streams(tenant_id, stream_id)
);
```

### Current Implementation Schema

```sql
-- Single table (from tests)
CREATE TABLE event_store (
    id BIGSERIAL PRIMARY KEY,
    event_id VARCHAR(36) NOT NULL,
    tenant_id VARCHAR(36) NOT NULL,
    stream_id VARCHAR(255) NOT NULL,
    stream_version INTEGER NOT NULL,
    event_type VARCHAR(255) NOT NULL,
    event_class_name VARCHAR(512) NOT NULL,
    payload JSONB NOT NULL,
    metadata JSONB NOT NULL,
    occurred_at TIMESTAMPTZ NOT NULL,
    UNIQUE (stream_id, stream_version)
);
```

### Decision: Align to Current Implementation

**Rationale:**
1. Current schema is simpler (single table, no FK dependencies)
2. Tests already use this schema
3. Migration to dual-table can happen later if needed
4. The `stream_id` + `stream_version` unique constraint provides concurrency safety

**Required Fix:** Update migration file `001_create_event_store.sql` to match the actual implementation.

---

## 4. Runtime Tenant Enforcement

### TenantIsolationViolationException (Already Exists)

```php
final class TenantIsolationViolationException extends DomainException
{
    public function __construct(
        private readonly TenantId $requestedTenantId,
        private readonly TenantId $actualTenantId,
        private readonly string $operation,
        private readonly ?string $resourceId = null,
        ?\Throwable $previous = null,
    ) { ... }
}
```

### Enforcement Points

| Layer | Enforcement |
|-------|-------------|
| EventStore | All queries filtered by `tenant_id` |
| Repository | `load()` requires `TenantId`, validates match |
| Aggregate | `TenantId` is immutable property |

### Decision: No Ambient Tenant Context

Per Kernel Doctrine Section 1.6 Principle 8:
> "Tenant Scope is Ambient at Infrastructure Edge, Explicit in Domain"

**Implementation:**
- HTTP middleware may resolve tenant from JWT/headers
- Tenant is passed explicitly to all repository/event store methods
- No global/static `TenantContext::current()`

---

## 5. Event Serialization

### Decision: Canonical JSON

Events must serialize deterministically:
- Keys sorted alphabetically
- No runtime-specific data (e.g., class references)
- All dates as ISO 8601 UTC

### Current Implementation

`EventSerializer` class exists at `src/Infrastructure/Persistence/EventStore/EventSerializer.php`

---

## 6. Concurrency Model

### Optimistic Concurrency Control

| Component | Role |
|-----------|------|
| `AggregateRoot.version` | Number of events applied (including uncommitted) |
| `AggregateRoot.streamVersion` | Version of last committed event |
| `ExpectedVersion` | Value object for append precondition |
| `ConcurrencyConflictException` | Thrown on version mismatch |

### Flow

1. Aggregate created → `streamVersion = -1` (via `markAsNew()`)
2. Events raised → `version++`
3. Save with `ExpectedVersion::noStream()` or `ExpectedVersion::exact(streamVersion)`
4. EventStore validates expected vs actual
5. On mismatch → `ConcurrencyConflictException`
6. On success → `markCommitted(newVersion)`

---

## 7. Reconstitution Flow

```php
// Load from store
$storedEvents = $eventStore->load($tenantId, $streamId);

// Hydrate to domain events
$domainEvents = $eventHydrator->hydrateAll($storedEvents);

// Reconstitute aggregate
$aggregate = new Aggregate($id, $tenantId);
$aggregate->reconstituteFromEvents($domainEvents, count($domainEvents));
```

### Event Hydrator Contract

```php
interface IEventHydrator
{
    public function hydrate(StoredEvent $storedEvent): DomainEvent;
    public function hydrateAll(array $storedEvents): array;
}
```

---

## 8. Missing Components (To Be Implemented)

| Component | Status | Priority |
|-----------|--------|----------|
| Type fix in `apply()` | **Blocking** | Critical |
| Aligned migration schema | Exists but wrong | High |
| `ICommand` interface | Missing | Medium |
| `IQuery` interface | Missing | Medium |
| `ICommandHandler` interface | Missing | Medium |
| `IQueryHandler` interface | Missing | Medium |
| `IRepository<T, TId>` interface | Missing | Low |
| `IEventHydrator` interface | Missing | Low |
| Spiral Bootloader | Missing | Low |

---

## 9. Test Verification Criteria

The runtime spine is proven when:

| # | Test | Expected Result |
|---|------|-----------------|
| 1 | Create aggregate | Version = 1, uncommitted events = 1 |
| 2 | Emit event | State mutated, uncommitted events++ |
| 3 | Persist to PostgreSQL | Events stored, stream exists |
| 4 | Rehydrate | State identical to original |
| 5 | Concurrency conflict | `ConcurrencyConflictException` thrown |
| 6 | Tenant isolation | Cross-tenant query returns empty |

---

**Context Frozen.** All decisions locked. Proceed to implementation.
