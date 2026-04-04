# Coding Conventions

**Analysis Date:** 2026-04-04

## Value Objects

**Pattern:** All value objects extend `ValueObject` base class and are immutable with private constructors and named factory methods.

**Location:** `packages/kernel/src/Domain/Shared/ValueObject/ValueObject.php`

### Structure

Every value object follows this pattern:

```php
final class TenantSlug extends ValueObject
{
    private readonly string $slug;

    private function __construct(string $slug)
    {
        // Validation in constructor
        if ($slug === '') {
            throw new \InvalidArgumentException('TenantSlug cannot be empty');
        }
        $this->slug = $slug;
    }

    public static function fromString(string $slug): self
    {
        // Full validation
        if (strlen($slug) < 3) {
            throw new \InvalidArgumentException('TenantSlug must be at least 3 characters');
        }
        // ... more validation
        return new self($slug);
    }

    public static function tryFromString(string $slug): ?self
    {
        try {
            return self::fromString($slug);
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    public function toString(): string
    {
        return $this->slug;
    }

    protected function valueEquals(ValueObject $other): bool
    {
        \assert($other instanceof self);
        return $this->slug === $other->slug;
    }

    public function __toString(): string
    {
        return $this->slug;
    }
}
```

### Key Rules

1. **Private constructor** - All value objects use `private function __construct()` to enforce validation through factory methods
2. **Readonly properties** - All properties use `private readonly` to ensure immutability
3. **Named factory methods** - Use `fromString()`, `tryFromString()` for creation
4. **Throw vs null** - `fromString()` throws `\InvalidArgumentException` on invalid input; `tryFromString()` returns `null`
5. **Self-validating** - All constraints checked in constructor/factory, not external validators
6. **Value equality** - Override `valueEquals()` for custom comparison logic
7. **String representation** - Implement `__toString()` and `toString()`

### Value Object Files

| File | Purpose |
|------|---------|
| `packages/kernel/src/Domain/Identity/TenantId.php` | Multi-tenant isolation boundary |
| `packages/kernel/src/Domain/Identity/UserId.php` | User identifiers |
| `packages/kernel/src/Domain/Identity/ActorId.php` | Execution context identifiers |
| `packages/kernel/src/Domain/Identity/EventId.php` | Domain event identifiers (UUID v7) |
| `packages/kernel/src/Domain/Identity/CorrelationId.php` | Request correlation |
| `packages/kernel/src/Domain/Identity/CausationId.php` | Event causation chain |
| `packages/kernel/src/Domain/Identity/DocumentId.php` | Document identifiers |
| `packages/kernel/src/Domain/Tenancy/TenantSlug.php` | Human-readable tenant identifiers |
| `packages/kernel/src/Domain/Tenancy/EmailAddress.php` | Validated email addresses |
| `packages/kernel/src/Domain/Tenancy/ResourceReference.php` | Cross-aggregate references |

## Exceptions

**Location:** `packages/kernel/src/Support/Exception/`

### Hierarchy

```
\Exception
└── KernelException (abstract)
    ├── DomainException (abstract)
    │   ├── ValidationException
    │   ├── BusinessRuleViolationException
    │   └── NotFoundException
    ├── AuthorizationException
    └── ConcurrencyConflictException
```

### Exception Types

| Exception | When to Use |
|-----------|-------------|
| `KernelException` | Base class only - never instantiate directly |
| `DomainException` | Business rule violations - abstract, use subclasses |
| `ValidationException` | Input validation failures |
| `BusinessRuleViolationException` | Domain invariant violations |
| `NotFoundException` | Resource not found |
| `AuthorizationException` | Permission denied |
| `ConcurrencyConflictException` | Optimistic concurrency failure |

### Exception Pattern

```php
final class ConcurrencyConflictException extends KernelException
{
    public function __construct(
        private readonly string $aggregateType,
        private readonly string $aggregateId,
        private readonly int $expectedVersion,
        private readonly int $actualVersion,
        ?\Throwable $previous = null
    ) {
        $message = sprintf(
            'Concurrency conflict for %s(%s): expected version %d, found %d',
            $aggregateType,
            $aggregateId,
            $expectedVersion,
            $actualVersion
        );
        parent::__construct($message, 0, $previous);
    }

    public function getErrorCode(): string
    {
        return 'CONCURRENCY_CONFLICT';
    }

    public function getContext(): array
    {
        return [
            'aggregateType' => $this->aggregateType,
            'aggregateId' => $this->aggregateId,
            'expectedVersion' => $this->expectedVersion,
            'actualVersion' => $this->actualVersion,
        ];
    }

    // Accessor methods
    public function getAggregateType(): string { return $this->aggregateType; }
    public function getAggregateId(): string { return $this->aggregateId; }
}
```

### Exception Rules

1. **Machine-readable codes** - Every exception implements `getErrorCode(): string`
2. **Rich context** - Override `getContext(): array` to provide debugging data
3. **Specific constructors** - Use typed parameters, not generic arrays
4. **Accessor methods** - Provide getters for all constructor parameters
5. **Message formatting** - Create descriptive message in constructor

## Result Pattern

**Location:** `packages/kernel/src/Domain/Shared/Result/Result.php`

The Result monad represents success or failure without exceptions. Use for application layer operations where failures are expected outcomes.

### Structure

```php
abstract class Result
{
    public static function success(mixed $value): Success { }
    public static function failure(ErrorDetail $error): Failure { }

    abstract public function isSuccess(): bool;
    abstract public function isFailure(): bool;
    abstract public function unwrap(): mixed;
    abstract public function unwrapOr(mixed $default): mixed;
    abstract public function error(): ErrorDetail;
    abstract public function map(callable $transformer): Result;
    abstract public function flatMap(callable $transformer): Result;
    abstract public function onSuccess(callable $sideEffect): Result;
    abstract public function onFailure(callable $sideEffect): Result;
    abstract public function match(callable $success, callable $failure): mixed;
}
```

### When to Use Result vs Exceptions

| Use Result | Use Exceptions |
|------------|----------------|
| Application service methods | Unrecoverable programming errors |
| Command handler returns | Infrastructure failures |
| Query handler returns | Invalid caller input |
| Domain service operations | Database connection failures |

### ErrorDetail Pattern

```php
// Create simple error
$error = ErrorDetail::create(
    ErrorCode::kernel('NOT_FOUND'),
    'Resource not found'
);

// Create with context
$error = ErrorDetail::withContextData(
    ErrorCode::domainError('ORDER', 'CREDIT_LIMIT'),
    'Credit limit exceeded',
    ['customerId' => 'CUST-001', 'limit' => 10000]
);

// Create validation error
$error = ErrorDetail::validationFailed(
    'Validation failed',
    ['email' => ['Invalid format'], 'password' => ['Too short']]
);
```

### ErrorCode Categories

| Prefix | Domain | Example |
|--------|--------|---------|
| `KERNEL.*` | Infrastructure errors | `KERNEL.CONCURRENCY_CONFLICT` |
| `DOMAIN.*` | Business rule violations | `DOMAIN.ORDER.CREDIT_LIMIT_EXCEEDED` |
| `VALIDATION.*` | Input validation | `VALIDATION.FAILED` |
| `AUTH.*` | Authorization errors | `AUTH.TENANT.ACCESS_DENIED` |

## Naming Conventions

### Files

- **Value Objects:** `{Concept}.php` - e.g., `TenantId.php`, `EmailAddress.php`
- **Exceptions:** `{Concept}Exception.php` - e.g., `ValidationException.php`
- **Tests:** `{Class}Test.php` - e.g., `TenantSlugTest.php`

### Classes

- **Value Objects:** PascalCase noun - `TenantSlug`, `EmailAddress`, `ResourceReference`
- **Exceptions:** PascalCase + `Exception` suffix - `ConcurrencyConflictException`
- **Result types:** `Result`, `Success`, `Failure`

### Methods

- **Factories:** `fromString()`, `tryFromString()`, `create()`, `fromArray()`
- **Accessors:** `toString()`, `toArray()`, `value()`, `code()`
- **Equality:** `equals()` (inherited from `ValueObject`)
- **Hash:** `hash()` (for array/set usage)

### Variables

- **Value objects:** `$tenantId`, `$emailAddress`, `$slug`
- **Result:** `$result`, `$success`, `$failure`
- **Errors:** `$error`, `$errorDetail`

## Import Organization

Imports follow this order:

```php
<?php

declare(strict_types=1);

namespace Spiral\Kernel\Domain\Identity;

use Spiral\Kernel\Domain\Shared\ValueObject\ValueObject;
use Spiral\Kernel\Domain\Shared\Error\ErrorCode;
use function assert;
```

1. Namespace declaration
2. Empty line
3. `use` statements (alphabetically)
4. `use function` statements
5. No `use const` currently used

## Error Handling Patterns

### Value Object Validation

```php
// Throw InvalidArgumentException for invalid input
public static function fromString(string $value): self
{
    if ($value === '') {
        throw new \InvalidArgumentException('Value cannot be empty');
    }
    // Per-constraint validation with specific messages
    if (strlen($value) < 3) {
        throw new \InvalidArgumentException(
            'Value must be at least 3 characters'
        );
    }
    return new self($value);
}
```

### Result Handling

```php
// Using match for branching
$result = $service->execute($command);
return $result->match(
    fn (SuccessData $data) => new SuccessResponse($data),
    fn (ErrorDetail $error) => new ErrorResponse($error)
);

// Chaining operations
$result = $service->execute($command)
    ->map(fn ($data) => $transformer->transform($data))
    ->flatMap(fn ($data) => $nextService->process($data));

// Side effects
$result->onSuccess(fn ($data) => $logger->info('Success'))
       ->onFailure(fn ($error) => $logger->error($error->message()));
```

## Type Safety

- **PHPStan Level 9** enforced via `phpstan.neon`
- **Strict types** - All files start with `declare(strict_types=1);`
- **Generic annotations** - `@template` for Result monad
- **Non-empty-string** - Use PHPDoc `@var non-empty-string` for validated strings
- **Readonly properties** - All value object properties are `private readonly`

---

*Convention analysis: 2026-04-04*