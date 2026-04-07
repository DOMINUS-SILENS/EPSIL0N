# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

EPSILONE ERP Kernel Foundation - a governance substrate for building ERP business modules. This is **not** a utility library but the non-negotiable execution substrate that defines operational laws for all bounded contexts.

**Stack:** PHP 8.3+ | Spiral Framework 3.x | RoadRunner | PostgreSQL
**Architecture:** DDD + OOD + Event-Sourcing Native

## Common Commands

```bash
# Install dependencies
cd packages/kernel && composer install

# Run tests
cd packages/kernel && ./vendor/bin/phpunit

# Run specific test suite
cd packages/kernel && ./vendor/bin/phpunit --testsuite Unit
cd packages/kernel && ./vendor/bin/phpunit --testsuite Integration

# Run static analysis (PHPStan level 9)
cd packages/kernel && ./vendor/bin/phpstan analyse
```

## Project Structure

```
packages/kernel/          # Main kernel package
├── src/                  # Kernel source code (Spiral\Kernel\ namespace)
├── tests/
│   ├── Unit/            # Unit tests
│   ├── Integration/     # Integration tests
│   └── Fixture/         # Test fixtures
├── resources/
│   ├── config/          # Configuration files
│   └── sql/             # SQL migrations
├── composer.json
├── phpunit.xml
└── phpstan.neon

Kernel_Foundation/        # Blueprint documentation
├── KERNEL_FOUNDATION_BLUEPRINT_INDEX.md   # Start here
├── KERNEL_FOUNDATION_BLUEPRINT.md         # Part 1: Doctrine & Structure
├── KERNEL_FOUNDATION_BLUEPRINT_PART2.md   # Part 2: Domain Model
├── KERNEL_FOUNDATION_BLUEPRINT_PART3.md   # Part 3: Event Store & App Layer
├── KERNEL_FOUNDATION_BLUEPRINT_PART4.md   # Part 4: Governance & Consistency
├── KERNEL_FOUNDATION_BLUEPRINT_PART5_FINAL.md  # Part 5: Operations & Checklist
└── KERNEL_SCAFFOLD_EXECUTABLE.md          # Implementation scaffold
```

## Kernel Doctrine

The Kernel provides **structural correctness**, not convenience. Key principles:

### Non-Negotiable Rules

1. **Tenant Isolation is Structural** - Every aggregate has immutable `TenantId`. No cross-tenant reads without explicit authorization.

2. **State Lives Behind Aggregates Only** - No public setters. All mutations via `raise()` method producing domain events.

3. **Authorization in Application Layer** - Auth checks happen BEFORE aggregate invocation, never inside aggregates.

4. **Events are Versioned & Deterministic** - Every event has `schemaVersion`. Events replay deterministically.

5. **Optimistic Concurrency Only** - All aggregates have `Version` field. Updates fail with `ConcurrencyConflictException` on mismatch.

6. **Audit is Automatic** - Every mutating command results in an audit entry via pipeline.

7. **Idempotency by Default** - Command handlers support idempotency keys. Same key = replayed result, not re-execution.

### Directory Structure (Per Blueprint)

```

├/packages/kernel/
├── src/
│   ├── Domain/
│   │   ├── Shared/
│   │   │   ├── Aggregate/
│   │   │   ├── Entity/
│   │   │   ├── ValueObject/
│   │   │   ├── Event/
│   │   │   ├── Result/
│   │   │   ├── Error/
│   │   │   ├── Specification/
│   │   │   └── Contract/
│   │   │
│   │   ├── Identity/
│   │   ├── Tenancy/
│   │   ├── Authorization/
│   │   ├── Temporal/
│   │   ├── Workflow/
│   │   ├── Approval/
│   │   ├── DocumentIdentity/
│   │   ├── Audit/
│   │   ├── Observability/
│   │   └── Serialization/
│   │
│   ├── Application/
│   │   ├── Contract/
│   │   │   ├── Command/
│   │   │   ├── Query/
│   │   │   ├── Handler/
│   │   │   ├── Validation/
│   │   │   ├── Authorization/
│   │   │   ├── Idempotency/
│   │   │   ├── Transaction/
│   │   │   └── Bus/
│   │   │
│   │   ├── Behavior/
│   │   │   ├── Validation/
│   │   │   ├── Authorization/
│   │   │   ├── Idempotency/
│   │   │   ├── Transaction/
│   │   │   ├── Audit/
│   │   │   └── Telemetry/
│   │   │
│   │   ├── Policy/
│   │   ├── Saga/
│   │   └── Service/
│   │
│   ├── Infrastructure/
│   │   ├── Contract/
│   │   │   ├── Persistence/
│   │   │   ├── EventStore/
│   │   │   ├── Eventing/
│   │   │   ├── Security/
│   │   │   ├── Clock/
│   │   │   ├── Serialization/
│   │   │   ├── Audit/
│   │   │   ├── Observability/
│   │   │   └── Diagnostics/
│   │   │
│   │   ├── Persistence/
│   │   │   ├── Doctrine/
│   │   │   ├── EventStore/
│   │   │   ├── SnapshotStore/
│   │   │   ├── Repository/
│   │   │   ├── UnitOfWork/
│   │   │   ├── Projection/
│   │   │   └── Idempotency/
│   │   │
│   │   ├── Eventing/
│   │   │   ├── Dispatcher/
│   │   │   ├── Outbox/
│   │   │   ├── Inbox/
│   │   │   ├── Upgrader/
│   │   │   └── Router/
│   │   │
│   │   ├── Security/
│   │   │   ├── Context/
│   │   │   ├── Authorization/
│   │   │   └── TenantResolution/
│   │   │
│   │   ├── Serialization/
│   │   │   ├── Event/
│   │   │   ├── ValueObject/
│   │   │   └── CanonicalJson/
│   │   │
│   │   ├── Audit/
│   │   ├── Observability/
│   │   │   ├── Tracing/
│   │   │   ├── Metrics/
│   │   │   └── Logging/
│   │   │
│   │   ├── Clock/
│   │   └── Spiral/
│   │       ├── Bootloader/
│   │       ├── Interceptor/
│   │       ├── Middleware/
│   │       ├── Queue/
│   │       ├── Console/
│   │       └── Reset/
│   │
│   ├── Diagnostics/
│   │   ├── Replay/
│   │   ├── Verification/
│   │   ├── Compliance/
│   │   └── Projection/
│   │
│   ├── Support/
│   │   ├── Exception/
│   │   ├── Collection/
│   │   ├── Utility/
│   │   └── TestKit/
│   │
│   └── Kernel.php
│
├── tests/
│   ├── Unit/
│   │   ├── Domain/
│   │   ├── Application/
│   │   ├── Infrastructure/
│   │   └── Diagnostics/
│   │
│   ├── Integration/
│   │   ├── EventStore/
│   │   ├── Outbox/
│   │   ├── Replay/
│   │   ├── Concurrency/
│   │   ├── Tenancy/
│   │   └── Spiral/
│   │
│   ├── Fixture/
│   │   ├── Aggregate/
│   │   ├── Event/
│   │   ├── Projection/
│   │   └── Persistence/
│   │
│   └── KernelTestCase.php
│
├── resources/
│   ├── sql/
│   │   ├── event_store/
│   │   ├── outbox/
│   │   ├── inbox/
│   │   ├── idempotency/
│   │   └── diagnostics/
│   │
│   └── config/
│       └── kernel.php
│
├── composer.json
├── phpstan.neon
├── phpunit.xml
├── rector.php
└── README.md
```

## Implementation Phases

**CRITICAL: Phases 1-2 complete. Next is Phase 2.5 (Runtime Spine), NOT Phase 3.**

The kernel has semantic primitives but no runtime execution. Phase 2.5 establishes the execution membrane between primitives and domain behavior.

### Phase 2.5 — Runtime Spine (CURRENT PRIORITY)
1. `AggregateRoot<TId>` - Event-sourced aggregate base class
2. Event contracts - DomainEvent, EventMetadata, StoredEvent, ExpectedVersion
3. `IEventStore` interface - append/load stream contract
4. PostgreSQL Event Store implementation
5. TenantIsolationViolationException + runtime tenant enforcement
6. Integration tests proving runtime behavior

### Phase 3 — Supporting Primitives (only as needed by Runtime Spine)
- Timestamp (for event timestamps)
- Money/Currency (if event payloads require)

### Phase 4+ (original order, after spine is proven)
4. Application Contracts (Commands, Queries, Handlers)
5. Infrastructure Abstractions (Interfaces only)
6. Spiral Bootloaders
7. PostgreSQL Implementations
8. Database Migrations
9. Testing Infrastructure
10. Diagnostics Tools
11. Documentation
12. Validation

## Key Abstractions

| Abstraction | Purpose |
|-------------|---------|
| `AggregateRoot<TId>` | Event-sourced state container |
| `ValueObject` | Immutable, self-validating primitives |
| `DomainEvent` | Append-only facts with schema version |
| `IRepository<T, TId>` | Aggregate persistence contract |
| `IEventStore` | Event log contract |
| `IOutboxStore` | Event distribution contract |
| `ICommand<TResult>` | Intent to mutate |
| `IAuthorizationService` | Authority verification |
| `IBusinessCalendar` | Temporal governance |

## Mandatory Value Objects

- **Identity:** TenantId, UserId, ActorId, EventId, CorrelationId, CausationId, DocumentId
- **Temporal:** BusinessDate, BusinessPeriod, Timestamp, TimezoneId
- **Financial:** Money, CurrencyCode, Quantity, UnitOfMeasure
- **Governance:** EmailAddress, DocumentNumber, ResourceReference, TenantSlug

## Database Configuration

Copy `.env.example` to `.env` and configure PostgreSQL:

```env
DB_DRIVER=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=epsilone_kernel
DB_USER=postgres
DB_PASSWORD=password
```

## Key Files to Read

- **Architecture decisions:** `Kernel_Foundation/KERNEL_FOUNDATION_BLUEPRINT_INDEX.md`
- **Build order:** `Kernel_Foundation/KERNEL_FOUNDATION_BLUEPRINT_PART2.md` Section 3
- **Implementation scaffold:** `Kernel_Foundation/KERNEL_SCAFFOLD_EXECUTABLE.md`

 The physical file structure for packages/kernel has been created. Here's what was implemented:

  ## Phase 0 — Package Skeleton ✓

  - Complete directory structure per the corrected blueprint
  - composer.json, phpstan.neon, phpunit.xml configured

  ## Phase 1 — Core Failure & Result Semantics ✓

    - src/Support/Exception/ — Exception hierarchy:
    - KernelException.php — Base exception
    - DomainException.php — Domain-layer failures
    - ValidationException.php — Input validation failures
    - ConcurrencyConflictException.php — Optimistic concurrency
    - AuthorizationException.php — Authorization denials
    - BusinessRuleViolationException.php — Domain rule violations
    - NotFoundException.php — Resource not found
    - src/Domain/Shared/Error/ — Error types:
    - ErrorCode.php — Structured error code value object
    - ErrorDetail.php — Rich error information
    - src/Domain/Shared/Result/ — Result monad:
    - Result.php — Success/Failure monad with map, flatMap, match

  ## Phase 2 — Identity & Traceability Primitives ✓

    - src/Domain/Shared/ValueObject/ValueObject.php — Base value object class
     - src/Domain/Identity/ — Identity primitives:
    - TenantId.php — Multi-tenant isolation boundary
    - UserId.php — User identifiers
    - ActorId.php — Execution context (user, system, job)
    - EventId.php — Domain event identifiers (UUID v7)
    - CorrelationId.php — Request correlation
    - CausationId.php — Event causation chain
    - DocumentId.php — Document identifiers
  - src/Domain/Tenancy/ — Governance primitives:
    - TenantSlug.php — Human-readable tenant identifiers
    - EmailAddress.php — Validated email addresses
    - ResourceReference.php — Cross-aggregate resource references

  All code passes PHPStan level 9 analysis. 
  
  Ready to continue with  
  
  ## Phase 3 (Temporal & Numeric Primitives).

<!-- GSD:project-start source:PROJECT.md -->
## Project

**EPSILON Kernel Foundation**

A DDD + Event-Sourcing governance substrate for ERP business modules. Currently at a critical transition point:

- **Phases 0-2 complete:** Semantic primitives (Value Objects, Exceptions, Result monad)
- **Current state:** DDD vocabulary exists, but the system does not execute
- **Goal:** Make the kernel runnable, not broader

**Core Value:** **Transform from semantic substrate → actual ERP kernel**

The kernel must prove it can:
- Create an aggregate
- Persist domain events
- Reload aggregate from event stream
- Enforce optimistic concurrency
- Reject cross-tenant access
- Survive serialization roundtrips

Until these pass real integration tests, the kernel is aspirational, not operational.
<!-- GSD:project-end -->

<!-- GSD:stack-start source:codebase/STACK.md -->
## Technology Stack

## Languages
- PHP 8.3+ - Core implementation language for the entire kernel
- Strict typing with `declare(strict_types=1)` enforced across all source files
- PHPStan level 9 (highest strictness) for static analysis
## Runtime
- RoadRunner 2025.1+ - High-performance PHP application server (PSR-7 compatible)
- Spiral Framework 3.x - Application framework providing DI container, HTTP handling, and lifecycle management
- Composer - PHP dependency manager
- Lockfile: composer.lock present
## Frameworks
- Spiral Framework ^3.0 - Application framework for ERP kernel substrate
- PHPUnit ^11.0 - Test framework for unit and integration tests
- PHPStan ^1.10 - Static analysis tool
## Key Dependencies
- `spiral/framework` ^3.0 - Core framework providing DI, lifecycle, HTTP handling
- `spiral/roadrunner` ^2025.1 - RoadRunner integration for application server
- `nyholm/psr7` ^1.8 - PSR-7 HTTP message implementation
- `ramsey/uuid` ^4.7 - UUID generation (v4 standard, v7 for time-ordered EventId)
- `phpunit/phpunit` ^11.0 - Testing framework
- `phpstan/phpstan` ^1.10 - Static analysis
- `spiral/roadrunner-bridge` ^3.0 - RoadRunner testing bridge
## Configuration
- `.env.example` present - Template for environment configuration
- Environment variables loaded via Spiral bridge
- Test database configuration in `phpunit.xml`:
- `packages/kernel/composer.json` - Dependencies and autoloading
- `packages/kernel/phpstan.neon` - Static analysis configuration
- `packages/kernel/phpunit.xml` - Test configuration
- `packages/kernel/rector.php` - (Planned, not yet present)
- Production: `Spiral\Kernel\` → `src/`
- Testing: `Spiral\Kernel\Tests\` → `tests/`
## Platform Requirements
- PHP 8.3+ runtime
- PostgreSQL 14+ database for integration tests
- RoadRunner application server
- Composer 2.x
- RoadRunner application server (high-performance async PHP)
- PostgreSQL database for event store and projections
- PSR-7 compatible HTTP layer
- Domain layer must NOT depend on Spiral framework (framework-agnostic)
- Domain layer must NOT depend on database implementations
- Domain layer must NOT depend on transport (HTTP, queues)
- Infrastructure layer provides implementations for domain contracts
## Build Tools
# Install dependencies
# Run tests
# Run specific test suites
# Run static analysis (PHPStan level 9)
## File Structure Summary
<!-- GSD:stack-end -->

<!-- GSD:conventions-start source:CONVENTIONS.md -->
## Conventions

## Value Objects
### Structure
### Key Rules
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
### Hierarchy
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
### Exception Rules
## Result Pattern
### Structure
### When to Use Result vs Exceptions
| Use Result | Use Exceptions |
|------------|----------------|
| Application service methods | Unrecoverable programming errors |
| Command handler returns | Infrastructure failures |
| Query handler returns | Invalid caller input |
| Domain service operations | Database connection failures |
### ErrorDetail Pattern
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
## Error Handling Patterns
### Value Object Validation
### Result Handling
## Type Safety
- **PHPStan Level 9** enforced via `phpstan.neon`
- **Strict types** - All files start with `declare(strict_types=1);`
- **Generic annotations** - `@template` for Result monad
- **Non-empty-string** - Use PHPDoc `@var non-empty-string` for validated strings
- **Readonly properties** - All value object properties are `private readonly`
<!-- GSD:conventions-end -->

<!-- GSD:architecture-start source:ARCHITECTURE.md -->
## Architecture

## Pattern Overview
- Domain-centric dependency inversion (Domain depends on nothing external)
- Event-sourced aggregates with mandatory versioning
- Multi-tenancy as structural law (TenantId required on every aggregate)
- Optimistic concurrency with explicit version tokens
- Result monad for explicit error handling
- Idempotency built into command pipeline
- Audit trail automatic and non-negotiable
## Layers
### Domain Layer
- **Purpose:** Business-law-neutral truth layer. Defines invariants, domain primitives, identity, authority semantics, event laws, temporal legality.
- **Location:** `packages/kernel/src/Domain/`
- **Contains:** Value objects, entities, aggregates, domain events, domain contracts, domain services
- **Depends on:** Nothing external (no framework, no ORM, no infrastructure)
- **Used by:** Application layer
### Application Layer
- **Purpose:** Orchestration layer. Coordinates use cases, handles commands/queries, enforces authorization, manages transactions.
- **Location:** `packages/kernel/src/Application/`
- **Contains:** Commands, queries, handlers, validators, policies, sagas, behaviors
- **Depends on:** Domain layer, Infrastructure contracts
- **Used by:** Infrastructure layer (via handlers)
### Infrastructure Layer
- **Purpose:** Implementation layer. Provides concrete implementations of domain/application contracts.
- **Location:** `packages/kernel/src/Infrastructure/`
- **Contains:** Event store, repositories, persistence, serialization, Spiral bootloaders, security adapters
- **Depends on:** Domain contracts, Application contracts, external packages (Spiral, PostgreSQL, RoadRunner)
- **Used by:** Entry points (controllers, console commands, queue consumers)
### Diagnostics Layer
- **Purpose:** Verification and compliance checking. Ensures event-sourcing correctness, replay determinism, audit verification.
- **Location:** `packages/kernel/src/Diagnostics/`
- **Contains:** Replay verification, projection consistency, compliance checks
- **Depends on:** Domain, Infrastructure
- **Used by:** Test harness, operational tooling
### Support Layer
- **Purpose:** Cross-cutting utilities that are truly generic (no domain meaning).
- **Location:** `packages/kernel/src/Support/`
- **Contains:** Exception hierarchy, collection utilities, test kit
- **Depends on:** Nothing
- **Used by:** All layers
## Data Flow
### Command Execution Flow
### Event Flow
```
```
### State Reconstruction Flow
```
```
## Key Abstractions
### AggregateRoot<TId>
- **Purpose:** Event-sourced state container. The only place where business state mutations occur.
- **Examples:** `packages/kernel/src/Domain/Shared/Aggregate/` (contracts)
- **Pattern:** All state changes via `raise()` method, state reconstruction via `When()` method
- **Invariants:**
### ValueObject
- **Purpose:** Immutable, self-validating primitives representing domain concepts.
- **Examples:**
- **Pattern:** Private constructor, factory methods (`fromString()`, `generate()`), value equality
### DomainEvent
- **Purpose:** Append-only facts representing what happened in the domain.
- **Examples:** `packages/kernel/src/Domain/Shared/Event/` (contracts)
- **Pattern:** Immutable record with mandatory metadata (EventId, TenantId, CorrelationId, CausationId, Timestamp, SchemaVersion)
- **Invariants:** Past-tense naming, no mutable references, deterministic serialization
### Result<TData>
- **Purpose:** Explicit success/failure monad for application layer returns.
- **Examples:** `packages/kernel/src/Domain/Shared/Result/Result.php`
- **Pattern:** `Result::success($value)` or `Result::failure($error)`, with `map()`, `flatMap()`, `match()` operations
- **Usage:** Command handlers return Result, never throw for business failures
### IRepository<TAggregate, TId>
- **Purpose:** Aggregate persistence contract. Loads and saves aggregates by ID.
- **Examples:** `packages/kernel/src/Infrastructure/Contract/Persistence/` (interface)
- **Pattern:** `getById($id, TenantId)`, `add($aggregate)`, `save($aggregate)`, `remove($aggregate)`
- **Invariants:** TenantId is mandatory, never optional
### IEventStore
- **Purpose:** Append-only event log contract. Persists and retrieves domain events.
- **Examples:** `packages/kernel/src/Infrastructure/Contract/EventStore/` (interface)
- **Pattern:** `append(TenantId, streamId, expectedVersion, events)`, `getStream(TenantId, streamId, fromVersion)`
- **Invariants:** Concurrency conflict throws `ConcurrencyConflictException`
### ICommand<TResult>
- **Purpose:** Intent to mutate state. Represents what was requested.
- **Examples:** `packages/kernel/src/Application/Contract/Command/` (interface)
- **Pattern:** Data carrier with optional `getIdempotencyKey()`
- **Invariants:** No business logic in command, just data
### IAuthorizationService
- **Purpose:** Verify actor has permission before aggregate invocation.
- **Examples:** `packages/kernel/src/Infrastructure/Contract/Security/` (interface)
- **Pattern:** `authorize(IActionRequirement, ISecurityContext)` throws `AuthorizationException` if denied
- **Invariants:** Authorization BEFORE aggregate method call, never inside aggregate
## Entry Points
### HTTP Controllers (Framework Layer)
- **Location:** Bounded context packages (not in kernel)
- **Purpose:** Convert HTTP requests to commands/queries
- **Triggers:** RoadRunner workers via Spiral HTTP middleware
- **Responsibilities:** Request parsing, command construction, response formatting
### Console Commands
- **Location:** `packages/kernel/src/Infrastructure/Spiral/Console/`
- **Purpose:** CLI operations, maintenance tasks, diagnostics
- **Triggers:** `php spiral command:name`
- **Responsibilities:** Bootstrap kernel, invoke command handlers, output results
### Queue Consumers
- **Location:** `packages/kernel/src/Infrastructure/Spiral/Queue/`
- **Purpose:** Async event processing, saga orchestration
- **Triggers:** RoadRunner queue workers
- **Responsibilities:** Deserialize message, invoke handler, acknowledge/retry
### Kernel Bootloader
- **Location:** `packages/kernel/src/Infrastructure/Spiral/Bootloader/`
- **Purpose:** Service registration, dependency injection container setup
- **Triggers:** Spiral application bootstrap
- **Responsibilities:** Bind interfaces to implementations, configure services
## Error Handling
- `KernelException` - Infrastructure/structural failures (thrown)
- `DomainException` - Business rule violations (thrown for programming errors)
- `Result<T>` - Expected business failures (returned, not thrown)
- `ValidationException` - Input validation failures (thrown at boundary)
- `ConcurrencyConflictException` - Optimistic concurrency version mismatch (thrown)
- `AuthorizationException` - Permission denied (thrown)
- `BusinessRuleViolationException` - Domain invariant violations (thrown)
- `NotFoundException` - Resource not found (thrown)
## Cross-Cutting Concerns
### Logging
- **Approach:** Via Spiral/Psr\Log\LoggerInterface
- **Location:** Infrastructure layer adapters
- **Pattern:** Domain defines no logging, Infrastructure provides implementations
### Validation
- **Approach:** IValidator interface, ValidationResult for errors
- **Location:** Application layer (`Application/Contract/Validation/`)
- **Pattern:** Validate BEFORE command execution, fail fast on invalid input
### Authentication/Authorization
- **Approach:** ISecurityContext for actor context, IAuthorizationService for permission checks
- **Location:** Infrastructure layer (`Infrastructure/Contract/Security/`, `Infrastructure/Security/`)
- **Pattern:** Authorization in Application layer BEFORE aggregate invocation, never inside Domain
### Multi-Tenancy
- **Approach:** TenantId is mandatory on every aggregate and repository operation
- **Location:** Domain layer (`Domain/Identity/TenantId.php`)
- **Pattern:** No ambient global tenant state, explicit tenant on every operation
- **Enforcement:** Repository contracts require TenantId parameter
### Concurrency
- **Approach:** Optimistic versioning on all aggregates
- **Location:** Domain layer (AggregateRoot version tracking)
- **Pattern:** Version mismatch throws `ConcurrencyConflictException`
- **No silent overwrites:** Callers must handle version conflicts explicitly
### Audit
- **Approach:** Automatic on every mutating command
- **Location:** Infrastructure layer (`Infrastructure/Audit/`)
- **Pattern:** Every state change produces audit entry with actor, timestamp, correlation
<!-- GSD:architecture-end -->

<!-- GSD:workflow-start source:GSD defaults -->
## GSD Workflow Enforcement

Before using Edit, Write, or other file-changing tools, start work through a GSD command so planning artifacts and execution context stay in sync.

Use these entry points:
- `/gsd:quick` for small fixes, doc updates, and ad-hoc tasks
- `/gsd:debug` for investigation and bug fixing
- `/gsd:execute-phase` for planned phase work

Do not make direct repo edits outside a GSD workflow unless the user explicitly asks to bypass it.
<!-- GSD:workflow-end -->

<!-- GSD:profile-start -->
## Developer Profile

> Profile not yet configured. Run `/gsd:profile-user` to generate your developer profile.
> This section is managed by `generate-claude-profile` -- do not edit manually.
<!-- GSD:profile-end -->
