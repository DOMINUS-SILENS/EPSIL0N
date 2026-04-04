# EPSILONE ERP KERNEL FOUNDATION BLUEPRINT — PART 5 (FINAL)

---

# SECTION 12 — AUDIT / OBSERVABILITY / DIAGNOSTICS FOUNDATION

## 12.1 Audit Trail

Audit is immutable traceability. Every mutating operation must leave a trace.

### IAuditTrail

```php
interface IAuditTrail
{
    /**
     * Record an audit entry for a mutating operation.
     */
    public function record(AuditEntry $entry): void;

    /**
     * Retrieve audit entries for an aggregate.
     */
    public function getEntriesForAggregate(
        string $aggregateId,
        TenantId $tenantId
    ): array;

    /**
     * Retrieve audit entries by correlation ID (entire transaction).
     */
    public function getEntriesByCorrelation(CorrelationId $correlationId): array;
}
```

### AuditEntry

```php
final class AuditEntry
{
    public readonly AuditEntryId $id;
    public readonly ActorId $actorId;
    public readonly TenantId $tenantId;
    public readonly string $commandType;
    public readonly string $targetAggregateId;
    public readonly string $targetAggregateType;
    public readonly CorrelationId $correlationId;
    public readonly ?array $stateBefore;  // Previous state (if available)
    public readonly ?array $stateAfter;   // New state
    public readonly Timestamp $occurredAt;
    public readonly BusinessDate $businessDate;  // What business date was this for?
    public readonly ?string $reason;      // Why? (approval reason, cancellation reason, etc.)
    public readonly OutcomeStatus $outcome;  // Success, failure, etc.
    public readonly ?string $errorMessage;

    public function __construct(
        ActorId $actorId,
        TenantId $tenantId,
        string $commandType,
        string $aggregateId,
        string $aggregateType,
        CorrelationId $correlationId,
        ?array $stateBefore,
        ?array $stateAfter,
        BusinessDate $businessDate,
        ?string $reason = null,
        OutcomeStatus $outcome = OutcomeStatus::SUCCESS,
        ?string $errorMessage = null
    ) {
        $this->id = AuditEntryId::generate();
        $this->actorId = $actorId;
        $this->tenantId = $tenantId;
        $this->commandType = $commandType;
        $this->targetAggregateId = $aggregateId;
        $this->targetAggregateType = $aggregateType;
        $this->correlationId = $correlationId;
        $this->stateBefore = $stateBefore;
        $this->stateAfter = $stateAfter;
        $this->occurredAt = Timestamp::now();
        $this->businessDate = $businessDate;
        $this->reason = $reason;
        $this->outcome = $outcome;
        $this->errorMessage = $errorMessage;
    }
}

enum OutcomeStatus
{
    case SUCCESS;
    case FAILED;
    case REJECTED;
    case CANCELLED;
}
```

### PostgreSQL Schema for Audit

```sql
CREATE TABLE audit_log (
    id BIGSERIAL PRIMARY KEY,
    audit_entry_id UUID NOT NULL UNIQUE,
    actor_id UUID NOT NULL,
    tenant_id UUID NOT NULL,
    command_type VARCHAR(255) NOT NULL,
    target_aggregate_id VARCHAR(255) NOT NULL,
    target_aggregate_type VARCHAR(255) NOT NULL,
    correlation_id UUID NOT NULL,
    state_before JSONB,
    state_after JSONB,
    occurred_at TIMESTAMP NOT NULL,
    business_date DATE NOT NULL,
    reason TEXT,
    outcome VARCHAR(50) NOT NULL DEFAULT 'SUCCESS',
    error_message TEXT,
    FOREIGN KEY(tenant_id) REFERENCES tenants(id)
);

CREATE INDEX idx_audit_aggregate ON audit_log(tenant_id, target_aggregate_id);
CREATE INDEX idx_audit_correlation ON audit_log(correlation_id);
CREATE INDEX idx_audit_actor ON audit_log(tenant_id, actor_id);
CREATE INDEX idx_audit_business_date ON audit_log(business_date);
```

## 12.2 Observability / Tracing

### ITracer

```php
interface ITracer
{
    /**
     * Create a span around an operation.
     * Automatically records duration and exceptions.
     */
    public function span(string $name, callable $operation): mixed;

    /**
     * Set current context (correlation, tenant, actor).
     */
    public function setContext(TracingContext $context): void;

    /**
     * Add a tag/label to current span.
     */
    public function addTag(string $key, string $value): void;

    /**
     * Record an event.
     */
    public function recordEvent(string $event, array $attributes = []): void;
}

final class TracingContext
{
    public function __construct(
        public readonly CorrelationId $correlationId,
        public readonly TenantId $tenantId,
        public readonly ActorId $actorId,
    ) {}
}
```

### IMetrics

```php
interface IMetrics
{
    /**
     * Increment a counter.
     */
    public function increment(string $name, int $count = 1, array $tags = []): void;

    /**
     * Record a gauge (instantaneous value).
     */
    public function gauge(string $name, int $value, array $tags = []): void;

    /**
     * Record a histogram (distribution).
     */
    public function histogram(string $name, int $value, array $tags = []): void;
}
```

### ILogger

```php
interface ILogger
{
    public function debug(string $message, array $context = []): void;
    public function info(string $message, array $context = []): void;
    public function warning(string $message, array $context = []): void;
    public function error(string $message, array $context = []): void;
}
```

### Required Trace Points

Command handlers must emit traces at:

```
Command received
  ├─ Command validated
  ├─ Authorization checked
  ├─ Idempotency checked
  ├─ Transaction started
  ├─ Aggregate loaded
  ├─ Command executed
  │  └─ Events raised
  ├─ Aggregate saved
  ├─ Events appended to store
  ├─ Outbox message enqueued
  ├─ Audit entry recorded
  ├─ Transaction committed
  └─ Command completed
```

Each span should include:
- `tenant_id`
- `actor_id`
- `correlation_id`
- `command_type`
- `duration_ms`
- `success` (true/false)

## 12.3 Diagnostics / Replay Verification

The Kernel must support verifiable correctness.

### IReplayVerifier

```php
interface IReplayVerifier
{
    /**
     * Verify aggregate state by replaying events.
     * Compares replayed state against persisted snapshot.
     */
    public function verifyAggregateReplay(
        TenantId $tenantId,
        string $aggregateId
    ): ReplayVerificationResult;

    /**
     * Verify all aggregates in a tenant can be replayed correctly.
     */
    public function verifyTenantReplay(
        TenantId $tenantId,
        ?int $sampleSize = null
    ): array;

    /**
     * Verify event serialization is deterministic.
     */
    public function verifyEventSerialization(): EventSerializationVerificationResult;
}

final class ReplayVerificationResult
{
    public bool $isValid;
    public string $aggregateId;
    public ulong $eventCount;
    public string $replayedStateHash;
    public string $persistedStateHash;
    public ?string $errorMessage;
    public int $durationMs;
}
```

### IProjectionVerifier

```php
interface IProjectionVerifier
{
    /**
     * Rebuild a projection from scratch and verify it matches.
     */
    public function verifyProjection(string $projectionName): ProjectionVerificationResult;

    /**
     * Rebuild all projections and verify consistency.
     */
    public function verifyAllProjections(): array;
}
```

**Usage:**

```php
// In diagnostic command
$verifier = $container->get(IReplayVerifier::class);
$result = $verifier->verifyTenantReplay($tenantId);

foreach ($result as $verification) {
    if (!$verification->isValid) {
        echo "ERROR: {$verification->aggregateId} - {$verification->errorMessage}";
    }
}
```

---

# SECTION 13 — SPIRAL-NATIVE INTEGRATION STRATEGY

## 13.1 Spiral Bootloader Architecture

Bootloaders are Spiral's configuration system. They register services, define middleware, etc.

### KernelBootloader

Root bootloader that boots all kernel services.

```php
namespace Spiral\Kernel\Infrastructure\Spiral\Bootloader;

use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Boot\Bootloader\DependencyResolver;
use Spiral\Core\Container;

final class KernelBootloader extends Bootloader
{
    protected const LOAD = [
        \Spiral\Config\ConfigLoader::class,
    ];

    protected const SINGLETONS = [
        // Value object factories
        'tenant_id_factory' => \Spiral\Kernel\Domain\Shared\Identity\TenantIdFactory::class,

        // Temporal services
        IClock::class => SystemClock::class,
        IBusinessCalendar::class => PostgresBusinessCalendar::class,

        // Security
        ISecurityContext::class => Middleware\AmbientSecurityContext::class,  // per-request
        IAuthorizationService::class => PostgresAuthorizationService::class,
        ITenantResolver::class => AmbientTenantResolver::class,

        // Observability (singletons)
        ITracer::class => SpiralTracer::class,
        IMetrics::class => PrometheusMetrics::class,
        ILogger::class => SpiralLogger::class,

        // Diagnostics
        IReplayVerifier::class => PostgresReplayVerifier::class,
        IProjectionVerifier::class => PostgresProjectionVerifier::class,
    ];

    public function boot(Container $container): void
    {
        // Additional initialization if needed
    }
}
```

### PersistenceBootloader

```php
final class PersistenceBootloader extends Bootloader
{
    protected const SINGLETONS = [
        IEventStore::class => PostgresEventStore::class,
        ISnapshotStore::class => PostgresSnapshotStore::class,
        IOutboxStore::class => PostgresOutboxStore::class,
        IProcessedMessageStore::class => PostgresProcessedMessageStore::class,
        IIdempotencyStore::class => PostgresIdempotencyStore::class,
        IUnitOfWork::class => PostgresUnitOfWork::class,
        IAuditTrail::class => PostgresAuditTrail::class,
    ];
}
```

### ApplicationBootloader

Registers command/query handlers.

```php
final class ApplicationBootloader extends Bootloader
{
    public function boot(Container $container): void
    {
        // Register command handlers
        $this->registerCommandHandlers($container);

        // Register query handlers
        $this->registerQueryHandlers($container);

        // Register validators
        $this->registerValidators($container);

        // Register authorization policies
        $this->registerAuthorizationPolicies($container);
    }

    private function registerCommandHandlers(Container $container): void
    {
        // Each module provides its handlers to a registry
        // Kernel doesn't know specific handlers, just the interface
        // $container->bind(ICommandHandler::class, ...);
    }
}
```

## 13.2 Middleware Pipeline

Spiral uses middleware for cross-cutting concerns.

### SecurityContextMiddleware

Resolves tenant and actor from request context.

```php
final class SecurityContextMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ITenantResolver $tenantResolver,
        private ISecurityContextFactory $contextFactory,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $tenantId = $this->tenantResolver->resolveTenant();
        $actorId = $this->extractActorFromRequest($request);

        $securityContext = $this->contextFactory->create($tenantId, $actorId);

        // Make ambient for this request
        ...
        // Store in container with per-request scope
        ...

        return $handler->handle($request);
    }

    private function extractActorFromRequest(ServerRequestInterface $request): ActorId
    {
        // Parse JWT, session, header, depending on auth strategy
        ...
    }
}
```

### CorrelationMiddleware

Links all operations in a request to a correlation ID.

```php
final class CorrelationMiddleware implements MiddlewareInterface
{
    public function __construct(private ITracer $tracer) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $correlationId = $request->getHeaderLine('X-Correlation-ID') ?: CorrelationId::generate();

        $this->tracer->setContext(new TracingContext($correlationId, ...));

        return $handler->handle($request);
    }
}
```

### AuditContextMiddleware

Sets audit context for this request.

```php
final class AuditContextMiddleware implements MiddlewareInterface
{
    public function __construct(private IAuditTrail $audit) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Audit middleware runs after security middleware
        // So security context is already set
        ...
        return $handler->handle($request);
    }
}
```

## 13.3 RoadRunner Integration

RoadRunner is Spiral's async runtime based on Go.

### Request Lifecycle (Per Request)

```
RoadRunner receives HTTP request
  ↓
PSR-7 ServerRequest created
  ↓
Middleware pipeline
  ├─ SecurityContextMiddleware (parse JWT, set tenant/actor)
  ├─ CorrelationMiddleware (generate correlation ID)
  ├─ AuditContextMiddleware (prepare audit)
  └─ Your handler middleware
  ↓
Handler (Controller) processes request
  ├─ Extracts command/query DTO
  ├─ Gets handler from container
  ├─ Invokes handler
  └─ Handler uses kernel services
  ↓
Response returned
  ↓
Scoped services cleaned up
  ↓
RoadRunner sends response
```

### Per-Request Scoped Services

SecurityContext must be per-request, not singleton.

```php
final class KernelBootloader extends Bootloader
{
    protected const BINDINGS = [
        ISecurityContext::class => Factories\SecurityContextFactory::class,
    ];
}
```

## 13.4 Console Command Integration

For background jobs, queue consumers, diagnostics commands.

```php
#[\Spiral\Attributes\Command]
final class VerifyKernelCommand
{
    public function __construct(
        private IReplayVerifier $verifier,
        private IProjectionVerifier $projectionVerifier,
    ) {}

    #[\Spiral\Attributes\Argument(name: 'tenant')]
    #[\Spiral\Attributes\Option(name: 'sample', description: 'Sample size')]
    public function perform(string $tenant, ?int $sample): void
    {
        $tenantId = new TenantId($tenant);

        echo "Verifying event replay...\n";
        $results = $this->verifier->verifyTenantReplay($tenantId, $sample);

        foreach ($results as $result) {
            if ($result->isValid) {
                echo "✓ {$result->aggregateId}\n";
            } else {
                echo "✗ {$result->aggregateId}: {$result->errorMessage}\n";
            }
        }
    }
}
```

## 13.5 Testing Bootstrap

The kernel must be testable without HTTP context.

```php
final class KernelTestCase extends TestCase
{
    protected Container $container;

    protected function setUp(): void
    {
        parent::setUp();

        // Create minimal container with kernel services
        $this->container = $this->createKernelContainer();
    }

    protected function createKernelContainer(): Container
    {
        $container = new Container();

        // Register all kernel services
        $bootloader = new KernelBootloader();
        $bootloader->boot($container);

        $persistence = new PersistenceBootloader();
        $persistence->boot($container);

        return $container;
    }

    protected function createOrder(TenantId $tenantId, ...): Order
    {
        return Order::create(...);
    }

    protected function saveAggregate(AggregateRoot $agg): void
    {
        $repo = $this->container->get(IOrderRepository::class);
        $repo->save($agg);
    }
}
```

---

# SECTION 14 — FOUNDATION IMPLEMENTATION CHECKLIST

## Executable, Ordered Checklist

This is what you build, in order, to implement the Kernel.

### PHASE 0: PACKAGE SKELETON

- [ ] Create `/packages/kernel/` directory structure
- [ ] Create `composer.json` with PSR-4 autoload for `Spiral\Kernel\` namespace
- [ ] Create `phpunit.xml` with Unit and Integration test suites
- [ ] Create `phpstan.neon` with level 9 configuration
- [ ] Create `.env.example` with database connection template

### PHASE 1: FAILURE & RESULT SEMANTICS

- [ ] Create `src/Support/Exception/KernelException.php`
- [ ] Create `src/Support/Exception/ValidationException.php`
- [ ] Create `src/Support/Exception/ConcurrencyConflictException.php`
- [ ] Create `src/Support/Exception/AuthorizationException.php`
- [ ] Create `src/Support/Exception/NotFoundException.php`
- [ ] Create `src/Support/Exception/TenantIsolationViolationException.php`
- [ ] Create `src/Support/Exception/BusinessRuleViolationException.php`
- [ ] Create `src/Domain/Shared/Error/DomainError.php`
- [ ] Create `src/Domain/Shared/Error/ValidationError.php`
- [ ] Create `src/Domain/Shared/Error/BusinessRuleViolation.php`
- [ ] Create `src/Domain/Shared/Error/ConcurrencyConflict.php`
- [ ] Create `src/Domain/Shared/Error/AuthorizationDenied.php`
- [ ] Create `src/Domain/Shared/Error/NotFoundError.php`
- [ ] Create `src/Domain/Shared/Error/ClosedPeriodViolation.php`
- [ ] Create `src/Domain/Shared/Error/InvalidStateTransition.php`
- [ ] Create `src/Domain/Shared/Result.php`

### PHASE 2: VALUE OBJECT FOUNDATION

- [ ] Create `src/Domain/Shared/ValueObject.php` (abstract base)
- [ ] Create identity VOs:
  - [ ] `src/Domain/Shared/Identity/TenantId.php`
  - [ ] `src/Domain/Shared/Identity/UserId.php`
  - [ ] `src/Domain/Shared/Identity/ActorId.php`
  - [ ] `src/Domain/Shared/Identity/EventId.php`
  - [ ] `src/Domain/Shared/Identity/CorrelationId.php`
  - [ ] `src/Domain/Shared/Identity/CausationId.php`
  - [ ] `src/Domain/Shared/Identity/DocumentId.php`
- [ ] Create governance VOs:
  - [ ] `src/Domain/Shared/Governance/EmailAddress.php`
  - [ ] `src/Domain/Shared/Governance/DocumentNumber.php`
  - [ ] `src/Domain/Shared/Governance/ResourceReference.php`
  - [ ] `src/Domain/Shared/Governance/TenantSlug.php`

### PHASE 3: TEMPORAL & NUMERIC PRIMITIVES

- [ ] Create temporal VOs:
  - [ ] `src/Domain/Temporal/Timestamp.php`
  - [ ] `src/Domain/Temporal/BusinessDate.php`
  - [ ] `src/Domain/Temporal/BusinessPeriod.php`
  - [ ] `src/Domain/Temporal/EffectiveDateRange.php`
  - [ ] `src/Domain/Temporal/TimezoneId.php`
- [ ] Create financial VOs:
  - [ ] `src/Domain/Shared/Financial/Money.php`
  - [ ] `src/Domain/Shared/Financial/CurrencyCode.php`
  - [ ] `src/Domain/Shared/Measurement/Quantity.php`
  - [ ] `src/Domain/Shared/Measurement/UnitOfMeasure.php`
  - [ ] `src/Domain/Shared/Financial/Percentage.php`
  - [ ] `src/Domain/Shared/Financial/Rate.php`

### PHASE 4: EVENT LAW

- [ ] Create `src/Domain/Shared/Event/DomainEvent.php`
- [ ] Create `src/Domain/Shared/Event/EventMetadata.php`
- [ ] Create `src/Domain/Shared/Event/EventEnvelope.php`
- [ ] Create `src/Domain/Shared/Event/EventSchemaVersion.php`

### PHASE 5: AGGREGATE LAW

- [ ] Create `src/Domain/Shared/Entity.php`
- [ ] Create `src/Domain/Shared/AggregateRoot.php`
- [ ] Create `src/Domain/Shared/Contract/IHasDomainEvents.php`

### PHASE 6: DOMAIN CONTRACTS

- [ ] Create persistence contracts:
  - [ ] `src/Infrastructure/Contract/Persistence/IRepository.php`
  - [ ] `src/Infrastructure/Contract/Persistence/ISpecificationRepository.php`
  - [ ] `src/Infrastructure/Contract/Persistence/IUnitOfWork.php`
- [ ] Create security contracts:
  - [ ] `src/Infrastructure/Contract/Security/ISecurityContext.php`
  - [ ] `src/Infrastructure/Contract/Security/IAuthorizationService.php`
  - [ ] `src/Infrastructure/Contract/Security/IActionRequirement.php`
- [ ] Create temporal contracts:
  - [ ] `src/Infrastructure/Contract/Temporal/IBusinessCalendar.php`
  - [ ] `src/Infrastructure/Contract/Clock/IClock.php`
- [ ] Create observability contracts:
  - [ ] `src/Infrastructure/Contract/Observability/IAuditTrail.php`
  - [ ] `src/Infrastructure/Contract/Observability/ITracer.php`
  - [ ] `src/Infrastructure/Contract/Observability/IMetrics.php`
  - [ ] `src/Infrastructure/Contract/Observability/ILogger.php`

### PHASE 7: APPLICATION CONTRACTS

- [ ] Create `src/Application/Contract/Command/ICommand.php`
- [ ] Create `src/Application/Contract/Query/IQuery.php`
- [ ] Create `src/Application/Contract/Handler/ICommandHandler.php`
- [ ] Create `src/Application/Contract/Handler/IQueryHandler.php`
- [ ] Create `src/Application/Contract/Validation/IValidator.php`
- [ ] Create `src/Application/Contract/Validation/ValidationResult.php`
- [ ] Create `src/Application/Contract/Bus/ICommandBus.php`
- [ ] Create `src/Application/Contract/Bus/IQueryBus.php`

### PHASE 8: DELIVERY SAFETY CONTRACTS

- [ ] Create `src/Infrastructure/Contract/EventStore/IEventStore.php`
- [ ] Create `src/Infrastructure/Contract/EventStore/ISnapshotStore.php`
- [ ] Create `src/Infrastructure/Contract/Eventing/IEventSerializer.php`
- [ ] Create `src/Infrastructure/Contract/Eventing/IOutboxStore.php`
- [ ] Create `src/Infrastructure/Contract/Eventing/IProcessedMessageStore.php`
- [ ] Create `src/Infrastructure/Contract/Eventing/IIdempotencyStore.php`

### PHASE 9: KERNEL-OWNED GOVERNANCE MODELS

- [ ] Create `src/Domain/Tenancy/Tenant.php` (minimal aggregate)
- [ ] Create `src/Domain/Tenancy/Events/TenantCreated.php`
- [ ] Create `src/Domain/Identity/Actor.php`
- [ ] Create `src/Domain/Identity/Events/ActorProvisioned.php`
- [ ] Create `src/Domain/Workflow/LifecycleState.php`
- [ ] Create `src/Domain/Workflow/TransitionRule.php`

### PHASE 10: APPLICATION BEHAVIORS

- [ ] Create `src/Application/Behavior/Validation/ValidationBehavior.php`
- [ ] Create `src/Application/Behavior/Authorization/AuthorizationBehavior.php`
- [ ] Create `src/Application/Behavior/Idempotency/IdempotencyBehavior.php`
- [ ] Create `src/Application/Behavior/Transaction/TransactionBehavior.php`
- [ ] Create `src/Application/Behavior/Audit/AuditBehavior.php`
- [ ] Create `src/Application/Behavior/Telemetry/TelemetryBehavior.php`

### PHASE 11: DIAGNOSTICS CONTRACTS

- [ ] Create `src/Infrastructure/Contract/Diagnostics/IReplayVerifier.php`
- [ ] Create `src/Infrastructure/Contract/Diagnostics/IProjectionVerifier.php`
- [ ] Create `src/Diagnostics/Replay/ReplayVerificationResult.php`
- [ ] Create `src/Diagnostics/Verification/ProjectionVerificationResult.php`

### PHASE 12: POSTGRESQL IMPLEMENTATIONS

- [ ] Create `src/Infrastructure/Persistence/EventStore/PostgresEventStore.php`
- [ ] Create `src/Infrastructure/Persistence/SnapshotStore/PostgresSnapshotStore.php`
- [ ] Create `src/Infrastructure/Persistence/UnitOfWork/PostgresUnitOfWork.php`
- [ ] Create `src/Infrastructure/Persistence/Repository/GenericRepository.php`
- [ ] Create `src/Infrastructure/Eventing/Outbox/PostgresOutboxStore.php`
- [ ] Create `src/Infrastructure/Eventing/Inbox/PostgresProcessedMessageStore.php`
- [ ] Create `src/Infrastructure/Eventing/Idempotency/PostgresIdempotencyStore.php`
- [ ] Create `src/Infrastructure/Serialization/Event/JsonEventSerializer.php`
- [ ] Create `src/Infrastructure/Observability/Audit/PostgresAuditTrail.php`
- [ ] Create `src/Infrastructure/Clock/SystemClock.php`

### PHASE 13: SPIRAL BOOTLOADERS

- [ ] Create `src/Infrastructure/Spiral/Bootloader/KernelBootloader.php`
- [ ] Create `src/Infrastructure/Spiral/Bootloader/PersistenceBootloader.php`
- [ ] Create `src/Infrastructure/Spiral/Bootloader/ApplicationBootloader.php`
- [ ] Create `src/Infrastructure/Spiral/Bootloader/TemporalBootloader.php`
- [ ] Create `src/Infrastructure/Spiral/Bootloader/SecurityBootloader.php`
- [ ] Create `src/Infrastructure/Spiral/Bootloader/ObservabilityBootloader.php`
- [ ] Create `src/Infrastructure/Spiral/Middleware/SecurityContextMiddleware.php`
- [ ] Create `src/Infrastructure/Spiral/Middleware/CorrelationMiddleware.php`

### PHASE 14: DATABASE MIGRATIONS

- [ ] Create migration: `domain_streams` table
- [ ] Create migration: `domain_events` table
- [ ] Create migration: `domain_snapshots` table
- [ ] Create migration: `outbox_messages` table
- [ ] Create migration: `inbox_processed_messages` table
- [ ] Create migration: `idempotency_records` table
- [ ] Create migration: `audit_log` table
- [ ] Create migration: `tenants` table
- [ ] Create migration: `actors` table

### PHASE 15: TESTING INFRASTRUCTURE

- [ ] Create `tests/KernelTestCase.php`
- [ ] Create `tests/Unit/Domain/ValueObjectTest.php`
- [ ] Create `tests/Unit/Domain/AggregateRootTest.php`
- [ ] Create `tests/Integration/EventStore/EventStoreTest.php`
- [ ] Create `tests/Integration/Repository/RepositoryTest.php`
- [ ] Create `tests/Integration/Audit/AuditTrailTest.php`
- [ ] Create `tests/Integration/EventSourcing/ReplayVerificationTest.php`

### PHASE 16: DIAGNOSTICS TOOLS

- [ ] Create `src/Infrastructure/Persistence/Diagnostics/PostgresReplayVerifier.php`
- [ ] Create `src/Infrastructure/Persistence/Diagnostics/PostgresProjectionVerifier.php`
- [ ] Create console command: `VerifyKernelCommand`
- [ ] Create console command: `ReplayAggregateCommand`

---

## Seven Closure Gates

The Kernel is complete only when **all seven closure conditions** are true.

### 1) Structural Completion

**Required:** All 14 kernel sections exist as **real implementation**, not placeholders.

**Completion signal:** No "TODO architecture" remains in the kernel root.

**Failure condition:** If any bounded context must invent its own base aggregate, result wrapper, idempotency pattern, event metadata, or audit behavior, then the kernel is **not complete**.

### 2) Behavioral Correctness

**Required:** Tests must prove the kernel behaves correctly under expected use.

**Minimum mandatory test suites:**

#### Unit
* all VOs
* all equality semantics
* all invariant guards
* all result/error semantics
* aggregate event emission
* aggregate replay
* policy behavior
* saga fold behavior

#### Integration
* event store append/load
* snapshot load/rebuild
* repository save/load
* optimistic concurrency conflict
* outbox write-on-commit
* inbox/idempotency dedupe
* tenant-scoped persistence
* authorization boundary enforcement

#### Deterministic replay
* full aggregate replay from genesis
* projection rebuild from event store
* event upgrader compatibility
* serializer canonical byte stability

**Completion signal:** No kernel behavior is "assumed by convention."

**Failure condition:** If correctness depends on "developer discipline," kernel is incomplete.

### 3) Consumability by a New Bounded Context

**Mandatory proof module:** Build one disposable validation context at `packages/modules/example-context/`.

It must prove:
* defines its own aggregate using `AggregateRoot`
* defines its own VOs using kernel base types
* emits domain events using kernel event law
* persists through kernel repository contracts
* uses kernel command/query contracts
* inherits audit automatically
* inherits idempotency automatically
* inherits tenant scoping automatically
* inherits tracing automatically

**Completion signal:** The module contains **only business logic**, not reimplementation of kernel mechanics.

**Failure condition:** If the first consumer has to build custom transaction wrapper, custom event metadata, custom tenant resolver, or custom replay logic, then kernel is incomplete.

### 4) Determinism and Replayability

**Level A — Aggregate determinism:** `genesis events → replay → exact same state`

**Level B — Projection determinism:**
```
truncate → replay all events → state hash H1
truncate → replay all events → state hash H2
assert H1 == H2
```

**Level C — Serialization determinism:** `same logical event → same canonical bytes → same hash`

**Completion signal:** No clock drift, unordered iteration, or nondeterministic serialization contaminates replay.

**Failure condition:** If replay produces equivalent state "most of the time," kernel is incomplete.

### 5) Tenant Isolation and Security Closure

**Required guarantees:**

#### Tenant isolation
* every aggregate carries `TenantId`
* every event carries `TenantId`
* every repository query requires tenant scope
* every projector writes tenant-scoped rows
* every command executes under a resolved tenant context
* tenantless writes are impossible except explicitly whitelisted system flows

#### Authorization
* every mutating operation passes authorization gate
* every actor is explicit
* every command is attributable
* privileged actions are auditable

#### Traceability
* every command has: actor, tenant, correlation id, causation id, timestamp

**Completion signal:** Unauthorized or cross-tenant access fails by architecture, not by developer memory.

**Failure condition:** If one forgotten `WHERE tenant_id = ?` can leak data, kernel is incomplete.

### 6) Operational Safety Closure

**Required:** Kernel must survive real failure modes:
* duplicate delivery
* retry storms
* partial crashes
* worker restarts
* concurrent writes
* out-of-order processing where applicable
* dead-letter conditions

**Required proof points:**

#### Idempotency
* same command retried N times → one logical effect

#### Outbox durability
* DB commit succeeds, publisher crashes → event still eventually publishes

#### Inbox safety
* same event delivered N times → one logical consumption effect

#### Concurrency safety
* two writers race same aggregate → one succeeds, one fails cleanly

#### Recovery
* process crash mid-pipeline → system recovers without corruption

**Completion signal:** Operational correctness does not depend on "the worker usually behaves."

**Failure condition:** If duplicate execution can corrupt state, kernel is incomplete.

### 7) Governance Closure

**Required governance guarantees:**

#### Audit inevitability
A successful command affecting domain state automatically creates an audit trail when applicable.

#### Temporal legality
Illegal temporal actions are impossible:
* backdating outside policy
* posting into closed period
* future-effective contradictions
* invalid effective-date overlap

#### Authority explicitness
Every state transition must answer: who did it, under what permission, under what tenant, at what time, due to what cause.

#### Compliance traceability
Every domain fact can be traced to its aggregate, event stream, originating command, actor, and causal chain.

**Completion signal:** Governance is structural, not dependent on "good developers."

**Failure condition:** If a module can bypass audit, bypass temporal rules, or create unauthenticated state changes, kernel is incomplete.

---

## Final Completion Definition

| Closure Gate | Meaning |
| ------------ | ------- |
| Structural Completion | All 14 sections exist concretely |
| Behavioral Correctness | Tests prove all core behaviors |
| Consumability | A new bounded context can use it without reinventing infrastructure law |
| Determinism | Replay, projections, and serialization are reproducible |
| Tenant & Security Closure | Isolation and authorization are mechanically enforced |
| Operational Safety | Retries, duplicates, crashes, and races do not corrupt the system |
| Governance Closure | Audit, authority, temporal legality, and traceability are unavoidable |

---

## After Kernel Completion

### Stage 1 — Validation Context

Build one intentionally small but complete bounded context. Good candidates:
* `FinancialPeriod`
* `DocumentIdentity`
* `ApprovalWorkflow`
* `OrganizationalUnit`

**Why:** You need a **kernel stress test**, not a giant module.

### Stage 2 — First Serious Core Context

Build one of:
* Financial Core
* Inventory Core

**Why:** These introduce temporal legality, concurrency pressure, identity discipline, auditability, and traceability. This is where weak kernels break.

### Stage 3 — Cross-Context Validation

Only after two real contexts exist should you validate:
* shared event contracts
* authorization consistency
* tenant isolation under multiple modules
* causal traceability across module boundaries

### Stage 4 — ERP Expansion

Only now:
* Sales
* Procurement
* HR
* CRM
* Projects
* Assets
* Billing
* Compliance
* Workflow

---

## Real Terminal Criterion

The kernel is done when this statement becomes true:

> **Any new bounded context added to EPSILONE inherits correctness, replayability, auditability, tenant safety, and execution discipline by default — without inventing its own substrate.**

That is the real completion definition.

**Then** Phase 1 starts.