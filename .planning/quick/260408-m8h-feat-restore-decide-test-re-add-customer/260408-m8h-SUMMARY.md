# Quick Task 260408-m8h: Restore Customer::decide() as @internal Test Helper

**Status:** COMPLETED
**Date:** 2026-04-08
**Commit:** e288002
**Duration:** ~5 minutes

## Summary

Successfully restored `Customer::decide()` as an `@internal` test-only method, maintaining strict separation between production event-sourced API (register, rename, etc.) and testing domain command pattern.

### What Was Delivered

**File Modified:** `packages/kernel/src/Domain/Customer/Customer.php`

**Changes Made:**

1. **decide() Method** — Test-only command handler with @internal marker
   - Accepts union type parameter: `CreateCustomer|RenameCustomer`
   - Returns `Result<array<DomainEvent>>`
   - Dispatches to private handler methods via match expression
   - Clear docblock distinguishing from production API

2. **Private Handlers**
   - `handleCreateCustomer()` — Creates CustomerCreated event with state validation
   - `handleRenameCustomer()` — Creates CustomerRenamed event with state validation
   - Both marked @internal with proper return types

3. **Test Event Handlers** — Added to apply() method for event replay
   - `applyCreated()` — Applies CustomerCreated test event
   - `applySimpleRenamed()` — Applies CustomerRenamed test event

4. **Imports** — Added for test event classes
   - `use Spiral\Kernel\Domain\Customer\CustomerCreated;`
   - `use Spiral\Kernel\Domain\Customer\CustomerRenamed;`

### Quality Assurance

**Static Analysis:** ✓ PASSED
- PHPStan level 9: No errors
- Proper generic type annotations on all methods
- Union type parameter ensures match expression exhaustiveness

**Unit Tests:** ✓ ALL PASSED (5/5)
- Determinism
- Register produces event
- Register twice fails
- Rename before register fails
- Replay correctness

**Code Quality:**
- Strict types enforced
- Immutability preserved
- LSP compliance maintained (no dual-execution paths)
- Clear architectural separation (production vs. testing)

## Architectural Correctness

### Production API (Event-Sourced)
```php
public function register()    // Event: CustomerRegistered
public function rename()       // Event: CustomerRenamed (event-sourced)
public function verifyEmail()  // Event: CustomerEmailVerified
public function deactivate()   // Event: CustomerDeactivated
public function reactivate()   // Event: CustomerReactivated
```

### Testing API (@internal)
```php
public function decide(CreateCustomer|RenameCustomer): Result<array<DomainEvent>>
```

**Key Principle:** Test pattern (decide → events) is clearly marked @internal and separated from production flow.

## Deviations from Plan

**1. [Auto-fix] PHPStan Compliance Issues**
- **Found during:** Static analysis verification
- **Issues:**
  1. Generic type parameters missing from private method return types
  2. Unreachable match arm in decide() due to parameter type mismatch
- **Fixes Applied:**
  1. Added `@return Result<array<DomainEvent>>` to private methods
  2. Changed parameter type from `object` to union `CreateCustomer|RenameCustomer`
  3. Removed unreachable default match arm (now guaranteed exhaustive)
- **Result:** PHPStan level 9 now passes with zero errors

## Files Modified

| File | Changes | Lines |
|------|---------|-------|
| `packages/kernel/src/Domain/Customer/Customer.php` | decide() + handlers + test event handlers | +97 |

## Testing Results

```
PHPUnit 11.5.55
Configuration: phpunit.xml
Runtime: PHP 8.4.16

Tests: 5 passed, 0 failed (100%)
Assertions: 11
Memory: 10.00 MB
Time: 0.033s

Test Cases:
 ✔ Determinism
 ✔ Register produces event
 ✔ Register twice fails
 ✔ Rename before register fails
 ✔ Replay correctness
```

## Verification Checklist

- [x] decide() method has @internal docblock
- [x] Production and test APIs clearly separated
- [x] All test events (CustomerCreated, CustomerRenamed) have handlers
- [x] Tests pass with new changes (5/5 passing)
- [x] PHPStan level 9 analysis passes (0 errors)
- [x] Code follows project conventions
- [x] Imports properly declared
- [x] Union type prevents invalid commands
- [x] Match expression is exhaustive

## Next Steps

- Quick task 260408-m8h is complete
- Customer aggregate now has both production and testing capabilities
- Suitable for integration testing of domain behavior

## Summary for STATE.md Update

Entry to add to `### Quick Tasks Completed` section:

```
| 260408-m8h | Restore Customer::decide() as @internal test helper | 2026-04-08 | e288002 | [260408-m8h-feat-restore-decide-test-re-add-customer](./quick/260408-m8h-feat-restore-decide-test-re-add-customer/) |
```
