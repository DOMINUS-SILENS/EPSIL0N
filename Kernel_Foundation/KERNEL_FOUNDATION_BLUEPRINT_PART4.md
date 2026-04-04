# EPSILONE ERP KERNEL FOUNDATION BLUEPRINT — PART 4

---

# SECTION 9 — TENANCY / SECURITY / AUTHORITY FOUNDATION

## 9.1 Security Context

### ISecurityContext

```php
interface ISecurityContext
{
    /**
     * The principal making the request/operation.
     */
    public function getActorId(): ActorId;

    /**
     * The tenant this operation is scoped to.
     */
    public function getTenantId(): TenantId;

    /**
     * Whether the actor is authenticated.
     */
    public function isAuthenticated(): bool;

    /**
     * Return all roles assigned to this actor in this tenant.
     */
    public function getRoles(): array;

    /**
     * Check if actor has a specific capability.
     */
    public function hasCapability(string $capability): bool;
}
```

**Usage:**

```php
// In a command handler
$context = $cmd->getSecurityContext();

$actorId = $context->getActorId();  // Who is making this request?
$tenantId = $context->getTenantId();  // In which tenant?

// Pass these to repositories
$order = $this->orders->getById($orderId, $tenantId);
```

## 9.2 Authorization Service

### IAuthorizationService

```php
interface IAuthorizationService
{
    /**
     * Authorize an action.
     * @throws AuthorizationException if not authorized
     */
    public function authorize(
        IActionRequirement $requirement,
        ISecurityContext $context
    ): void;

    /**
     * Check if authorized (returns bool, doesn't throw).
     */
    public function can(
        IActionRequirement $requirement,
        ISecurityContext $context
    ): bool;
}
```

### Action Requirements

```php
interface IActionRequirement
{
    // Marker interface for specific requirements
}

// Example: editing an order requires specific permission
final class EditOrderRequirement implements IActionRequirement
{
    public function __construct(
        public readonly OrderId $orderId,
        public readonly TenantId $tenantId
    ) {}
}

// Example: approving an order requires approval role
final class ApproveOrderRequirement implements IActionRequirement
{
    public function __construct(
        public readonly OrderId $orderId,
        public readonly int $approvalThreshold,
        public readonly TenantId $tenantId
    ) {}
}
```

### Authorization Decisions

```php
abstract class AuthorizationPolicy
{
    final public function authorize(
        IActionRequirement $requirement,
        ISecurityContext $context
    ): bool {
        // Check tenant scope first (always)
        if (!$this->isInCorrectTenant($requirement, $context)) {
            return false;
        }

        // Then check capability
        return $this->authorizeInTenant($requirement, $context);
    }

    private function isInCorrectTenant(
        IActionRequirement $req,
        ISecurityContext $ctx
    ): bool {
        // Get tenant from requirement (all requirements know their tenant)
        $reqTenant = $this->extractTenant($req);
        return $ctx->getTenantId()->equals($reqTenant);
    }

    protected function extractTenant(IActionRequirement $req): TenantId
    {
        // Requirements should expose their tenant
        if (property_exists($req, 'tenantId')) {
            return $req->tenantId;
        }
        throw new InvalidArgumentException('Requirement must include tenantId');
    }

    abstract protected function authorizeInTenant(
        IActionRequirement $requirement,
        ISecurityContext $context
    ): bool;
}
```

## 9.3 Tenant Resolution

### ITenantResolver

```php
interface ITenantResolver
{
    /**
     * Resolve the current tenant from request context.
     * May parse JWT claims, HTTP headers, subdomain, etc.
     */
    public function resolveTenant(): TenantId;

    /**
     * Resolve tenant lazily if not yet determined.
     */
    public function resolveTenantOrNull(): ?TenantId;
}
```

## 9.4 Tenant Aggregate (Minimal Kernel Model)

Even though tenancy is mostly infrastructure, a Tenant aggregate in the kernel ensures consistency.

```php
final class Tenant extends AggregateRoot<TenantId>
{
    private TenantSlug $slug;
    private string $name;
    private FeatureFlags $features;
    private Timestamp $createdAt;

    public static function create(
        TenantId $id,
        TenantSlug $slug,
        string $name
    ): self {
        $tenant = new self($id, $id);  // Tenant is root for its own ID
        $tenant->raise(new TenantCreated($id, $slug, $name));
        return $tenant;
    }

    protected function when(DomainEvent $event): void
    {
        match ($event::class) {
            TenantCreated::class => $this->onTenantCreated($event),
            TenantRenamed::class => $this->onTenantRenamed($event),
            FeatureFlagToggled::class => $this->onFeatureFlagToggled($event),
        };
    }

    private function onTenantCreated(TenantCreated $event): void
    {
        $this->slug = $event->slug;
        $this->name = $event->name;
        $this->features = new FeatureFlags();
        $this->createdAt = $event->occurredAt;
    }

    private function onTenantRenamed(TenantRenamed $event): void
    {
        $this->name = $event->newName;
    }

    private function onFeatureFlagToggled(FeatureFlagToggled $event): void
    {
        if ($event->enabled) {
            $this->features->enable($event->feature);
        } else {
            $this->features->disable($event->feature);
        }
    }

    public function rename(string $newName): void
    {
        $this->raise(new TenantRenamed($this->eventId(), $newName));
    }

    public function toggleFeature(FeatureFlag $feature, bool $enabled): void
    {
        $this->raise(new FeatureFlagToggled($this->eventId(), $feature, $enabled));
    }

    public function getSlug(): TenantSlug { return $this->slug; }
    public function getName(): string { return $this->name; }
    public function getFeatures(): FeatureFlags { return $this->features; }
}
```

## 9.5 Tenant Isolation Rules

**These are non-negotiable and enforced at kernel layer:**

### Rule 1: Every Query Must Include Tenant Filter

```php
// WRONG
$statement = $pdo->query("SELECT * FROM orders WHERE id = ?");

// CORRECT
$statement = $pdo->query("SELECT * FROM orders WHERE id = ? AND tenant_id = ?");
```

### Rule 2: Repositories Must Enforce Tenant

```php
// WRONG: Tenant is optional
public function getById(OrderId $id): Order
{
    return $this->db->table('orders')->find($id);
}

// CORRECT: Tenant is mandatory
public function getById(OrderId $id, TenantId $tenantId): Order
{
    return $this->db->table('orders')
        ->where('id', $id)
        ->where('tenant_id', $tenantId)
        ->firstOrFail();
}
```

### Rule 3: Aggregates Know Their Tenant

```php
$order = $this->orders->getById($orderId, $tenantId);

// This is safe because the aggregate itself refuses cross-tenant operations
assert($order->getTenantId()->equals($tenantId));
```

### Rule 4: No Ambient Global Tenant State

```php
// WRONG
class Auth {
    private static $currentTenant;  // Global state!
}

// CORRECT
class Auth {
    public function __construct(private TenantId $tenantId) {}
}
```

---

# SECTION 10 — TEMPORAL / APPROVAL / WORKFLOW FOUNDATION

## 10.1 Business Calendar

### IBusinessCalendar

The kernel provides temporal governance. Every module uses this.

```php
interface IBusinessCalendar
{
    /**
     * Check if a date is open for posting.
     */
    public function canPost(
        BusinessDate $date,
        TenantId $tenantId,
        BusinessOperation $operation = null
    ): bool;

    /**
     * Get the business period containing a date.
     */
    public function getPeriod(
        BusinessDate $date,
        TenantId $tenantId
    ): BusinessPeriod;

    /**
     * Check if a period is closed.
     */
    public function isPeriodClosed(
        BusinessPeriod $period,
        TenantId $tenantId
    ): bool;

    /**
     * Check if backdating is allowed to a specific date.
     * (e.g., can GL post to yesterday? Last month?)
     */
    public function canBackdate(
        BusinessDate $toDate,
        TenantId $tenantId
    ): bool;
}
```

### BusinessOperation

```php
enum BusinessOperation
{
    case POSTING;        // General ledger posting
    case INVOICING;      // Issuing invoices
    case RECEIVING;      // Goods receipt
    case SHIPPING;       // Goods shipment
    case PAYROLL;        // Payroll processing
    case RECONCILIATION; // Account reconciliation

    // Domains can add their own operations
}
```

### Usage

```php
// In Invoice domain
$invoice = new Invoice(...);

// Check if we can post this invoice
if (!$this->calendar->canPost($invoice->getDate(), $tenantId, BusinessOperation::INVOICING)) {
    throw new ClosedPeriodViolationException(
        "Cannot post invoices to {$invoice->getDate()}"
    );
}

$invoice->post();
```

## 10.2 Workflow / State Transition

### LifecycleState

```php
abstract class LifecycleState
{
    // Subclasses represent specific domain states
    abstract public function canTransitionTo(LifecycleState $target): bool;
    abstract public function getName(): string;
}
```

### Example: Order States

```php
enum OrderStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case APPROVED = 'approved';
    case CANCELLED = 'cancelled';
    case COMPLETED = 'completed';

    public function canTransitionTo(OrderStatus $target): bool
    {
        return match ([$this, $target]) {
            [OrderStatus::DRAFT, OrderStatus::SUBMITTED] => true,
            [OrderStatus::SUBMITTED, OrderStatus::APPROVED] => true,
            [OrderStatus::SUBMITTED, OrderStatus::CANCELLED] => true,
            [OrderStatus::APPROVED, OrderStatus::COMPLETED] => true,
            [OrderStatus::DRAFT, OrderStatus::CANCELLED] => true,
            default => false,
        };
    }
}
```

### StateTransitionPolicy

```php
interface IStateTransitionPolicy
{
    /**
     * Determine if a transition is legal.
     */
    public function canTransition(
        LifecycleState $from,
        LifecycleState $to,
        mixed $context = null
    ): bool;
}
```

### Usage in Aggregate

```php
final class Order extends AggregateRoot
{
    private OrderStatus $status;

    public function approve(ActorId $actor): void
    {
        if (!$this->status->canTransitionTo(OrderStatus::APPROVED)) {
            throw new InvalidStateTransitionException(
                "Cannot approve order in status {$this->status->value}"
            );
        }

        $this->raise(new OrderApproved($actor));
    }

    protected function onOrderApproved(OrderApproved $event): void
    {
        $this->status = OrderStatus::APPROVED;
    }
}
```

## 10.3 Approval Core

Many ERP workflows require approval.

### ApprovalRequest Aggregate

```php
final class ApprovalRequest extends AggregateRoot<ApprovalRequestId>
{
    private string $subject;
    private string $description;
    private ActorId $requestorActorId;
    private ApprovalPolicy $policy;
    private ApprovalDecision $decision = null;  // null = pending
    private Timestamp $decidedAt = null;

    public static function create(
        ApprovalRequestId $id,
        TenantId $tenantId,
        string $subject,
        ActorId $requestor,
        ApprovalPolicy $policy
    ): self {
        $req = new self($id, $tenantId);
        $req->raise(new ApprovalRequested($id, $subject, $requestor, $policy));
        return $req;
    }

    public function approve(ActorId $approver, string $reason = ''): void
    {
        if ($this->isDecided()) {
            throw new AlreadyDecidedException();
        }

        // Policy determines if approver is authorized
        if (!$this->policy->isAuthorized($approver)) {
            throw new NotAuthorizedToApproveException();
        }

        $this->raise(new ApprovalApproved($approver, $reason));
    }

    public function reject(ActorId $rejector, string $reason): void
    {
        if ($this->isDecided()) {
            throw new AlreadyDecidedException();
        }

        $this->raise(new ApprovalRejected($rejector, $reason));
    }

    private function isDecided(): bool
    {
        return $this->decision !== null;
    }

    protected function when(DomainEvent $event): void
    {
        match ($event::class) {
            ApprovalRequested::class => $this->onApprovalRequested($event),
            ApprovalApproved::class => $this->onApprovalApproved($event),
            ApprovalRejected::class => $this->onApprovalRejected($event),
        };
    }

    private function onApprovalRequested(ApprovalRequested $event): void
    {
        $this->subject = $event->subject;
        $this->requestorActorId = $event->requestorActorId;
        $this->policy = $event->policy;
    }

    private function onApprovalApproved(ApprovalApproved $event): void
    {
        $this->decision = ApprovalDecision::APPROVED;
        $this->decidedAt = $event->occurredAt;
    }

    private function onApprovalRejected(ApprovalRejected $event): void
    {
        $this->decision = ApprovalDecision::REJECTED;
        $this->decidedAt = $event->occurredAt;
    }

    public function getDecision(): ?ApprovalDecision { return $this->decision; }
    public function isApproved(): bool { return $this->decision === ApprovalDecision::APPROVED; }
    public function isRejected(): bool { return $this->decision === ApprovalDecision::REJECTED; }
}
```

### ApprovalPolicy

```php
abstract class ApprovalPolicy
{
    /**
     * Determine if an actor is authorized to approve.
     */
    abstract public function isAuthorized(ActorId $actor): bool;

    /**
     * Get human-readable approval requirement description.
     */
    abstract public function getDescription(): string;
}

// Example: Requires Finance Manager role
final class FinanceManagerApprovalPolicy extends ApprovalPolicy
{
    public function __construct(private IAuthorizationService $auth) {}

    public function isAuthorized(ActorId $actor): bool
    {
        // Delegation, expiration, and capability checks
        return $this->auth->hasRole($actor, 'FINANCE_MANAGER');
    }

    public function getDescription(): string
    {
        return 'Requires Finance Manager approval';
    }
}

// Example: Threshold-based (certain amounts need higher approval)
final class ThresholdApprovalPolicy extends ApprovalPolicy
{
    public function __construct(
        private Money $threshold,
        private IAuthorizationService $auth
    ) {}

    public function isAuthorized(ActorId $actor): bool
    {
        // Only executives can approve above threshold
        return $this->auth->hasRole($actor, 'EXECUTIVE');
    }

    public function getDescription(): string
    {
        return "Requires approval for amounts > {$this->threshold->getMajorAmount()}";
    }
}
```

---

# SECTION 11 — OUTBOX / INBOX / IDEMPOTENCY FOUNDATION

## 11.1 Outbox Pattern

The Outbox solves the "dual-write" problem: how do we emit events reliably?

### The Problem

```
Save aggregate ← writes Event to database
Also publish event to message bus ← network call fails!

Result: Database has event, but message bus never got it.
Event is lost.
```

### The Solution: Transactional Outbox

```
BEGIN TRANSACTION
  Save aggregate
  Save event to domain_events table
  Save message to outbox table ← all in same transaction
COMMIT

Separately (async):
  Read from outbox
  Publish to message bus
  Mark as published
  If publish fails, retry indefinitely

No race condition. No data loss.
```

### IOutboxStore

```php
interface IOutboxStore
{
    /**
     * Enqueue a message for async publishing.
     * Called as part of aggregate save transaction.
     */
    public function enqueue(OutboxMessage $message): void;

    /**
     * Dequeue messages for publishing.
     */
    public function dequeue(int $batchSize = 100): array;

    /**
     * Mark a message as published.
     */
    public function markPublished(OutboxMessageId $id): void;

    /**
     * Mark a message as failed (will retry).
     */
    public function markFailed(OutboxMessageId $id, string $error): void;

    /**
     * Get dead-lettered messages (failed too many times).
     */
    public function getDeadLettered(): array;
}
```

### OutboxMessage

```php
final class OutboxMessage
{
    public readonly OutboxMessageId $id;
    public readonly TenantId $tenantId;
    public readonly string $messageType;  // e.g., 'events.order.approved'
    public readonly array $payload;
    public readonly Timestamp $createdAt;
    public ?OutboxMessageStatus $status;
    public ?int $retryCount;

    public function __construct(
        OutboxMessageId $id,
        TenantId $tenantId,
        string $messageType,
        array $payload
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->messageType = $messageType;
        $this->payload = $payload;
        $this->createdAt = Timestamp::now();
        $this->status = OutboxMessageStatus::PENDING;
        $this->retryCount = 0;
    }
}

enum OutboxMessageStatus
{
    case PENDING;
    case PUBLISHED;
    case FAILED;
    case DEAD_LETTERED;
}
```

### PostgreSQL Schema for Outbox

```sql
CREATE TABLE outbox_messages (
    id BIGSERIAL PRIMARY KEY,
    outbox_message_id UUID NOT NULL UNIQUE,
    tenant_id UUID NOT NULL,
    message_type VARCHAR(255) NOT NULL,
    payload JSONB NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'PENDING',
    retry_count INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    published_at TIMESTAMP,
    last_error TEXT,
    FOREIGN KEY(tenant_id) REFERENCES tenants(id)
);

CREATE INDEX idx_outbox_status ON outbox_messages(status, created_at);
```

## 11.2 Inbox / Idempotency Pattern

When messages from external sources are consumed (e.g., from an event bus), they must be deduplicated.

### The Problem

```
Message arrives: Order shipped
Process it, update inventory
Network interruption!
Message is re-delivered
Process it again!

Inventory updated twice. Stock count is wrong.
```

### The Solution: Inbox

```
Receive message
Check if we've processed this message ID before
If yes: ignore (already processed)
If no: process + mark as processed

All in same transaction.
```

### IProcessedMessageStore

```php
interface IProcessedMessageStore
{
    /**
     * Check if we've already processed this message.
     */
    public function hasProcessed(string $messageId): bool;

    /**
     * Mark a message asprocessed.
     */
    public function markProcessed(string $messageId): void;
}
```

### PostgreSQL Schema for Inbox

```sql
CREATE TABLE inbox_processed_messages (
    id BIGSERIAL PRIMARY KEY,
    message_id VARCHAR(255) NOT NULL UNIQUE,
    tenant_id UUID NOT NULL,
    processed_at TIMESTAMP NOT NULL DEFAULT NOW(),
    FOREIGN KEY(tenant_id) REFERENCES tenants(id)
);
```

## 11.3 Command Idempotency

Commands may be retried (network timeout, crash, etc.).

If the same command is retried with the same IdempotencyKey, we should return the same result without re-executing.

### IIdempotencyStore

```php
interface IIdempotencyStore
{
    /**
     * Check if we've already processed a command with this key.
     */
    public function existsKey(string $key): bool;

    /**
     * Retrieve the cached result for a command.
     */
    public function getResult(string $key): ?mixed;

    /**
     * Store the result of a command execution.
     */
    public function storeResult(string $key, mixed $result, int $ttlSeconds = 3600): void;
}
```

### PostgreSQL Schema for Idempotency

```sql
CREATE TABLE idempotency_records (
    id BIGSERIAL PRIMARY KEY,
    idempotency_key VARCHAR(255) NOT NULL UNIQUE,
    tenant_id UUID NOT NULL,
    result_payload JSONB NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    expires_at TIMESTAMP NOT NULL,
    FOREIGN KEY(tenant_id) REFERENCES tenants(id)
);

CREATE INDEX idx_idempotency_expires ON idempotency_records(expires_at);
```

### Usage in Command Handler

```php
final class CreateOrderHandler implements ICommandHandler
{
    public function handle(CreateOrderCommand $cmd): Result
    {
        // 1. Check idempotency
        $idempotencyKey = $cmd->getIdempotencyKey();
        if ($idempotencyKey) {
            if ($this->idempotencyStore->existsKey($idempotencyKey)) {
                return $this->idempotencyStore->getResult($idempotencyKey);
            }
        }

        // 2. Execute normally
        $result = $this->executeCreateOrder($cmd);

        // 3. Cache result
        if ($idempotencyKey) {
            $this->idempotencyStore->storeResult($idempotencyKey, $result);
        }

        return $result;
    }
}
```

---continued in final section...