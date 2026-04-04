# Codebase Structure

**Analysis Date:** 2026-04-04

## Directory Layout

```
packages/kernel/
├── src/
│   ├── Domain/                    # Business-law-neutral truth layer
│   │   ├── Shared/                # Shared domain primitives
│   │   │   ├── Aggregate/         # Aggregate root contracts
│   │   │   ├── Entity/            # Entity base classes
│   │   │   ├── ValueObject/       # Value object base
│   │   │   ├── Event/             # Domain event contracts
│   │   │   ├── Result/            # Result monad (Success/Failure)
│   │   │   ├── Error/             # Error types (ErrorCode, ErrorDetail)
│   │   │   ├── Specification/     # Specification pattern
│   │   │   └── Contract/          # Domain contracts
│   │   ├── Identity/              # Identity primitives (TenantId, UserId, ActorId, EventId, etc.)
│   │   ├── Tenancy/               # Tenant governance (TenantSlug, EmailAddress, ResourceReference)
│   │   ├── Authorization/          # Authorization domain concepts
│   │   ├── Temporal/              # Time primitives (BusinessDate, BusinessPeriod, etc.)
│   │   ├── Workflow/              # Workflow state machines
│   │   ├── Approval/              # Approval workflows
│   │   ├── DocumentIdentity/      # Document numbering
│   │   ├── Audit/                 # Audit domain concepts
│   │   ├── Observability/         # Observability primitives
│   │   └── Serialization/         # Domain serialization contracts
│   │
│   ├── Application/               # Orchestration layer
│   │   ├── Contract/              # Application contracts
│   │   │   ├── Command/           # ICommand<TResult> interface
│   │   │   ├── Query/             # IQuery<TResult> interface
│   │   │   ├── Handler/           # ICommandHandler, IQueryHandler interfaces
│   │   │   ├── Validation/        # IValidator, ValidationResult
│   │   │   ├── Authorization/     # IActionRequirement
│   │   │   ├── Idempotency/       # Idempotency contracts
│   │   │   ├── Transaction/       # IUnitOfWork
│   │   │   └── Bus/               # ICommandBus, IQueryBus
│   │   ├── Behavior/              # Cross-cutting behaviors
│   │   │   ├── Validation/        # Validation pipeline behavior
│   │   │   ├── Authorization/     # Authorization pipeline behavior
│   │   │   ├── Idempotency/      # Idempotency pipeline behavior
│   │   │   ├── Transaction/      # Transaction pipeline behavior
│   │   │   ├── Audit/            # Audit pipeline behavior
│   │   │   └── Telemetry/        # Telemetry pipeline behavior
│   │   ├── Policy/                # Authorization policies
│   │   ├── Saga/                  # Saga orchestrators
│   │   └── Service/               # Application services
│   │
│   ├── Infrastructure/            # Implementation layer
│   │   ├── Contract/              # Infrastructure contracts (interfaces only)
│   │   │   ├── Persistence/       # IRepository, ISpecificationRepository
│   │   │   ├── EventStore/        # IEventStore, ISnapshotStore
│   │   │   ├── Eventing/          # IEventDispatcher, IOutboxStore
│   │   │   ├── Security/          # ISecurityContext, IAuthorizationService
│   │   │   ├── Clock/             # IClock, IBusinessCalendar
│   │   │   ├── Serialization/     # IEventSerializer
│   │   │   ├── Audit/             # IAuditTrail
│   │   │   ├── Observability/     # ITracer, IMetrics
│   │   │   └── Diagnostics/       # IReplayVerifier
│   │   ├── Persistence/           # PostgreSQL implementations
│   │   │   ├── Doctrine/          # Doctrine ORM adapters
│   │   │   ├── EventStore/        # Event store implementation
│   │   │   ├── SnapshotStore/     # Snapshot store implementation
│   │   │   ├── Repository/        # Repository implementations
│   │   │   ├── UnitOfWork/        # Unit of work implementation
│   │   │   ├── Projection/        # Projection store
│   │   │   └── Idempotency/       # Idempotency store
│   │   ├── Eventing/              # Event distribution
│   │   │   ├── Dispatcher/        # Event dispatcher
│   │   │   ├── Outbox/            # Transactional outbox
│   │   │   ├── Inbox/             # Message deduplication
│   │   │   ├── Upgrader/          # Event version upgraders
│   │   │   └── Router/            # Event routing
│   │   ├── Security/              # Security implementations
│   │   │   ├── Context/           # Security context
│   │   │   ├── Authorization/     # Authorization service
│   │   │   └── TenantResolution/ # Tenant resolution
│   │   ├── Serialization/         # Serialization implementations
│   │   │   ├── Event/             # Event serialization
│   │   │   ├── ValueObject/       # Value object serialization
│   │   │   └── CanonicalJson/     # Deterministic JSON
│   │   ├── Audit/                 # Audit trail implementation
│   │   ├── Observability/         # Observability implementations
│   │   │   ├── Tracing/           # Distributed tracing
│   │   │   ├── Metrics/           # Metrics collection
│   │   │   └── Logging/            # Logging adapters
│   │   ├── Clock/                 # Clock implementation
│   │   └── Spiral/                # Spiral Framework integration
│   │       ├── Bootloader/        # Service registration
│   │       ├── Interceptor/       # Command/query interceptors
│   │       ├── Middleware/        # HTTP middleware
│   │       ├── Queue/             # Queue consumers
│   │       ├── Console/           # Console commands
│   │       └── Reset/            # Scoped context reset
│   │
│   ├── Diagnostics/               # Verification and compliance
│   │   ├── Replay/                # Replay verification
│   │   ├── Verification/          # Integrity checks
│   │   ├── Compliance/            # Compliance validation
│   │   └── Projection/            # Projection consistency
│   │
│   ├── Support/                   # Cross-cutting utilities
│   │   ├── Exception/             # Exception hierarchy
│   │   ├── Collection/            # Collection utilities
│   │   ├── Utility/               # Generic utilities
│   │   └── TestKit/               # Test helpers
│   │
│   └── Kernel.php                 # Kernel entry point (optional)
│
├── tests/
│   ├── Unit/                      # Unit tests (mirror src structure)
│   │   ├── Domain/
│   │   ├── Application/
│   │   ├── Infrastructure/
│   │   └── Diagnostics/
│   │
│   ├── Integration/               # Integration tests
│   │   ├── EventStore/            # Event store integration
│   │   ├── Outbox/                # Outbox integration
│   │   ├── Replay/                # Replay integration
│   │   ├── Concurrency/           # Concurrency tests
│   │   ├── Tenancy/               # Multi-tenancy tests
│   │   ├── Idempotency/           # Idempotency tests
│   │   ├── Repository/            # Repository tests
│   │   └── Spiral/                # Spiral integration tests
│   │
│   ├── Fixture/                   # Test fixtures
│   │   ├── Aggregate/             # Test aggregates
│   │   ├── Event/                 # Test events
│   │   ├── Projection/            # Test projections
│   │   └── Persistence/           # Test persistence
│   │
│   ├── Smoke/                     # Smoke tests
│   ├── EndToEnd/                  # End-to-end tests
│   └── KernelTestCase.php         # Base test case
│
├── resources/
│   ├── config/                    # Configuration files
│   └── sql/                        # SQL migrations
│       ├── event_store/           # Event store schema
│       ├── outbox/                # Outbox schema
│       ├── inbox/                 # Inbox schema
│       ├── idempotency/           # Idempotency schema
│       └── diagnostics/           # Diagnostics schema
│
├── composer.json                  # PHP dependencies
├── phpunit.xml                    # PHPUnit configuration
├── phpstan.neon                   # PHPStan configuration (level 9)
└── rector.php                     # Rector configuration
```

## Directory Purposes

### Domain/
- **Purpose:** Business-law-neutral truth layer. Defines invariants and domain primitives.
- **Contains:** Value objects, entities, aggregates, domain events, domain services
- **Key Files:**
  - `packages/kernel/src/Domain/Shared/ValueObject/ValueObject.php` - Base value object
  - `packages/kernel/src/Domain/Shared/Result/Result.php` - Result monad
  - `packages/kernel/src/Domain/Identity/TenantId.php` - Tenant isolation boundary

### Application/
- **Purpose:** Orchestration layer. Coordinates use cases, handles commands/queries.
- **Contains:** Commands, queries, handlers, validators, policies, behaviors
- **Key Files:**
  - `packages/kernel/src/Application/Contract/Command/` - Command contracts
  - `packages/kernel/src/Application/Contract/Handler/` - Handler contracts

### Infrastructure/
- **Purpose:** Implementation layer. Concrete implementations of contracts.
- **Contains:** Event store, repositories, persistence, Spiral integration
- **Key Files:**
  - `packages/kernel/src/Infrastructure/Contract/` - Infrastructure interfaces
  - `packages/kernel/src/Infrastructure/Persistence/` - Persistence implementations
  - `packages/kernel/src/Infrastructure/Spiral/Bootloader/` - Framework bootstrapping

### Diagnostics/
- **Purpose:** Verification and compliance. Ensures event-sourcing correctness.
- **Contains:** Replay verification, projection consistency, compliance checks
- **Key Files:** None yet (contracts exist, implementations pending)

### Support/
- **Purpose:** Generic cross-cutting utilities (no domain meaning).
- **Contains:** Exception hierarchy, collection utilities, test kit
- **Key Files:**
  - `packages/kernel/src/Support/Exception/KernelException.php` - Base exception
  - `packages/kernel/src/Support/Exception/DomainException.php` - Domain exceptions

## Key File Locations

### Entry Points
- `packages/kernel/src/Kernel.php` - Kernel entry point (optional, must not become service locator)
- `packages/kernel/src/Infrastructure/Spiral/Bootloader/` - Spiral bootloaders for service registration

### Configuration
- `packages/kernel/composer.json` - PHP dependencies, autoloading
- `packages/kernel/phpunit.xml` - Test configuration
- `packages/kernel/phpstan.neon` - Static analysis configuration (level 9)

### Core Domain Primitives
- `packages/kernel/src/Domain/Shared/ValueObject/ValueObject.php` - Base class for all value objects
- `packages/kernel/src/Domain/Shared/Result/Result.php` - Success/Failure monad
- `packages/kernel/src/Domain/Shared/Error/ErrorCode.php` - Structured error codes
- `packages/kernel/src/Domain/Shared/Error/ErrorDetail.php` - Rich error information

### Identity Primitives
- `packages/kernel/src/Domain/Identity/TenantId.php` - Multi-tenant isolation boundary
- `packages/kernel/src/Domain/Identity/UserId.php` - User identifiers
- `packages/kernel/src/Domain/Identity/ActorId.php` - Execution context (user/system/job)
- `packages/kernel/src/Domain/Identity/EventId.php` - Domain event identifiers (UUID v7)
- `packages/kernel/src/Domain/Identity/CorrelationId.php` - Request correlation
- `packages/kernel/src/Domain/Identity/CausationId.php` - Event causation chain
- `packages/kernel/src/Domain/Identity/DocumentId.php` - Document identifiers

### Governance Primitives
- `packages/kernel/src/Domain/Tenancy/TenantSlug.php` - Human-readable tenant identifiers
- `packages/kernel/src/Domain/Tenancy/EmailAddress.php` - Validated email addresses
- `packages/kernel/src/Domain/Tenancy/ResourceReference.php` - Cross-aggregate references

### Exceptions
- `packages/kernel/src/Support/Exception/KernelException.php` - Base infrastructure exception
- `packages/kernel/src/Support/Exception/DomainException.php` - Domain rule violations
- `packages/kernel/src/Support/Exception/ValidationException.php` - Input validation failures
- `packages/kernel/src/Support/Exception/ConcurrencyConflictException.php` - Optimistic concurrency
- `packages/kernel/src/Support/Exception/AuthorizationException.php` - Authorization denials
- `packages/kernel/src/Support/Exception/BusinessRuleViolationException.php` - Domain invariants
- `packages/kernel/src/Support/Exception/NotFoundException.php` - Resource not found

### Tests
- `packages/kernel/tests/Unit/` - Unit tests (mirror src structure)
- `packages/kernel/tests/Integration/` - Integration tests
- `packages/kernel/tests/Fixture/` - Test fixtures (aggregates, events)

## Naming Conventions

### Files
- **Classes:** PascalCase - `TenantId.php`, `AggregateRoot.php`
- **Interfaces:** IPascalCase prefix - `IRepository.php`, `IEventStore.php`
- **Tests:** Mirror source structure with `Test` suffix - `TenantIdTest.php`

### Namespaces
- **Pattern:** `Spiral\Kernel\{Layer}\{Domain}\{Subdomain}`
- **Examples:**
  - `Spiral\Kernel\Domain\Identity\TenantId`
  - `Spiral\Kernel\Application\Contract\Command\ICommand`
  - `Spiral\Kernel\Infrastructure\Persistence\EventStore\PostgreSQLEventStore`

### Classes
- **Value Objects:** Immutable, self-validating - `TenantId`, `UserId`, `Money`
- **Entities:** Mutable identity - `Entity<TId>`
- **Aggregates:** Event-sourced roots - `AggregateRoot<TId>`
- **Exceptions:** Semantic suffix - `KernelException`, `DomainException`
- **Interfaces:** `I` prefix - `IRepository`, `ICommandHandler`

### Methods
- **Factory methods:** `fromString()`, `generate()`, `create()`
- **Value object equality:** `equals(ValueObject $other): bool`
- **Aggregate state changes:** `raise(DomainEvent $event): void`
- **Aggregate reconstruction:** `reconstituteFrom(array $events): self`

### Variables
- **Pattern:** camelCase
- **Examples:** `$tenantId`, `$aggregateRoot`, `$eventStream`

## Where to Add New Code

### New Value Object
- **Location:** `packages/kernel/src/Domain/{Subdomain}/`
- **Example:** `packages/kernel/src/Domain/Temporal/BusinessDate.php`
- **Extend:** `Spiral\Kernel\Domain\Shared\ValueObject\ValueObject`

### New Exception
- **Location:** `packages/kernel/src/Support/Exception/`
- **Example:** `packages/kernel/src/Support/Exception/NewException.php`
- **Extend:** `KernelException` or `DomainException`

### New Command/Query
- **Location:** `packages/kernel/src/Application/Contract/Command/` or `Query/`
- **Example:** `packages/kernel/src/Application/Contract/Command/ICommand.php`

### New Infrastructure Contract
- **Location:** `packages/kernel/src/Infrastructure/Contract/{Domain}/`
- **Example:** `packages/kernel/src/Infrastructure/Contract/Persistence/IRepository.php`

### New Infrastructure Implementation
- **Location:** `packages/kernel/src/Infrastructure/{Domain}/`
- **Example:** `packages/kernel/src/Infrastructure/Persistence/EventStore/PostgreSQLEventStore.php`

### New Test
- **Unit tests:** `packages/kernel/tests/Unit/` (mirror src structure)
- **Integration tests:** `packages/kernel/tests/Integration/`
- **Example:** `packages/kernel/tests/Unit/Domain/Identity/TenantIdTest.php`

### New Bounded Context Module
- **Location:** `packages/{module}/` (not in kernel)
- **Extend from:** Kernel contracts, base classes
- **Required:** Own `composer.json`, own namespace, own tests

## Special Directories

### resources/sql/
- **Purpose:** Database migration scripts
- **Generated:** No (manual SQL for PostgreSQL schemas)
- **Committed:** Yes
- **Key files:**
  - `event_store/` - Event store table definitions
  - `outbox/` - Transactional outbox tables
  - `idempotency/` - Idempotency key tables

### tests/Fixture/
- **Purpose:** Test fixtures for integration tests
- **Generated:** No (manual test helpers)
- **Committed:** Yes
- **Usage:** Test aggregates, test events for replay verification

### Kernel_Foundation/
- **Purpose:** Blueprint documentation (not code)
- **Generated:** No (architectural specification)
- **Committed:** Yes
- **Key files:**
  - `KERNEL_FOUNDATION_BLUEPRINT_INDEX.md` - Start here
  - `KERNEL_FOUNDATION_BLUEPRINT.md` - Doctrine & Structure
  - `KERNEL_FOUNDATION_BLUEPRINT_PART2.md` - Domain Model
  - `KERNEL_FOUNDATION_BLUEPRINT_PART3.md` - Event Store & App Layer
  - `KERNEL_FOUNDATION_BLUEPRINT_PART4.md` - Governance & Consistency
  - `KERNEL_FOUNDATION_BLUEPRINT_PART5_FINAL.md` - Operations & Checklist

---

*Structure analysis: 2026-04-04*