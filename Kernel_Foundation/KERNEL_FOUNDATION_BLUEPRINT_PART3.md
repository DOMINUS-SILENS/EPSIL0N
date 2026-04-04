# EPSILONE ERP KERNEL FOUNDATION BLUEPRINT — PART 3

---

# SECTION 6 — EVENT MODEL & EVENT STORE FOUNDATION

## 6.1 Domain Event Contract

```php
abstract record DomainEvent(
    EventId $eventId,
    string $aggregateId,
    TenantId $tenantId,
    CorrelationId $correlationId,
    CausationId $causationId,
    Timestamp $occurredAt,
    int $schemaVersion = 1
) {}
```

**Metadata Requirements:**

Every domain event captures:

- **EventId:** Globally unique event identifier (UUID)
- **aggregateId:** The aggregate root ID this event belongs to (string, immutable)
- **tenantId:** Mandatory tenant isolation marker
- **correlationId:** Links all events from a single user action
- **causationId:** Links to the event/command that triggered this event
- **occurredAt:** When the event happened (UTC, immutable)
- **schemaVersion:** Event version for upgrade strategies

**Invariants:**
- Events are immutable records (no setters)
- Event names are past-tense (OrderApproved, not ApproveOrder)
- No mutable references in event payload
- All dates are UTC
- No ORM models in event payload
- Payload is serializable to JSON deterministically

## 6.2 Event Versioning Strategy

Events evolve. Domains add fields, structure changes.

**Problem:** Old events replayed don't have new fields.

**Solution:** Schema versioning + upgraders.

### Example: User Email Added

**Version 1:**
```php
final record UserProvisioned(
    EventId $eventId,
    string $aggregateId,
    TenantId $tenantId,
    CorrelationId $correlationId,
    CausationId $causationId,
    Timestamp $occurredAt,
    int $schemaVersion = 1,
    string $username
) extends DomainEvent {}
```

**Version 2 (email added):**
```php
final record UserProvisioned(
    EventId $eventId,
    string $aggregateId,
    TenantId $tenantId,
    CorrelationId $correlationId,
    CausationId $causationId,
    Timestamp $occurredAt,
    int $schemaVersion = 2,
    string $username,
    string $email
) extends DomainEvent {}
```

### Event Upgrader Contract

```php
interface IEventUpgrader
{
    /**
     * Upgrade event data from $fromVersion to $toVersion.
     * @return array The upgraded event payload
     */
    public function upgrade(
        int $fromVersion,
        int $toVersion,
        array $eventData
    ): array;
}
```

### Example Upgrader

```php
class UserProvisionedUpgrader implements IEventUpgrader
{
    public function upgrade(int $fromVersion, int $toVersion, array $data): array
    {
        if ($fromVersion === 1 && $toVersion === 2) {
            // Old events didn't have email, default it
            $data['email'] = 'unknown@example.com';
            return $data;
        }
        return $data;
    }
}
```

**Rule:** Upgraders must be deterministic. Same input always produces same output.

## 6.3 Event Store Contracts

### IEventStore

```php
interface IEventStore
{
    /**
     * Append events to a stream (aggregate event history).
     * @throws ConcurrencyConflictException if expectedVersion doesn't match current
     */
    public function append(
        TenantId $tenantId,
        string $streamId,
        ulong $expectedVersion,
        DomainEvent ...$events
    ): void;

    /**
     * Load event stream for an aggregate.
     * @param int $fromVersion Start loading from this version (0 = genesis)
     */
    public function getStream(
        TenantId $tenantId,
        string $streamId,
        int $fromVersion = 0
    ): array;

    /**
     * Load single event by ID (for debugging/audit).
     */
    public function getEventById(EventId $eventId): ?StoredEvent;

    /**
     * Get all events for a tenant in order (for projections, sagas).
     */
    public function getAllEventsByTenant(
        TenantId $tenantId,
        int $afterEventNumber = 0
    ): array;
}
```

### StoredEvent

```php
final class StoredEvent
{
    public readonly EventId $eventId;
    public readonly string $aggregateId;
    public readonly TenantId $tenantId;
    public readonly CorrelationId $correlationId;
    public readonly CausationId $causationId;
    public readonly Timestamp $occurredAt;
    public readonly int $schemaVersion;
    public readonly string $eventType;  // fully qualified class name
    public readonly array $eventPayload;  // deserialized event data
    public readonly ulong $sequenceNumber;  // global ordering
    public readonly Timestamp $storedAt;

    public function __construct(
        EventId $eventId,
        string $aggregateId,
        TenantId $tenantId,
        CorrelationId $correlationId,
        CausationId $causationId,
        Timestamp $occurredAt,
        int $schemaVersion,
        string $eventType,
        array $eventPayload,
        ulong $sequenceNumber,
        Timestamp $storedAt
    ) {
        $this->eventId = $eventId;
        $this->aggregateId = $aggregateId;
        $this->tenantId = $tenantId;
        $this->correlationId = $correlationId;
        $this->causationId = $causationId;
        $this->occurredAt = $occurredAt;
        $this->schemaVersion = $schemaVersion;
        $this->eventType = $eventType;
        $this->eventPayload = $eventPayload;
        $this->sequenceNumber = $sequenceNumber;
        $this->storedAt = $storedAt;
    }
}
```

## 6.4 Snapshot Store Contracts

Snapshots are performance optimization. You can load a snapshot of aggregate state as-of version N, then replay only events after N.

### ISnapshotStore

```php
interface ISnapshotStore
{
    /**
     * Save a snapshot of aggregate state.
     */
    public function save(
        TenantId $tenantId,
        string $aggregateId,
        ulong $version,
        mixed $state  // serialized aggregate state
    ): void;

    /**
     * Load snapshot if one exists for this aggregate.
     * @return array|null The snapshotted state, or null if no snapshot exists
     */
    public function load(
        TenantId $tenantId,
        string $aggregateId
    ): ?array;

    /**
     * Get the version of the snapshot.
     */
    public function getSnapshotVersion(
        TenantId $tenantId,
        string $aggregateId
    ): ?ulong;
}
```

## 6.5 Event Serialization Contracts

Events must be serialized deterministically for comparison and persistence.

### IEventSerializer

```php
interface IEventSerializer
{
    /**
     * Serialize a domain event to JSON string.
     * Key ordering must be deterministic (sorted keys).
     */
    public function serialize(DomainEvent $event): string;

    /**
     * Deserialize JSON back to a domain event.
     */
    public function deserialize(string $json, string $eventFqn): DomainEvent;
}
```

**Deterministic Serialization Rule:**

```json
// CORRECT (keys sorted alphabetically)
{
  "aggregateId": "...",
  "causationId": "...",
  "correlationId": "...",
  "eventId": "...",
  "occurredAt": "...",
  "schemaVersion": 1,
  "tenantId": "...",
  "username": "alice"
}

// WRONG (keys not sorted)
{
  "username": "alice",
  "correlationId": "...",
  ...
}
```

**Why:** When replaying, we need to hash events to verify they computed correctly. Hash must be stable.

## 6.6 PostgreSQL Event Store Schema (Conceptual)

```sql
CREATE TABLE domain_streams (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    stream_id VARCHAR(255) NOT NULL,
    version BIGINT NOT NULL DEFAULT 0,
    aggregate_type VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE(tenant_id, stream_id)
);

CREATE TABLE domain_events (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    stream_id VARCHAR(255) NOT NULL,
    event_id UUID NOT NULL UNIQUE,
    aggregate_id VARCHAR(255) NOT NULL,
    correlation_id UUID NOT NULL,
    causation_id VARCHAR(255),
    event_type VARCHAR(255) NOT NULL,
    event_payload JSONB NOT NULL,
    schema_version INT NOT NULL DEFAULT 1,
    occurred_at TIMESTAMP NOT NULL,
    stored_at TIMESTAMP NOT NULL DEFAULT NOW(),
    sequence_number BIGINT NOT NULL UNIQUE,  -- global ordering
    FOREIGN KEY(tenant_id, stream_id) REFERENCES domain_streams(tenant_id, stream_id)
);

CREATE TABLE domain_snapshots (
    id BIGSERIAL PRIMARY KEY,
    tenant_id UUID NOT NULL,
    aggregate_id VARCHAR(255) NOT NULL,
    version BIGINT NOT NULL,
    aggregate_type VARCHAR(255) NOT NULL,
    state_payload JSONB NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE(tenant_id, aggregate_id)
);

CREATE INDEX idx_domain_events_tenant ON domain_events(tenant_id);
CREATE INDEX idx_domain_events_stream ON domain_events(tenant_id, stream_id);
CREATE INDEX idx_domain_events_correlation ON domain_events(correlation_id);
CREATE INDEX idx_domain_events_sequence ON domain_events(sequence_number);
```

---

# SECTION 7 — REPOSITORY + UNIT OF WORK FOUNDATION

## 7.1 Repository Contracts

### IRepository<TAggregate, TId>

```php
/**
 * @template TAggregate of AggregateRoot
 * @template TId
 */
interface IRepository
{
    /**
     * Generate a new unique ID for this aggregate type.
     * (May delegate to ID generation service)
     */
    public function nextIdentity(): TId;

    /**
     * Get an aggregate by ID.
     * @throws NotFoundException if aggregate doesn't exist or is deleted
     * @throws TenantIsolationViolationException if tenantId doesn't match
     */
    public function getById(TId $id, TenantId $tenantId): TAggregate;

    /**
     * Add a new aggregate (initial save).
     * @throws DuplicateIdentityException if ID already exists
     */
    public function add(TAggregate $aggregate): void;

    /**
     * Save changes to an existing aggregate.
     * @throws ConcurrencyConflictException if version mismatch
     * @throws NotFoundException if aggregate doesn't exist
     */
    public function save(TAggregate $aggregate): void;

    /**
     * Delete an aggregate (soft or hard, domain-dependent).
     */
    public function remove(TAggregate $aggregate): void;

    /**
     * Check if an aggregate exists (for existence checks without loading).
     */
    public function exists(TId $id, TenantId $tenantId): bool;
}
```

**Invariants:**
- tenant_id is mandatory parameter (not optional)
- no `find*Any()` methods that leak query internals
- no `IQueryable` exposure
- all operations are aggregate-centric, not property-centric
- version conflicts are loud, not silent

### ISpecificationRepository<TReadModel>

For read models, queries are allowed but still via Specifications (not raw SQL).

```php
interface ISpecificationRepository
{
    /**
     * Find read models matching a specification.
     */
    public function findBySpecification(
        Specification $spec,
        TenantId $tenantId,
        Paging $paging = null
    ): array;

    /**
     * Count matching read models.
     */
    public function countBySpecification(
        Specification $spec,
        TenantId $tenantId
    ): int;
}
```

## 7.2 Unit of Work

### IUnitOfWork

```php
interface IUnitOfWork
{
    /**
     * Execute an operation within a transaction.
     * All persistence operations inside the callable are atomic.
     * @throws Exception if transaction fails, operation is rolled back
     */
    public function transactional(
        callable $operation
    ): mixed;

    /**
     * Get the current repository for an aggregate type.
     * Repositories are scoped to the current transaction.
     */
    public function getRepository(string $aggregateClass): IRepository;

    /**
     * Explicitly save all pending changes (aggregates, events, outbox).
     */
    public function saveChanges(): void;

    /**
     * Rollback pending changes.
     */
    public function rollback(): void;
}
```

### Transaction Semantics

**Exactly these operations are atomic in one transaction:**

1. Aggregate state persisted (INSERT + UPDATE)
2. Uncommitted domain events persisted
3. Outbox message persisted
4. Audit entry persisted

**All or nothing.**

**Example:**

```php
await _unitOfWork.transactional(async () => {
    var customer = await _customerRepo.getById(customerId, tenantId);

    customer.assignCredit(creditAmount, actor);  // raises event

    await _customerRepo.save(customer);  // save + events + outbox + audit atomic
});
```

If saving fails, everything rolls back. No orphan events. No orphan audit. No orphan outbox.

---

# SECTION 8 — APPLICATION BOUNDARY FOUNDATION

## 8.1 Command / Query Contracts

The application layer is where use cases are orchestrated.

### ICommand<TResult>

```php
/**
 * @template TResult The type of result this command produces
 */
interface ICommand
{
    /**
     * Optional idempotency key.
     * If provided, same key + same command are deduplicated.
     */
    public function getIdempotencyKey(): ?string;
}
```

### IQuery<TResult>

```php
/**
 * @template TResult The type of result this query produces
 */
interface IQuery
{
    // Queries are side-effect free, read-only
}
```

### Handler Contracts

```php
/**
 * @template TCommand of ICommand<TResult>
 * @template TResult
 */
interface ICommandHandler
{
    public function handle(ICommand $command): Result;
}

/**
 * @template TQuery of IQuery<TResult>
 * @template TResult
 */
interface IQueryHandler
{
    public function handle(IQuery $query): Result;
}
```

## 8.2 Command Execution Pipeline

**The pipeline order is mandatory and must not be changed:**

```
1. Input Validation
   ↓
2. Authorization Check
   ↓
3. Idempotency Check (if key provided)
   ↓
4. Begin Transaction
   ↓
5. Audit Attribution (set actor context)
   ↓
6. Execute Command Handler
   ↓
7. Persist Uncommitted Events + Outbox
   ↓
8. Commit Transaction
   ↓
9. Emit Telemetry
   ↓
10. Return Result
```

**Why this order matters:**

- **Validation before auth:** Fail fast on malformed input
- **Auth before idempotency:** Don't waste idempotency lookups for unauthorized requests
- **Idempotency before transaction:** Deduplicate without re-executing
- **Transaction wraps handler:** All side effects are atomic
- **Audit attribution inside transaction:** Audit is coherent with state change
- **Telemetry after commit:** Only emit metrics for successful operations
- **Result returned last:** Handler has completed, transaction is committed

## 8.3 Validator Contracts

### IValidator<T>

```php
interface IValidator
{
    /**
     * Validate an input.
     * @return ValidationResult with errors, or empty if valid
     */
    public function validate(mixed $input): ValidationResult;
}

final class ValidationResult
{
    /** @var ValidationError[] */
    private array $errors = [];

    public function addError(ValidationError $error): self
    {
        $this->errors[] = $error;
        return $this;
    }

    public function isValid(): bool
    {
        return empty($this->errors);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
```

### ValidationError

```php
final class ValidationError
{
    public string $field;
    public string $code;
    public string $message;
    public ?mixed $attemptedValue;

    public function __construct(
        string $field,
        string $code,
        string $message,
        ?mixed $attemptedValue = null
    ) {
        $this->field = $field;
        $this->code = $code;
        $this->message = $message;
        $this->attemptedValue = $attemptedValue;
    }
}
```

## 8.4 Authorization Contracts

### IActionRequirement

```php
interface IActionRequirement
{
}

// Example requirements
final class ApproveOrderRequirement implements IActionRequirement
{
    public function __construct(
        public readonly OrderId $orderId,
        public readonly TenantId $tenantId
    ) {}
}

final class EditInvoiceRequirement implements IActionRequirement
{
    public function __construct(
        public readonly InvoiceId $invoiceId,
        public readonly TenantId $tenantId
    ) {}
}
```

### IAuthorizationService

```php
interface IAuthorizationService
{
    /**
     * Authorize a requirement.
     * @throws AuthorizationException if authorization fails
     */
    public function authorize(
        IActionRequirement $requirement,
        ISecurityContext $context
    ): void;

    /**
     * Check if authorization would pass (without throwing).
     */
    public function canAuthorize(
        IActionRequirement $requirement,
        ISecurityContext $context
    ): bool;
}
```

## 8.5 Result Pattern (Already Defined in Section 2)

Recap:

```php
/**
 * @template TData
 * @template TError of DomainError
 */
final class Result
{
    private bool $isSuccess;
    private ?mixed $data;
    private ?DomainError $error;

    public static function success(mixed $data): self
    {
        $result = new self();
        $result->isSuccess = true;
        $result->data = $data;
        $result->error = null;
        return $result;
    }

    public static function failure(DomainError $error): self
    {
        $result = new self();
        $result->isSuccess = false;
        $result->data = null;
        $result->error = $error;
        return $result;
    }

    public function isSuccess(): bool { return $this->isSuccess; }
    public function getData(): mixed { return $this->data; }
    public function getError(): ?DomainError { return $this->error; }
}
```

## 8.6 Example Command Handler

This shows how all pieces fit together:

```php
final class ApproveOrderHandler implements ICommandHandler
{
    public function __construct(
        private readonly IValidator $validator,
        private readonly IAuthorizationService $auth,
        private readonly IIdempotencyStore $idempotency,
        private readonly IOrderRepository $orders,
        private readonly IUnitOfWork $unitOfWork,
        private readonly IAuditTrail $audit,
        private readonly ITracer $tracer,
    ) {}

    public function handle(ApproveOrderCommand $cmd): Result
    {
        return $this->tracer->span('ApproveOrderCommand', function() use ($cmd) {
            // 1. Validate input
            $validation = $this->validator->validate($cmd);
            if (!$validation->isValid()) {
                return Result::failure(new ValidationError(...));
            }

            // 2. Check authorization
            try {
                $this->auth->authorize(
                    new ApproveOrderRequirement($cmd->orderId, $cmd->tenantId),
                    $cmd->context
                );
            } catch (AuthorizationException $e) {
                return Result::failure(new AuthorizationDenied(...));
            }

            // 3. Check idempotency
            if ($cmd->getIdempotencyKey()) {
                $existing = $this->idempotency->getResult($cmd->getIdempotencyKey());
                if ($existing !== null) {
                    return $existing;  // Replay result
                }
            }

            // 4-8. Execute in transaction
            return $this->unitOfWork->transactional(function() use ($cmd) {
                // 5. Set audit context
                $auditContext = new AuditContext(
                    $cmd->context->getActorId(),
                    $cmd->tenantId,
                    get_class($cmd),
                    $cmd->correlationId
                );
                $this->audit->setContext($auditContext);

                // 6. Load and execute
                $order = $this->orders->getById($cmd->orderId, $cmd->tenantId);
                $order->approve($cmd->context->getActorId(), $cmd->reason);

                // Automatically persists events + audit + outbox
                $this->orders->save($order);

                // 9. Emit telemetry
                $this->tracer->increment('orders.approved');

                // 10. Return result
                return Result::success(new ApproveOrderResult($order->getId()));
            });
        });
    }
}
```

---continued...