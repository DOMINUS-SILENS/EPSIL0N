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

Follow the checklist in `KERNEL_FOUNDATION_BLUEPRINT_PART5_FINAL.md`:

1. Core Domain Primitives (Value Objects)
2. Result/Error Model
3. Domain Model (Aggregates, Entities, Events)
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