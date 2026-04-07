# Repository Contract Enhancement — Specification

> **Feature:** Phase 5 — Release 1: Repository Contract Enhancement
> **Goal:** Extend IRepository interface with missing operations for production-grade aggregate persistence
> **Status:** Draft

---

## Overview

The kernel's `IRepository` interface is currently minimal (`getById()`, `save()`). This spec adds missing operations required for production ERP systems: create, delete, existence checks, listing with pagination, and batch operations.

---

## Architecture

### Approach: Minimal Enhancement

- Extend existing `IRepository<T, TId>` interface
- Add pagination value objects in `Domain/Shared/ValueObject/`
- Update `EventSourcedRepository` implementation
- No new abstraction layers — keep it simple

### Design Principles

1. **Tenant isolation mandatory** — Every operation takes `TenantId`
2. **Explicit over implicit** — `findById()` returns `Result<T>`, not `null`
3. **Pagination bounded** — Default limit 100, max 1000
4. **Batch operations supported** — `saveAll()`, `removeAll()`

---

## Components

### 1. IRepository Interface Extension

**File:** `packages/kernel/src/Infrastructure/Contract/Persistence/IRepository.php`

**New Methods:**

```php
/**
 * Adds a new aggregate to the repository.
 *
 * @param T $aggregate
 * @throws \Spiral\Kernel\Support\Exception\DomainException If aggregate already exists
 */
public function add(AggregateRoot $aggregate): void;

/**
 * Removes an aggregate by its ID.
 *
 * @param TId $id
 * @param TenantId $tenantId
 * @throws \Spiral\Kernel\Support\Exception\NotFoundException If aggregate not found
 */
public function remove(ValueObject $id, TenantId $tenantId): void;

/**
 * Checks if an aggregate exists.
 *
 * @param TId $id
 * @param TenantId $tenantId
 * @return bool
 */
public function exists(ValueObject $id, TenantId $tenantId): bool;

/**
 * Finds an aggregate by ID, returning Result (not null).
 *
 * @param TId $id
 * @param TenantId $tenantId
 * @return Result<AggregateRoot>
 */
public function findById(ValueObject $id, TenantId $tenantId): Result;

/**
 * Finds all aggregates for a tenant with pagination.
 *
 * @param TenantId $tenantId
 * @param Limit|null $limit
 * @param Offset|null $offset
 * @return list<AggregateRoot>
 */
public function findAll(TenantId $tenantId, ?Limit $limit = null, ?Offset $offset = null): array;

/**
 * Saves multiple aggregates in a batch.
 *
 * @param list<AggregateRoot> $aggregates
 */
public function saveAll(array $aggregates): void;
```

### 2. Pagination Value Objects

#### Limit

**File:** `packages/kernel/src/Domain/Shared/ValueObject/Pagination/Limit.php`

```php
final class Limit
{
    public function __construct(int $value) {
        if ($value < 1) {
            throw new \InvalidArgumentException('Limit must be >= 1');
        }
        if ($value > 1000) {
            throw new \InvalidArgumentException('Limit must be <= 1000');
        }
    }

    public static function default(): self; // 100
    public static function max(): self;      // 1000
    public static function fromInt(int $value): self;
    public function value(): int;
    public function toInt(): int;
}
```

#### Offset

**File:** `packages/kernel/src/Domain/Shared/ValueObject/Pagination/Offset.php`

```php
final class Offset
{
    public function __construct(int $value) {
        if ($value < 0) {
            throw new \InvalidArgumentException('Offset must be >= 0');
        }
    }

    public static function zero(): self;
    public static function fromInt(int $value): self;
    public function value(): int;
    public function toInt(): int;
}
```

### 3. EventSourcedRepository Implementation

**File:** `packages/kernel/src/Infrastructure/Persistence/EventSourcedRepository.php`

**Implementation Notes:**

- `add()`: Check `streamExists()` first, throw if true
- `remove()`: Delete stream via event store, throw if not found
- `exists()`: Return `streamExists()` result
- `findById()`: Return `Result::success()` or `Result::failure(new NotFoundException(...))`
- `findAll()`: Use event store's stream enumeration (or implement if not present)
- `saveAll()`: Iterate and call `save()` per aggregate

### 4. Error Handling

| Method | Success | Failure |
|--------|---------|----------|
| `add()` | void | DomainException (exists) |
| `remove()` | void | NotFoundException |
| `exists()` | bool | N/A |
| `findById()` | Result::success(T) | Result::failure(NotFoundException) |
| `findAll()` | list<T> | Empty list |
| `saveAll()` | void | ConcurrencyConflictException |

---

## Testing Requirements

### Unit Tests

1. `LimitTest` — Valid values, boundary checks (1, 100, 1000), invalid throws
2. `OffsetTest` — Valid values (0+), invalid throws
3. `IRepositoryTest` — Interface method signature verification

### Integration Tests

1. `EventSourcedRepositoryAddTest` — Add new aggregate, add duplicate throws
2. `EventSourcedRepositoryRemoveTest` — Remove existing, remove missing throws
3. `EventSourcedRepositoryExistsTest` — True/false cases
4. `EventSourcedRepositoryFindByIdTest` — Result success/failure paths
5. `EventSourcedRepositoryFindAllTest` — Pagination, empty tenant
6. `EventSourcedRepositorySaveAllTest` — Batch persistence

---

## Dependencies

- **Existing:** `IRepository`, `IEventStore`, `EventSourcedRepository`, `Result<T>`
- **New:** `Limit`, `Offset` value objects
- **No external packages required**

---

## Files to Modify

| File | Change |
|------|--------|
| `IRepository.php` | Add new interface methods |
| `EventSourcedRepository.php` | Implement new methods |
| `Domain/Shared/ValueObject/Pagination/Limit.php` | Create |
| `Domain/Shared/ValueObject/Pagination/Offset.php` | Create |
| `Result.php` | Ensure compatibility (already supports) |

---

## Files to Create

| File | Purpose |
|------|---------|
| `packages/kernel/src/Domain/Shared/ValueObject/Pagination/Limit.php` | Pagination limit |
| `packages/kernel/src/Domain/Shared/ValueObject/Pagination/Offset.php` | Pagination offset |
| `packages/kernel/tests/Unit/Domain/Shared/ValueObject/Pagination/LimitTest.php` | Unit tests |
| `packages/kernel/tests/Unit/Domain/Shared/ValueObject/Pagination/OffsetTest.php` | Unit tests |
| `packages/kernel/tests/Integration/Persistence/EventSourcedRepositoryTest.php` | Integration tests |

---

## Timeline

- **Task 1:** Create Limit and Offset value objects + unit tests
- **Task 2:** Extend IRepository interface
- **Task 3:** Implement new methods in EventSourcedRepository
- **Task 4:** Integration tests for all new operations
- **Task 5:** PHPStan level 9 verification

---

## Acceptance Criteria

1. ✅ `IRepository` has all 6 new methods defined
2. ✅ `Limit` validates 1-1000, defaults to 100
3. ✅ `Offset` validates >= 0
4. ✅ `EventSourcedRepository` implements all methods
5. ✅ `findById()` returns Result, never null
6. ✅ All tests pass (unit + integration)
7. ✅ PHPStan level 9 passes

---

**Spec Status:** Ready for Implementation