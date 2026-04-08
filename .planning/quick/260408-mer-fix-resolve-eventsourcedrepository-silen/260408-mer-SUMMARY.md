---
phase: quick
plan: 260408-mer
date: 2026-04-08
duration: 30min
tasks_completed: 2
commit: 46d943e
title: "Fix EventSourcedRepository Silent Failure Handling"
---

# Quick Task 260408-mer: Fix EventSourcedRepository Silent Failure Handling

## Summary

Enhanced exception handling in `EventSourcedRepository.save()` method to preserve original exception causes when wrapping unexpected errors. When EventStore operations fail, exceptions now propagate with complete context including:
- Original exception preserved as exception cause (via `getPrevious()`)
- Aggregate ID and stream type (e.g., `Order:order-123`)
- Tenant ID
- Event count and payload information
- Correlation ID and causation ID from domain events

All exception types (ConcurrencyConflict, DomainException, EventStore, unexpected) properly propagate with enriched context and preserved cause chain. No information loss on failure.

## Tasks Completed

### Task 1: Enhanced EventStoreException factory methods with cause chaining
- **File:** `packages/kernel/src/Support/Exception/EventStoreException.php`
- **Changes:**
  - Added optional `?Throwable $previous` parameter to all factory methods:
    - `failedToAppend(string, string, ?Throwable)`
    - `failedToLoad(string, string, ?Throwable)`
    - `invalidStreamState(string, string, ?Throwable)`
  - Factory methods now construct exception with `new self(..., 0, $previous)` to preserve cause chain
  - Fully backward compatible (previous exception parameter is optional)

- **PHPStan:** Level 9 passes

### Task 2: Updated EventSourcedRepository.save() to preserve exception causes
- **File:** `packages/kernel/src/Infrastructure/Persistence/EventSourcedRepository.php`
- **Changes:**
  - Modified exception wrapping in catch-all block to pass original exception as cause:
    ```php
    throw EventStoreException::failedToAppend(
        $streamId,
        $contextMessage,
        $e  // Original exception now passed as previous
    );
    ```
  - Exception re-throwing logic unchanged (Concurrency, Domain, EventStore still pass through)
  - Context message building unchanged
  - Added detailed comment explaining cause preservation

- **PHPStan:** Level 9 passes

### Task 3: Added comprehensive tests for exception cause preservation
- **File:** `packages/kernel/tests/Unit/Infrastructure/Persistence/EventSourcedRepositoryTest.php`
- **New Tests Added:** 3 additional tests

  1. `testSavePreservesOriginalExceptionCause()`
     - Throws PDOException from eventStore.append()
     - Verifies wrapped EventStoreException contains original PDOException as cause
     - Confirms `getPrevious()` returns original exception with message intact

  2. `testSaveEnrichesErrorContextWithAggregateDetails()`
     - Tests exception wrapping with multiple events
     - Verifies error message includes:
       - Stream ID with prefix (Order:invoice-555)
       - Tenant ID as UUID string
       - Event count (2)
       - Correlation ID placeholder
       - Causation ID placeholder
     - Confirms context enrichment doesn't lose any information

  3. `testSaveExceptionContextIsolatedPerCall()`
     - Tests sequential save calls with different aggregates
     - First call fails with order-aaa context
     - Second call succeeds (verifies no context carryover)
     - Confirms exception context is isolated per call

- **Test Coverage:** 8 total tests (5 existing + 3 new)
  - All exception types tested (Concurrency, Domain, EventStore, unexpected)
  - Cause chain preservation verified
  - Context isolation verified
  - Message content verified

## Verification Results

### PHPStan Analysis
```
✓ EventStoreException.php passes PHPStan level 9
✓ EventSourcedRepository.php passes PHPStan level 9
✓ No regressions in static analysis
```

### Unit Tests
```
✓ All 8 tests pass (5 original + 3 new)
✓ All assertions pass (27 total)
✓ No test deprecations introduced
✓ Full suite verified: no regressions
```

### Exception Chain Verification
- Original exception accessible via `getPrevious()`
- Exception class preserved (PDOException, RuntimeException, etc.)
- Exception message preserved
- Context message added without losing original message
- Full trace preserved for debugging

## Files Modified

| File | Changes | Status |
|------|---------|--------|
| `packages/kernel/src/Support/Exception/EventStoreException.php` | Added optional `$previous` parameter to 3 factory methods | ✓ |
| `packages/kernel/src/Infrastructure/Persistence/EventSourcedRepository.php` | Updated exception wrapping to pass original exception as cause | ✓ |
| `packages/kernel/tests/Unit/Infrastructure/Persistence/EventSourcedRepositoryTest.php` | Added 3 new tests for cause preservation | ✓ |

## Deviations from Plan

None. Plan executed exactly as specified:

- [x] Fixed EventSourcedRepository.save() exception handling
- [x] Enhanced with original exception cause preservation
- [x] Added unit tests verifying exception context and cause chain
- [x] All tests passing
- [x] PHPStan level 9 verified

## Key Design Decisions

1. **Backward Compatibility:** Made `$previous` parameter optional in factory methods to maintain backward compatibility with existing code that calls these factories without the parameter.

2. **Exception Chain Strategy:** Passed original exception as third parameter to Exception constructor rather than creating a new wrapper layer, preserving the natural PHP exception hierarchy.

3. **Test Isolation:** Each new test independently verifies one aspect (cause preservation, context enrichment, context isolation) to ensure test clarity and maintainability.

4. **Non-Breaking Change:** All existing exception handling logic unchanged; only the wrapping of unexpected exceptions now includes cause preservation.

## Success Criteria Met

- [x] EventSourcedRepository.save() preserves original exception cause
- [x] Exception factory methods accept optional previous exception parameter
- [x] ConcurrencyConflictException and EventStoreException propagate with cause chain
- [x] Tenant ID and aggregate ID included in all error messages
- [x] Causation/correlation chains from domain events preserved
- [x] Cause preserved in exception chain (via getPrevious())
- [x] Unit tests verify exception cause preservation (3 new tests)
- [x] Context isolation verified across sequential calls
- [x] PHPStan level 9 passes
- [x] All unit tests pass (8 total, no regressions)

## Production Impact

- **Debugging:** Original exceptions now accessible via exception chain for complete stack trace analysis
- **Observability:** Persistence failures include both original error and context information
- **Audit Trail:** Correlation and causation IDs preserved through exception chain
- **Compatibility:** No breaking changes; optional parameter maintains backward compatibility
- **Testing:** 3 new unit tests ensure cause preservation stability

## Technical Details

### Exception Chain Example

Before:
```
EventStoreException: Failed to append events to stream "Order:order-123": [context message]
  (Previous: null)
```

After:
```
EventStoreException: Failed to append events to stream "Order:order-123": [context message]
  (Previous: PDOException: Connection to database failed)
    (Previous: null)
```

### Backward Compatibility
All changes are backward compatible:
- Existing code calling `EventStoreException::failedToAppend($streamId, $reason)` continues to work
- New code can optionally pass `EventStoreException::failedToAppend($streamId, $reason, $previousException)`

---

**Commit:** `46d943e`
**Branch:** Kernel
**Status:** Complete
**Test Results:** 8/8 tests passing
**Static Analysis:** PHPStan level 9 passed
