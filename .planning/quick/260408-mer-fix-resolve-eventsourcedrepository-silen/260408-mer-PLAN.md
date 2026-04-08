---
phase: quick
plan: 260408-mer
type: execute
wave: 1
depends_on: []
files_modified:
  - packages/kernel/src/Infrastructure/Persistence/EventSourcedRepository.php
  - packages/kernel/tests/Unit/Infrastructure/Persistence/EventSourcedRepositoryTest.php
autonomous: true
requirements: []
user_setup: []

must_haves:
  truths:
    - "EventStore exceptions are caught and re-thrown with aggregate context"
    - "Concurrency conflicts preserve expected/actual version information"
    - "Tenant context is maintained in all error conditions"
    - "Causation chains from domain events are included in exception metadata"
    - "No silent suppression of EventStore failures"
  artifacts:
    - path: "packages/kernel/src/Infrastructure/Persistence/EventSourcedRepository.php"
      provides: "Error handling wrapper around EventStore.append()"
      exports: ["save() with exception context"]
    - path: "packages/kernel/tests/Unit/Infrastructure/Persistence/EventSourcedRepositoryTest.php"
      provides: "Unit tests for error propagation"
      min_lines: 80
  key_links:
    - from: "EventSourcedRepository.save()"
      to: "IEventStore.append()"
      via: "try/catch with context enrichment"
      pattern: "ConcurrencyConflictException|EventStoreException"
    - from: "Exception handler"
      to: "Domain events"
      via: "extract causation from events"
      pattern: "popUncommittedEvents"
---

<objective>
Fix silent exception handling in EventSourcedRepository.save() method. When EventStore operations fail, exceptions must propagate with complete context (tenant, aggregate ID, causation chains) rather than being lost.

Purpose: Provide complete observability into persistence failures. Missing context makes production debugging impossible.

Output: Improved error visibility in EventSourcedRepository with unit tests verifying exception propagation.
</objective>

<execution_context>
@packages/kernel/src/Infrastructure/Persistence/EventSourcedRepository.php
@packages/kernel/src/Infrastructure/Contract/EventStore/IEventStore.php
@packages/kernel/src/Support/Exception/EventStoreException.php
@packages/kernel/src/Support/Exception/ConcurrencyConflictException.php
</execution_context>

<context>
Current EventSourcedRepository.save() (line 75-98) has NO exception handling around eventStore.append() call. When EventStore fails, exceptions propagate with no context about:
- Which aggregate was being saved
- Tenant context
- Causation chains from the domain events

Per CLAUDE.md error patterns: exceptions must preserve tenant context, correlation IDs, and causation chains. This is critical for production debugging and audit trails.

IEventStore.append() throws:
- ConcurrencyConflictException (version mismatch)
- DomainException (structural errors)
- EventStoreException (database failures)

All must be caught and re-thrown with aggregate/tenant context.
</context>

<tasks>

<task type="auto">
  <name>Task 1: Add error handling to EventSourcedRepository.save() with context preservation</name>
  <files>packages/kernel/src/Infrastructure/Persistence/EventSourcedRepository.php</files>
  <action>
Wrap the eventStore.append() call (line 90-95) in a try/catch block that enriches exceptions with aggregate context:

1. Extract first domain event to access causation chain (if events exist)
2. Wrap append() in try/catch:
   - Catch ConcurrencyConflictException: re-throw as-is (already has version info)
   - Catch DomainException: re-throw as-is (structural errors bubble up)
   - Catch EventStoreException: re-throw as-is (database failures bubble up)
   - Catch Throwable: wrap in RuntimeException with context message including:
     * aggregate type (from streamPrefix)
     * aggregate ID
     * tenant ID
     * first event's correlation/causation IDs (if present)

3. Pattern: On catch, construct error context string:
   ```
   sprintf(
       'Failed to persist aggregate [%s:%s] for tenant [%s] (correlation: %s, causation: %s)',
       $this->streamPrefix,
       $aggregate->getId(),
       $tenantId->toString(),
       $firstEvent->correlationId() ?? 'N/A',
       $firstEvent->causationId() ?? 'N/A'
   )
   ```

4. Use this message in exception re-throw or RuntimeException wrap.

5. Add clear PHPDoc @throws tags documenting which exceptions propagate.

No silent suppression: all exceptions must reach caller with observable context.
  </action>
  <verify>
    <automated>cd /home/dominus/Project/EPSILON && ./vendor/bin/phpstan analyse packages/kernel/src/Infrastructure/Persistence/EventSourcedRepository.php</automated>
  </verify>
  <done>
    - save() method wraps eventStore.append() in try/catch
    - All exceptions caught and re-thrown with aggregate/tenant context
    - Error messages include correlation/causation IDs from domain events
    - No silent suppression of EventStore failures
    - PHPStan level 9 passes
  </done>
</task>

<task type="auto">
  <name>Task 2: Add unit tests for EventSourcedRepository error handling</name>
  <files>packages/kernel/tests/Unit/Infrastructure/Persistence/EventSourcedRepositoryTest.php</files>
  <action>
Create new unit test file with 4 test cases verifying error handling:

1. Create a mock EventStore that throws specific exceptions
2. Create a mock AggregateRoot with known ID and TenantId
3. Create a mock EventHydrator (not used in save(), but required for constructor)

Test cases:

**testSavePropagatesConcurrencyConflictException:**
- Setup: Mock eventStore.append() throws ConcurrencyConflictException
- Execute: Call repository.save(aggregate)
- Verify: ConcurrencyConflictException propagates unchanged
- Verify: Exception contains expected/actual version info

**testSavePreservesAggregateContextOnFailure:**
- Setup: Mock eventStore.append() throws EventStoreException
- Execute: Call repository.save(aggregate) with known aggregate ID "order-123"
- Verify: EventStoreException propagates
- Verify: If wrapped/enriched, error message contains aggregate ID and tenant

**testSaveIncludesCausationChainOnError:**
- Setup: Create 2 uncommitted events with correlation/causation IDs set
- Setup: Mock eventStore.append() to throw EventStoreException
- Execute: Call repository.save(aggregate)
- Verify: Exception message/context includes correlation and causation IDs

**testSaveNoSilentFailures:**
- Setup: Mock eventStore.append() throws generic RuntimeException
- Execute: Call repository.save(aggregate)
- Verify: RuntimeException is caught and re-thrown (not silently suppressed)
- Verify: Re-thrown exception message contains aggregate context

Use standard PHPUnit patterns:
- createMock() for EventStore
- Aggregate fixture with getId(), getTenantId(), popUncommittedEvents()
- Domain events with correlationId() and causationId() methods
- See packages/kernel/tests/Unit/Domain/Shared/Result/ResultTest.php for assertion patterns

Target: ~80 lines of focused unit tests with clear setup/assert.
  </action>
  <verify>
    <automated>cd /home/dominus/Project/EPSILON && ./vendor/bin/phpunit --testsuite Unit --filter EventSourcedRepository 2>&1 | head -50</automated>
  </verify>
  <done>
    - 4 unit tests created verifying error propagation
    - ConcurrencyConflictException handling verified
    - Tenant/aggregate context preserved in errors
    - Causation chains included in error context
    - All tests pass
    - Test file follows naming convention: EventSourcedRepositoryTest.php
  </done>
</task>

</tasks>

<verification>
After completion, run:

```bash
cd /home/dominus/Project/EPSILON

# 1. Type check
./vendor/bin/phpstan analyse packages/kernel/src/Infrastructure/Persistence/EventSourcedRepository.php

# 2. Unit tests
./vendor/bin/phpunit --testsuite Unit --filter EventSourcedRepository

# 3. Full unit suite (ensure no regression)
./vendor/bin/phpunit --testsuite Unit
```

Expected state:
- EventSourcedRepository.php: PHPStan level 9 passes
- New test file: 4 tests, all green
- All unit tests: no new failures
</verification>

<success_criteria>
- [x] EventSourcedRepository.save() has complete error handling
- [x] ConcurrencyConflictException and EventStoreException propagate with context
- [x] Tenant ID and aggregate ID included in all error messages
- [x] Causation/correlation chains from domain events preserved in context
- [x] No silent suppression of EventStore failures
- [x] Unit tests verify all error paths
- [x] PHPStan level 9 passes
- [x] All unit tests pass (including new tests)
</success_criteria>

<output>
After completion, update:
- `.planning/STATE.md` — Log quick task completion (260408-mer)
- Commit changes to git
</output>
