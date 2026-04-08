---
quick_task_id: 260408-jzs
title: Fix 7 Critical Kernel Issues
created: 2026-04-08
mode: quick
context_budget: 30%
target_completion_time: 120  # minutes
---

# QUICK TASK: Fix 7 Critical Kernel Issues

## Objective

Fix 7 critical kernel issues affecting AggregateRoot version tracking, event handling, LSP compliance, and financial calculations. Each issue is isolated and can be fixed independently.

**Why this matters:** These issues cause inconsistent state management, silent event loss, LSP violations, and rounding errors in financial operations. All are blocker-level bugs.

## Issues Summary

| # | Issue | File | Problem | Impact |
|---|-------|------|---------|--------|
| 1 | Version tracking inconsistency | AggregateRoot.php | `reconstituteFromEvents()` double-increments | State version mismatch after reload |
| 2 | LSP violation | Organization.php | `apply()` throws but parent doesn't declare it | Subclass contract violation |
| 3 | Dual execution path | Customer.php | `decide()` + public methods conflicting | Code confusion, maintainability issue |
| 4 | Rounding error | Money.php | `allocate()` floor calculation error | Financial precision loss |
| 5 | Redundant validation | TenantSlug.php | Regex + hyphen check duplication | Hard to maintain, inconsistent |
| 6 | Missing exception | Infrastructure | EventStoreException not defined | Type safety violation |
| 7 | Event handler gaps | Customer.php | Unknown events throw, may lose data | Silent data loss risk |

---

## Task 1: Fix AggregateRoot Version Tracking Inconsistency

**File:** `packages/kernel/src/Domain/Shared/Aggregate/AggregateRoot.php`

**Issue:** In `reconstituteFromEvents()` (lines 153-162), the version is set to `streamVersion`, then incremented for each event. This creates double-counting:
- Initial: `version = streamVersion` (e.g., 5)
- Loop: increment for each of 3 events: `version = 6, 7, 8`
- Final: `version = 8` (should be `5 + 3 = 8`, but logic is backwards)

The correct semantics: `streamVersion` is the version of the last persisted event. After loading 3 events on top of version 5, the final version should be exactly `5 + number_of_loaded_events`.

**Fix:**
```php
public function reconstituteFromEvents(array $events, int $streamVersion = 0): void
{
    $this->streamVersion = $streamVersion;
    $this->version = $streamVersion;  // Start from stream version

    foreach ($events as $event) {
        $this->apply($event);
        $this->version++;  // Increment AFTER apply
    }
    // Final version = streamVersion + count(events) ✓
}
```

The code is actually correct as-is. The issue is that `version` should reflect "number of events applied" not "version after applying". Verify the semantics: does `getVersion()` return the version number for concurrency checking or event count?

**Action:** Verify semantics of `version` vs `streamVersion` in tests. If `version` should be event count, ensure it's correct. If `version` should be the next version number for optimistic concurrency, ensure increment happens BEFORE, not after event application.

**Verify:** Run `cd packages/kernel && ./vendor/bin/phpunit tests/Unit/Domain/Shared/Aggregate/AggregateRootTest.php --filter version`

**Done:** Version tracking is consistent: `streamVersion = N` means "N events persisted", `version = streamVersion + uncommitted_count`

---

## Task 2: Fix Organization::apply() LSP Violation

**File:** `packages/organization/src/Domain/Aggregate/Organization.php` (lines 227-244)

**Issue:** The `apply()` method throws `RuntimeException` for unknown event types (line 236-242):
```php
protected function apply(DomainEvent $event): void
{
    match ($event::class) {
        // ... handlers ...
        default => throw new \RuntimeException(...),
    };
}
```

This violates Liskov Substitution Principle: parent class `AggregateRoot` declares `apply()` as `abstract protected` with no throw specification. Subclass `Organization` adds undeclared throwing behavior.

**Fix Options:**
- **Option A (Strict):** Make parent throw: Update `AggregateRoot::apply()` docblock to declare it can throw and is abstract.
- **Option B (Defensive):** Log and continue: Instead of throwing, log unknown events and return gracefully.
- **Option C (Type-safe):** Use sealed union: Create specific event interface `OrganizationEvent` and guarantee coverage.

**Action:** Choose Option A (strict): Add docblock to `AggregateRoot::apply()` declaring throws behavior. Update Organization::apply() docblock to match parent contract.

**Verify:** PHPStan analysis passes level 9: `cd packages/kernel && ./vendor/bin/phpstan analyse`

**Done:** Organization::apply() conforms to Liskov Substitution Principle. Parent class documents throw behavior. No subclass adds undeclared exceptions.

---

## Task 3: Fix Customer::decide() Dual Execution Path

**File:** `packages/kernel/src/Domain/Customer/Customer.php` (lines 206-244)

**Issue:** Customer has two conflicting patterns:
1. **Event-sourced methods** (lines 49-204): `register()`, `verifyEmail()`, `rename()`, etc. — call `raise()` directly, apply events immediately
2. **Domain command handler** (lines 229-244): `decide()` method — returns events without applying them

This dual path causes:
- Code confusion: What's the "right" way to execute commands?
- Inconsistency: Some commands raise+apply, others just return events
- Testing nightmare: Two different semantics to test

**Fix:** Choose event-sourced pattern as canonical. Remove `decide()` and related domain command classes.

**Why:** Event-sourced methods are the public contract. Domain commands in `decide()` are for "functional testing" but they're never called. Remove dead code.

**Action:**
1. Delete `decide()` method (lines 229-244)
2. Delete `handleCreateCustomer()` method (lines 254-275)
3. Delete `handleRenameCustomer()` method (lines 286-315)
4. Remove domain event classes if they exist: CustomerCreated, CustomerRenamed
5. Update `apply()` match statement to remove CustomerCreated and CustomerRenamed cases (lines 320-322)

**Verify:**
```bash
cd packages/kernel
./vendor/bin/phpunit tests/Unit/Domain/Customer/CustomerTest.php
./vendor/bin/phpstan analyse
```

**Done:** Customer uses single event-sourced pattern. No dual execution paths. Public methods (register, verify, rename, etc.) are the only way to execute domain commands.

---

## Task 4: Fix Money::allocate() Rounding Error

**File:** `packages/kernel/src/Domain/Shared/ValueObject/Financial/Money.php` (lines 252-285)

**Issue:** The allocation logic uses floor-based rounding but has a subtle bug. Line 278:
```php
$allocated = $isLast
    ? $remaining
    : (int) floor($this->minorUnits * $proportion);
```

When `$proportion` is calculated (line 268):
```php
$normalizedRatios = array_map(fn(float $r) => $r / $total, $ratios);
```

The issue: floating-point precision in `$proportion` can cause floor() to lose pennies across iterations. Example:
- Original: 100 cents, ratios [1, 1, 1] → each should get 33.33... → [33, 33, 34]
- But: `100 * (1/3) = 33.333...` → floor = 33 for all three → remainder = 1 cent

The "last item gets remainder" fixes this, BUT only for the very last item. If there are rounding errors in the middle allocations, precision is lost.

**Fix:** Use accumulated allocation instead of proportional:
```php
$allocated = $isLast
    ? $remaining
    : (int) floor($remaining * ($normalizedRatios[$i] / array_sum(array_slice($normalizedRatios, $i))));
```

OR: Simply accumulate and track remainder properly.

**Better approach:** Use banker's rounding for intermediate steps, not floor. Or: allocate sequentially using remaining balance.

**Action:** Replace allocation logic to use accumulated allocation:
```php
for ($i = 0; $i < $count; $i++) {
    $isLast = $i === $count - 1;

    // For last item, use remaining balance (guarantees sum equals original)
    if ($isLast) {
        $allocated = $remaining;
    } else {
        // Use rounding banker's rule: round to nearest, ties to even
        $proportion = $normalizedRatios[$i];
        $allocated = (int) round($this->minorUnits * $proportion);
        // But don't over-allocate
        $allocated = min($allocated, $remaining);
    }

    $result[] = new self($this->currency, $allocated);
    $remaining -= $allocated;
}
```

**Verify:**
```bash
cd packages/kernel
./vendor/bin/phpunit tests/Unit/Domain/Shared/ValueObject/Financial/MoneyTest.php --filter allocate
```

Test cases:
- 100 cents / [1, 1, 1] → [33, 33, 34]
- 97 cents / [1, 1, 1] → [32, 32, 33]
- Sum of results === original amount

**Done:** `allocate()` maintains exact sum. No rounding loss. Last item receives remainder to guarantee precision.

---

## Task 5: Fix TenantSlug Redundant Validation

**File:** `packages/kernel/src/Domain/Tenancy/TenantSlug.php` (lines 57-99)

**Issue:** Validation has redundancy:
- Line 77: Regex checks format, start/end rules, no consecutive hyphens
- Line 85: Explicit check for consecutive hyphens (redundant with regex)

The regex `/^[a-z](?:[a-z0-9]|[a-z0-9\-]*[a-z0-9])?$/` should catch:
- ✓ Starts with `[a-z]`
- ✓ Optional middle part `[a-z0-9\-]*` (allows hyphens)
- ✓ Ends with `[a-z0-9]`

But the regex doesn't prevent `--` (consecutive hyphens). The hyphen check on line 85 is necessary but appears after the regex.

Also, empty string is checked twice (lines 59-61 and again via regex).

**Fix:** Consolidate into single, comprehensive regex:
```php
// Pattern explanation:
// ^[a-z]           - Must start with lowercase letter
// (?:
//   [a-z0-9]       - Single character (letter or number)
//   |
//   [a-z0-9](?:[a-z0-9]*[a-z0-9])?  - Letter/number, optionally followed by more, ending with letter/number
//   |
//   [a-z0-9]\-[a-z0-9](?:([a-z0-9]|[\-](?=[a-z0-9]))*[a-z0-9])?  - With hyphens, but no consecutive
// )*$
```

Actually, a cleaner approach: Use a simpler regex and explicit checks in order:

**Action:**
1. Remove redundant empty check (line 59-61)
2. Update regex to `/^[a-z][a-z0-9]*(?:\-[a-z0-9]+)*$/` (starts with letter, allows segments separated by single hyphen)
3. Keep explicit consecutive hyphen check for clarity (since regex is hard to read)
4. Remove final length checks in constructor (line 44-46) - keep only in `fromString()`

**Verify:**
```bash
cd packages/kernel
./vendor/bin/phpunit tests/Unit/Domain/Tenancy/TenantSlugTest.php
```

Test cases:
- Valid: `acme-corp`, `a`, `abc`, `my-tenant-slug`
- Invalid: `--`, `-acme`, `acme-`, `acme--corp`, `123abc`, `ACME`, etc.

**Done:** TenantSlug validation is clear, consolidated, and maintainable. No redundant checks. Regex is understandable or replaced with simple logic.

---

## Task 6: Create EventStoreException

**File:** `packages/kernel/src/Support/Exception/EventStoreException.php` (NEW)

**Issue:** No dedicated exception for EventStore operations. When EventStore fails (append, load, version mismatch), it should throw a specific exception type for:
- Concurrency conflicts (already have `ConcurrencyConflictException`)
- Stream not found (already have `NotFoundException`)
- **Missing:** General EventStore failures (invalid state, persistence errors, etc.)

**Fix:** Create `EventStoreException` base class in Support/Exception:

**Action:**
```php
<?php declare(strict_types=1);

namespace Spiral\Kernel\Support\Exception;

/**
 * Base exception for event store failures.
 *
 * Subclasses:
 * - ConcurrencyConflictException (version mismatch)
 * - NotFoundException (stream not found)
 * - EventStoreException (general failures)
 *
 * Use for: Failed appends, invalid streams, persistence errors, etc.
 */
class EventStoreException extends KernelException
{
    public static function failedToAppend(string $streamId, string $reason): self
    {
        return new self(\sprintf(
            'Failed to append events to stream "%s": %s',
            $streamId,
            $reason
        ));
    }

    public static function failedToLoad(string $streamId, string $reason): self
    {
        return new self(\sprintf(
            'Failed to load stream "%s": %s',
            $streamId,
            $reason
        ));
    }

    public static function invalidStreamState(string $streamId, string $detail): self
    {
        return new self(\sprintf(
            'Invalid stream state for "%s": %s',
            $streamId,
            $detail
        ));
    }
}
```

**Verify:**
```bash
cd packages/kernel
./vendor/bin/phpstan analyse
./vendor/bin/phpunit tests/Unit/Support/Exception/EventStoreExceptionTest.php
```

**Done:** EventStoreException created. Can be thrown by EventStore implementations. Extends KernelException. Includes factory methods for common failure scenarios.

---

## Task 7: Fix Customer::apply() Event Handler Gaps

**File:** `packages/kernel/src/Domain/Customer/Customer.php` (lines 317-333)

**Issue:** The `apply()` method uses a match expression that throws `RuntimeException` for unknown events (line 329-331):
```php
default => throw new \RuntimeException(
    sprintf('Unknown event type: %s', get_class($event))
),
```

This is correct behavior (fail fast on unknown events). BUT the issue might be:
1. Events from Task 3 (CustomerCreated, CustomerRenamed) are still in the match but deleted from handlers → dead code
2. Some event types exist but aren't handled → silent failures
3. EventCustomerRenamed vs CustomerRenamed confusion (line 14, 304, 322-324)

**Fix:** After Task 3 removes domain commands:
1. Remove CustomerCreated case (line 321)
2. Remove CustomerRenamed (domain) case (line 322)
3. Ensure EventCustomerRenamed is the only rename event (line 324, 358)
4. Update docblock to list all handled events

**Action:**
```php
/**
 * Apply events to mutate state.
 *
 * Handles the following events:
 * - CustomerRegistered: Register new customer
 * - CustomerEmailVerified: Mark email as verified
 * - CustomerRenamed (event-sourced): Rename customer
 * - CustomerDeactivated: Deactivate customer
 * - CustomerReactivated: Reactivate customer
 *
 * Throws RuntimeException for unknown event types (fail-fast).
 */
protected function apply(DomainEvent $event): void
{
    match (true) {
        $event instanceof EventCustomerRenamed => $this->applyRenamed($event),
        $event instanceof CustomerRegistered => $this->applyRegistered($event),
        $event instanceof CustomerEmailVerified => $this->applyVerified($event),
        $event instanceof CustomerDeactivated => $this->applyDeactivated($event),
        $event instanceof CustomerReactivated => $this->applyReactivated($event),
        default => throw new \RuntimeException(
            sprintf('Unknown event type: %s', $event::class)
        ),
    };
}
```

**Verify:**
```bash
cd packages/kernel
./vendor/bin/phpunit tests/Unit/Domain/Customer/CustomerTest.php
./vendor/bin/phpstan analyse
```

**Done:** Customer::apply() handles all event types. No dead code. Event types are clear. Unknown events fail loudly. No silent event loss.

---

## Execution Order

Tasks can run **in parallel** (independent file modifications):
- **Wave 1:** Tasks 1, 2, 4, 5, 6 (can run simultaneously)
- **Wave 2:** Tasks 3, 7 (depend on Task 3 removal of domain commands, Task 7 cleans up after)

**Recommended sequential execution:**
1. Task 1 (AggregateRoot) — Low risk, high confidence fix
2. Task 6 (EventStoreException) — New file, no dependencies
3. Task 5 (TenantSlug) — Validation consolidation
4. Task 4 (Money.allocate) — Financial precision fix
5. Task 2 (Organization LSP) — Documentation/semantics
6. Task 3 (Customer.decide removal) — Removes dead code
7. Task 7 (Customer.apply cleanup) — Cleans up after Task 3

---

## Summary

**Total effort:** ~120 minutes (2 hours)

**Context budget:** ~30% (focused tasks, clear fixes)

**Risk:** Low (isolated fixes, mostly consolidation and dead code removal)

**Verification:** Each task includes specific test commands and acceptance criteria

**Outcome:** Kernel is semantically correct, LSP-compliant, no rounding errors, single execution pattern, proper exception handling
