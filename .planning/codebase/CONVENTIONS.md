# Coding Conventions

**Analysis Date:** 2026-04-06

## Naming Patterns

**Files:**
- **Value Objects:** `{Concept}.php` - e.g., `TenantId.php`, `Money.php`
- **Exceptions:** `{Concept}Exception.php` - e.g., `ConcurrencyConflictException.php`
- **Tests:** `{Class}Test.php` - e.g., `ResultTest.php`, `TenantIdTest.php`
- **Interfaces:** Prefixed with `I` - e.g., `IEventStore.php`

**Functions/Methods:**
- **Factories:** Static methods using `fromString()`, `generate()`, `create()`
- **Accessors:** `toString()`, `value()`, `code()`
- **Equality:** `equals()` for value objects, `valueEquals()` for internal logic
- **Monadic Operations:** `map()`, `flatMap()`, `match()`, `unwrap()`

**Variables:**
- **Value Objects:** camelCase descriptive names - `$tenantId`, `$correlationId`
- **Results:** `$result`, `$success`, `$failure`
- **Errors:** `$error`, `$errorDetail`

**Types:**
- **Value Objects:** PascalCase nouns - `TenantId`, `BusinessDate`
- **Exceptions:** PascalCase + `Exception` suffix - `KernelException`

## Code Style

**Formatting:**
- **Strict Typing:** `declare(strict_types=1);` is mandatory at the top of every file.
- **Readonly Properties:** All state in Value Objects and Result implementations is `private readonly`.
- **Private Constructors:** Used in Value Objects and Results to enforce the use of factory methods.

**Linting:**
- **PHPStan:** Level 9 enforced.
- **Type Safety:** Heavy use of generics via PHPDoc `@template` (e.g., in `Result<TData>`).
- **Non-empty strings:** Use of `@var non-empty-string` for validated inputs.

## Import Organization

**Order:**
1. Standard PHP extensions
2. Third-party packages (e.g., `Ramsey\Uuid\Uuid`)
3. Internal Kernel namespaces (`Spiral\Kernel\...`)

## Error Handling

**Patterns:**
- **Result Monad:** Used for expected business failures in Application/Domain services.
- **Exceptions:** Used for unrecoverable structural or infrastructure failures.
- **Hierarchy:**
    - `KernelException` $\rightarrow$ Infrastructure/System failures.
    - `DomainException` $\rightarrow$ Business rule violations.
- **Structured Errors:** `ErrorDetail` combined with `ErrorCode` for machine-readable failures.

## Logging

**Framework:** Psr\Log\LoggerInterface (via Spiral)
**Pattern:** Infrastructure layer provides implementations; Domain layer remains agnostic of logging.

## Comments

**When to Comment:**
- Complex architectural invariants (e.g., in `ValueObject.php` and `Result.php`).
- Documenting the "why" behind the design (e.g., "Tenant isolation is structural").

**JSDoc/TSDoc (PHP equivalents):**
- Extensive use of class-level docblocks to define the role of the component within the DDD architecture.

## Function Design

**Size:** Small, single-responsibility methods.
**Parameters:** Strongly typed.
**Return Values:** Explicit return types, often using the `Result` monad for operations that can fail.

## Module Design

**Exports:** Public APIs via interfaces in `Contract` namespaces.
**Barrel Files:** Not observed; direct class imports are preferred.

---

*Convention analysis: 2026-04-06*
