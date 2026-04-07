# Testing Patterns

**Analysis Date:** 2026-04-06

## Test Framework

**Runner:**
- PHPUnit 11.0
- Config: `packages/kernel/phpunit.xml`

**Assertion Library:**
- PHPUnit built-in assertions (`assertSame`, `assertInstanceOf`, `assertTrue`, `expectException`)

**Run Commands:**
```bash
cd packages/kernel && ./vendor/bin/phpunit              # Run all tests
cd packages/kernel && ./vendor/bin/phpunit --testsuite Unit # Unit tests only
cd packages/kernel && ./vendor/bin/phpunit --testsuite Integration # Integration tests only
```

## Test File Organization

**Location:**
- Separate `tests/` directory mirroring `src/` structure.

**Naming:**
- `{ClassName}Test.php`

**Structure:**
```
packages/kernel/tests/
├── Unit/             # Isolated logic tests (ValueObjects, Results)
├── Integration/      # Component interaction tests (EventStore, Persistence)
├── EndToEnd/         # Full system flow tests
├── Smoke/            # High-level stability checks
└── Fixture/          # Reusable test data and mock objects
```

## Test Structure

**Suite Organization:**
```php
final class ResultCreationTest extends KernelTestCase
{
    // ========== Success Creation Tests ==========
    public function testSuccessCreatesSuccessInstance(): void
    {
        $result = Result::success('test value');
        $this->assertInstanceOf(Success::class, $result);
    }
}
```

**Patterns:**
- **Base Classes:** `KernelTestCase` for unit tests, `IntegrationTestCase` for DB-dependent tests.
- **Setup/Teardown:** `IntegrationTestCase` handles PostgreSQL connection and truncates tables in `tearDown()` to ensure test isolation.
- **Data Driven:** Use of multiple test methods per class to cover edge cases (e.g., `ResultEdgeCasesTest`).

## Mocking

**Framework:** PHPUnit Mocks / Manual Fixtures.

**Patterns:**
- Usage of `Fixture` directory for complex objects (e.g., `TestAggregate.php`, `TestEvent.php`).

**What to Mock:**
- External infrastructure dependencies.

**What NOT to Mock:**
- Core domain primitives (Value Objects, Result monad) - these are tested concretely.

## Fixtures and Factories

**Test Data:**
- Concrete fixture classes in `packages/kernel/tests/Fixture/`.
- Manual creation of `ErrorDetail` and `ErrorCode` for result failure tests.

**Location:**
- `packages/kernel/tests/Fixture/`

## Coverage

**Requirements:** Defined in `phpunit.xml` via `<coverage>` block.
**View Coverage:** Via PHPUnit coverage reports.

## Test Types

**Unit Tests:**
- Focus: Mathematical correctness of monads, value object validation, exception hierarchy.
- Location: `packages/kernel/tests/Unit/`

**Integration Tests:**
- Focus: Event store persistence, concurrency checks, tenant isolation in DB.
- Location: `packages/kernel/tests/Integration/`
- Dependency: Requires PostgreSQL.

**E2E Tests:**
- Focus: Full kernel orchestration.
- Location: `packages/kernel/tests/EndToEnd/`

## Common Patterns

**Async Testing:**
- Not explicitly observed in current primitives; handled via Spiral/RoadRunner in Infrastructure.

**Error Testing:**
```php
$this->expectException(\LogicException::class);
$this->expectExceptionMessage('Cannot get error from a successful result');
$result->error();
```

**Monad Law Testing:**
- Explicit tests for Left Identity, Right Identity, and Associativity in `ResultMonadLawsTest`.

---

*Testing analysis: 2026-04-06*
