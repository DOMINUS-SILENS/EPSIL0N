# Testing Patterns

**Analysis Date:** 2026-04-04

## Test Framework

**Runner:** PHPUnit 11.x
**Config:** `packages/kernel/phpunit.xml`

### Run Commands

```bash
# Run all tests
cd packages/kernel && ./vendor/bin/phpunit

# Run unit tests only
cd packages/kernel && ./vendor/bin/phpunit --testsuite Unit

# Run integration tests only
cd packages/kernel && ./vendor/bin/phpunit --testsuite Integration

# Run specific test file
cd packages/kernel && ./vendor/bin/phpunit tests/Unit/Domain/Tenancy/TenantSlugTest.php

# Run with coverage
cd packages/kernel && ./vendor/bin/phpunit --coverage-html coverage/
```

## Test File Organization

**Location:** Co-located with source in `tests/` directory mirroring `src/` structure

```
packages/kernel/tests/
├── KernelTestCase.php                    # Base unit test case
├── Unit/
│   ├── Domain/
│   │   ├── Tenancy/
│   │   │   └── TenantSlugTest.php        # Value object tests
│   │   ├── Identity/
│   │   │   └── TenantIdTest.php          # Identity tests
│   │   ├── Aggregate/
│   │   │   ├── AggregateBehaviorTest.php
│   │   │   └── AggregateStateTest.php
│   │   └── Shared/
│   │       ├── Result/
│   │       │   └── ResultTest.php        # Result monad tests
│   │       ├── Error/
│   │       │   ├── ErrorCodeTest.php
│   │       │   └── ErrorDetailTest.php
│   │       └── ValueObject/
│   │           └── ValueObjectTest.php
│   └── Support/
│       └── KernelExceptionHierarchyTest.php
├── Integration/
│   ├── IntegrationTestCase.php           # Base integration test case
│   ├── DatabaseConnectionTest.php
│   ├── EventStore/
│   │   └── EventStoreTest.php
│   ├── Outbox/
│   │   └── OutboxTest.php
│   ├── Replay/
│   │   └── ReplayTest.php
│   ├── Concurrency/
│   │   └── ConcurrencyTest.php
│   ├── Tenancy/
│   │   └── TenancyTest.php
│   ├── Spiral/
│   │   └── SpiralIntegrationTest.php
│   ├── Idempotency/
│   │   └── IdempotencyTest.php
│   └── Repository/
│       └── RepositoryPersistenceTest.php
├── Fixture/
│   ├── Aggregate/
│   │   └── TestAggregate.php             # Test fixtures
│   ├── Event/
│   │   └── TestEvent.php
│   ├── Projection/
│   │   └── TestProjection.php
│   └── Persistence/
│       └── TestRepository.php
├── EndToEnd/
│   └── KernelOrchestrationTest.php
└── Smoke/
    └── SystemStabilityTest.php
```

### Naming Convention

- Test classes: `{ClassUnderTest}Test.php`
- Test methods: `test{Scenario}{ExpectedResult}.php` (e.g., `testFromStringCreatesValidSlug()`)

## Test Structure

### Base Classes

**Unit Tests:** `packages/kernel/tests/KernelTestCase.php`
```php
namespace Spiral\Kernel\Tests;

use PHPUnit\Framework\TestCase;

class KernelTestCase extends TestCase {}
```

**Integration Tests:** `packages/kernel/tests/Integration/IntegrationTestCase.php`
```php
namespace Spiral\Kernel\Tests\Integration;

use PHPUnit\Framework\TestCase;

abstract class IntegrationTestCase extends TestCase
{
    private static ?\PDO $pdo = null;

    protected function getConnection(): \PDO { /* ... */ }
    protected function skipIfDatabaseNotAvailable(): void { /* ... */ }
    protected function skipIfEventStoreNotAvailable(): void { /* ... */ }
    protected function tearDown(): void { /* truncate tables */ }
}
```

### Test Class Organization

Tests are organized into logical sections using comments:

```php
final class TenantSlugTest extends KernelTestCase
{
    // ========== Creation Tests ==========

    public function testFromStringCreatesValidSlug(): void
    {
        $slug = TenantSlug::fromString('acme-corp');
        $this->assertSame('acme-corp', $slug->toString());
    }

    // ========== Validation Tests - Length ==========

    public function testRejectsTooShortSlug(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('at least 3 characters');

        TenantSlug::fromString('ab');
    }

    // ========== Equality Tests ==========

    public function testEqualsReturnsTrueForSameSlug(): void
    {
        $slug1 = TenantSlug::fromString('acme-corp');
        $slug2 = TenantSlug::fromString('acme-corp');

        $this->assertTrue($slug1->equals($slug2));
    }

    // ========== Immutability Tests ==========

    public function testImmutabilityViaReadonlyProperties(): void
    {
        $reflection = new \ReflectionClass(TenantSlug::class);

        foreach ($reflection->getProperties() as $property) {
            $this->assertTrue(
                $property->isReadOnly(),
                "Property {$property->getName()} should be readonly"
            );
        }
    }
}
```

## Assertion Patterns

### Value Object Assertions

```php
// Basic value assertions
$this->assertSame('expected', $valueObject->toString());

// Type assertions
$this->assertInstanceOf(TenantSlug::class, $result);

// Boolean assertions
$this->assertTrue($slug->equals($otherSlug));
$this->assertFalse($slug->isReserved());

// Null assertions
$this->assertNull(TenantSlug::tryFromString('invalid'));

// Array assertions
$this->assertIsArray(TenantSlug::getReservedSlugs());
$this->assertContains('www', $reserved);
```

### Exception Assertions

```php
// Expecting exception
$this->expectException(\InvalidArgumentException::class);
$this->expectExceptionMessage('cannot be empty');

TenantSlug::fromString('');

// Catching as parent type
try {
    throw new ValidationException(['field' => ['error']]);
} catch (KernelException $e) {
    $this->assertSame($exception, $e);
}
```

### Result Monad Assertions

```php
// Success assertions
$this->assertTrue($result->isSuccess());
$this->assertFalse($result->isFailure());
$this->assertSame('value', $result->unwrap());

// Failure assertions
$this->assertTrue($result->isFailure());
$this->assertSame('KERNEL.NOT_FOUND', $result->error()->code()->code());

// Unwrap throws on failure
$this->expectException(\LogicException::class);
$this->expectExceptionMessage('Cannot unwrap a failed result');
$result->unwrap();
```

## Test Categories

### Unit Tests

**Purpose:** Test individual classes in isolation

**Location:** `tests/Unit/`

**Characteristics:**
- No database or external dependencies
- Fast execution
- Test single class responsibility
- Heavy use of value object assertions

**Example Test Classes:**
- `TenantSlugTest` - Value object validation
- `ResultTest` - Result monad operations
- `KernelExceptionHierarchyTest` - Exception hierarchy

### Integration Tests

**Purpose:** Test component interaction with real infrastructure

**Location:** `tests/Integration/`

**Characteristics:**
- Require PostgreSQL database
- Use `IntegrationTestCase` base class
- Clean up data in `tearDown()`
- Skip if database unavailable

```php
final class EventStoreTest extends IntegrationTestCase
{
    public function testAppendAndRetrieveEvents(): void
    {
        $this->skipIfEventStoreNotAvailable();

        // Test implementation
    }
}
```

### Fixtures

**Location:** `tests/Fixture/`

**Purpose:** Test doubles for integration testing

```php
// TestAggregate.php - Sample aggregate for testing
// TestEvent.php - Sample domain event
// TestProjection.php - Sample projection
// TestRepository.php - Sample repository
```

## Coverage Requirements

**Configuration:** `packages/kernel/phpunit.xml`

```xml
<coverage processUncoveredFiles="true">
    <include>
        <directory suffix=".php">src</directory>
    </include>
</coverage>
```

**Target:** High coverage of value objects and Result monad

**Key Areas Tested:**
1. All factory methods (`fromString`, `tryFromString`, `create`)
2. All validation rules (per-constraint)
3. Edge cases (empty, too long, invalid format)
4. Equality and hash methods
5. Immutability verification
6. Error handling paths

## Mocking

**Current Approach:** No mocks in existing tests - uses real value objects

**When Mocks Would Be Needed:**
- Repository interfaces (not yet implemented)
- External services (not yet implemented)
- Event store (integration tests use real database)

## Static Analysis

**Tool:** PHPStan Level 9

**Config:** `packages/kernel/phpstan.neon`

```yaml
parameters:
    level: 9
    paths:
        - src
        - tests
    excludePaths:
        - src/Support
    treatPhpDocTypesAsCertain: false
```

**Run Command:**
```bash
cd packages/kernel && ./vendor/bin/phpstan analyse
```

## Common Test Patterns

### Value Object Test Template

```php
final class ValueObjectTest extends KernelTestCase
{
    // ========== Creation Tests ==========

    public function testFromStringCreatesValidInstance(): void
    {
        $vo = ValueObject::fromString('valid-value');

        $this->assertInstanceOf(ValueObject::class, $vo);
        $this->assertSame('valid-value', $vo->toString());
    }

    public function testTryFromStringReturnsNullOnInvalidInput(): void
    {
        $vo = ValueObject::tryFromString('invalid');

        $this->assertNull($vo);
    }

    // ========== Validation Tests ==========

    public function testRejectsEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot be empty');

        ValueObject::fromString('');
    }

    // ========== Equality Tests ==========

    public function testEqualsReturnsTrueForSameValue(): void
    {
        $vo1 = ValueObject::fromString('value');
        $vo2 = ValueObject::fromString('value');

        $this->assertTrue($vo1->equals($vo2));
    }

    public function testEqualsReturnsFalseForDifferentValues(): void
    {
        $vo1 = ValueObject::fromString('value1');
        $vo2 = ValueObject::fromString('value2');

        $this->assertFalse($vo1->equals($vo2));
    }

    // ========== Immutability Tests ==========

    public function testAllPropertiesAreReadonly(): void
    {
        $reflection = new \ReflectionClass(ValueObject::class);

        foreach ($reflection->getProperties() as $property) {
            $this->assertTrue(
                $property->isReadOnly(),
                "Property {$property->getName()} should be readonly"
            );
        }
    }
}
```

### Exception Test Template

```php
final class CustomExceptionTest extends KernelTestCase
{
    // ========== Creation Tests ==========

    public function testCreationWithAllParameters(): void
    {
        $exception = new CustomException('param1', 'param2');

        $this->assertStringContainsString('param1', $exception->getMessage());
    }

    // ========== Error Code Tests ==========

    public function testGetErrorCode(): void
    {
        $exception = new CustomException('param');

        $this->assertSame('CUSTOM_ERROR', $exception->getErrorCode());
    }

    // ========== Context Tests ==========

    public function testGetContext(): void
    {
        $exception = new CustomException('param1', 'param2');
        $context = $exception->getContext();

        $this->assertSame('param1', $context['key1']);
        $this->assertSame('param2', $context['key2']);
    }

    // ========== Throwable Behavior Tests ==========

    public function testCanBeCaughtAsParentException(): void
    {
        $exception = new CustomException('param');

        try {
            throw $exception;
        } catch (KernelException $e) {
            $this->assertSame($exception, $e);
        }
    }
}
```

### Result Test Template

```php
final class ResultTest extends KernelTestCase
{
    // ========== Success Tests ==========

    public function testSuccessCreatesSuccessInstance(): void
    {
        $result = Result::success('value');

        $this->assertTrue($result->isSuccess());
        $this->assertFalse($result->isFailure());
    }

    public function testMapTransformsValueOnSuccess(): void
    {
        $result = Result::success(5);
        $mapped = $result->map(fn (int $x): int => $x * 2);

        $this->assertSame(10, $mapped->unwrap());
    }

    // ========== Failure Tests ==========

    public function testFailureCreatesFailureInstance(): void
    {
        $error = ErrorDetail::create(ErrorCode::kernel('ERROR'), 'Error');
        $result = Result::failure($error);

        $this->assertFalse($result->isSuccess());
        $this->assertTrue($result->isFailure());
    }

    public function testMapIsNoOpOnFailure(): void
    {
        $error = ErrorDetail::create(ErrorCode::kernel('ERROR'), 'Error');
        $result = Result::failure($error);

        $mapped = $result->map(fn ($x) => 'transformed');

        $this->assertTrue($mapped->isFailure());
        $this->assertSame($error, $mapped->error());
    }

    // ========== Monad Laws Tests ==========

    public function testLeftIdentityLaw(): void
    {
        $value = 5;
        $f = fn (int $x): Result => Result::success($x * 2);

        $left = Result::success($value)->flatMap($f);
        $right = $f($value);

        $this->assertEquals($left->unwrap(), $right->unwrap());
    }
}
```

## Database Tests

**Integration tests require PostgreSQL with event store tables.**

**Environment Configuration:** (`phpunit.xml`)
```xml
<php>
    <env name="DB_HOST" value="127.0.0.1"/>
    <env name="DB_PORT" value="5432"/>
    <env name="DB_DATABASE" value="epsilone_kernel_test"/>
    <env name="DB_USER" value="postgres"/>
    <env name="DB_PASSWORD" value="password"/>
</php>
```

**Test Isolation:** Each integration test truncates tables in `tearDown()`

---

*Testing analysis: 2026-04-04*