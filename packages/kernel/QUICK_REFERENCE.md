# EPSILON Kernel — Quick Reference Index

**Last Updated:** 2026-04-03 | **Phase:** 1-2 Complete

---

## Directory Map (src/)

```
src/
├── Support/Exception/
│   ├── KernelException.php              (base: infrastructure failures)
│   ├── DomainException.php              (base: domain failures)
│   ├── ValidationException.php          (input validation)
│   ├── ConcurrencyConflictException.php (optimistic lock)
│   ├── AuthorizationException.php       (access denied)
│   ├── BusinessRuleViolationException.php (invariant break)
│   └── NotFoundException.php            (resource not found)
│
├── Domain/Shared/
│   ├── ValueObject/
│   │   └── ValueObject.php              (base: all VOs)
│   ├── Error/
│   │   ├── ErrorCode.php                (structured error codes)
│   │   └── ErrorDetail.php              (rich error info)
│   └── Result/
│       └── Result.php                   (Success/Failure monad)
│
├── Domain/Identity/
│   ├── TenantId.php                     (tenant isolation boundary)
│   ├── UserId.php                       (human user identifier)
│   ├── ActorId.php                      (execution context)
│   ├── EventId.php                      (event identifier - v7 UUID)
│   ├── CorrelationId.php                (distributed trace grouping)
│   ├── CausationId.php                  (event causation chain)
│   └── DocumentId.php                   (domain document identifier)
│
└── Domain/Tenancy/
    ├── TenantSlug.php                   (human-readable tenant ID)
    ├── EmailAddress.php                 (validated email)
    └── ResourceReference.php            (cross-aggregate reference)
```

---

## Classes at a Glance

### Exception Hierarchy
```
throw new ValidationException(
    ['email' => ['Email format invalid']],
    'Input validation failed'
);

throw new ConcurrencyConflictException(
    'Order', $orderId, 5, 7
);

throw new AuthorizationException(
    $actorId, 'delete_order', 'Order', $orderId
);

throw new BusinessRuleViolationException(
    'CREDIT_LIMIT_EXCEEDED',
    'Customer credit limit exceeded',
    ['limit' => 10000, 'used' => 10500]
);

throw new NotFoundException('Order', $orderId);
```

### Using Result Monad
```php
// Success path
Result::success($data)
    ->map(fn($v) => transform($v))
    ->onSuccess(fn($v) => log($v))
    ->unwrap(); // throws if failure

// Failure path
Result::failure(ErrorDetail::create(
    ErrorCode::fromString('ORDER.NOT_FOUND'),
    'Order not found'
))
    ->unwrapOr($defaultOrder)
    ->match(
        success: fn($order) => response($order),
        failure: fn($error) => error($error->message())
    );
```

### Creating Identity Value Objects
```php
// Generate new
$tenantId = TenantId::generate();
$eventId = EventId::generate();  // v7 (time-ordered)

// From string
$userId = UserId::fromString('550e8400-e29b-41d4-a716-446655440000');

// System actor
$systemActor = ActorId::system(); // 00000000-0000-0000-0000-000000000000

// From another ID
$actorId = ActorId::fromUserId($userId);
$causationId = CausationId::fromEventId($eventId);

// String conversion
echo $tenantId; // UUID string
$uuid = $tenantId->toUuid(); // Ramsey UuidInterface
```

### Creating Governance Value Objects
```php
// TenantSlug (3-63 chars, lowercase, alphanumeric + hyphens)
$slug = TenantSlug::fromString('my-company'); // throws on failure
$slug = TenantSlug::tryFromString('my-company'); // null on failure
if (TenantSlug::isReserved('admin')) { /* ... */ }

// EmailAddress (RFC-5322 simplified)
$email = EmailAddress::fromString('user@example.com');
echo $email->localPart(); // "user"
echo $email->domain();    // "example.com" (lowercased)
if ($email->matchesDomain('*.example.com')) { /* ... */ }

// ResourceReference (Type:Id or Type:Id@TenantId)
$ref = ResourceReference::create('Order', $orderId);
$ref = ResourceReference::crossTenant('Payment', $paymentId, $otherTenantId);
echo $ref; // "Order:uuid" or "Payment:uuid@tenant-uuid"
```

### Error Codes
```php
// Create error codes
$code = ErrorCode::kernel('OPERATION_FAILED');
$code = ErrorCode::validation('EMAIL_INVALID');
$code = ErrorCode::auth('PERMISSION_DENIED');
$code = ErrorCode::domainError('Order', 'INVALID_QUANTITY');

// Check error code type
$code->isKernelError();
$code->isDomainError();
$code->isValidationError();
$code->isAuthError();

// Built-in constants
ErrorCode::CONCURRENCY_CONFLICT;
ErrorCode::NOT_FOUND;
ErrorCode::INVALID_STATE;
ErrorCode::OPERATION_FAILED;
```

### Rich Error Details
```php
// From code + message
$detail = ErrorDetail::create(
    ErrorCode::fromString('ORDER.OUT_OF_STOCK'),
    'Insufficient inventory for requested quantity'
);

// With context
$detail = ErrorDetail::withContextData(
    ErrorCode::fromString('ORDER.PRICE_MISMATCH'),
    'Price changed since quote',
    ['expectedPrice' => 100, 'currentPrice' => 110]
);

// Validation errors
$detail = ErrorDetail::validationFailed(
    'Form validation failed',
    [
        'email' => ['Email format invalid', 'Email already registered'],
        'password' => ['Too short']
    ],
    $traceId
);

// From exception
$detail = ErrorDetail::fromException(
    $exception,
    $traceId,
    $correlationId
);

// Immutable withers
$detail = $detail
    ->withAddedContext(['userId' => $user->id()])
    ->withTraceIdentifiers($traceId, $correlationId);

// Extract data
$detail->code();           // ErrorCode
$detail->message();        // string
$detail->context();        // array
$detail->fieldErrors();    // array<field => array<error>>
$detail->hasFieldErrors(); // bool
$detail->toArray();        // serialize for logging
```

---

## Common Patterns

### Validation
```php
// Validate input and return Result
$errors = [];
if (!EmailAddress::tryFromString($input['email'])) {
    $errors['email'] = ['Invalid email format'];
}
if (!TenantSlug::tryFromString($input['slug'])) {
    $errors['slug'] = ['Invalid slug format'];
}

if (!empty($errors)) {
    return Result::failure(
        ErrorDetail::validationFailed('Validation failed', $errors)
    );
}

// Or throw exception
if ($errors) {
    throw new ValidationException($errors);
}
```

### Concurrency Handling
```php
try {
    $order->updateQuantity($newQty, $expectedVersion);
} catch (ConcurrencyConflictException $e) {
    logger()->warn('Concurrency conflict', [
        'aggregate' => $e->getAggregateType(),
        'id' => $e->getAggregateId(),
        'expected' => $e->getExpectedVersion(),
        'actual' => $e->getActualVersion(),
    ]);
    throw $e; // Re-raise for retry logic
}
```

### Authorization Checks
```php
// In application layer (BEFORE aggregate invocation)
if (!$authService->can($actor, 'delete_order', $order)) {
    throw new AuthorizationException(
        (string)$actor->id(),
        'delete_order',
        'Order',
        (string)$order->id()
    );
}

// Safe to invoke aggregate now
$order->delete($actor);
```

### Business Rule Checks
```php
if ($order->totalAmount() > $customer->creditLimit()) {
    throw new BusinessRuleViolationException(
        'CREDIT_LIMIT_EXCEEDED',
        'Customer credit limit would be exceeded',
        [
            'limit' => $customer->creditLimit(),
            'requested' => $order->totalAmount(),
            'available' => $customer->availableCredit(),
        ]
    );
}
```

### Traced Error Handling
```php
$traceId = CorrelationId::generate();
$correlationId = $correlationId ?? CorrelationId::generate();

try {
    // business logic
} catch (KernelException $e) {
    $detail = ErrorDetail::fromException(
        $e,
        $traceId->toString(),
        $correlationId->toString()
    );

    logger()->error($detail->message(), $detail->toArray());
}
```

---

## Constants Reference

### Reserved Tenant Slugs
`www`, `api`, `admin`, `app`, `mail`, `ftp`, `smtp`, `pop`, `imap`, `secure`, `login`, `logout`, `register`, `signup`, `signin`, `signout`, `account`, `dashboard`, `settings`, `config`, `system`, `internal`, `test`, `staging`, `production`, `localhost`, `example`, `demo`

### Error Code Formats
- Kernel: `KERNEL.CODE`
- Domain: `DOMAIN.CONTEXT.ERROR`
- Validation: `VALIDATION.FIELD`
- Auth: `AUTH.RULE`

### UUID Versions Used
- **v4:** General IDs (TenantId, UserId, ActorId, DocumentId, CorrelationId, CausationId)
- **v7:** Time-ordered (EventId for sequencing)

---

## Testing Helpers (Ready for Phase 3)

```bash
# Run all tests
cd packages/kernel && ./vendor/bin/phpunit

# Run unit tests only
./vendor/bin/phpunit --testsuite Unit

# Run integration tests
./vendor/bin/phpunit --testsuite Integration

# Run static analysis (PHPStan level 9)
./vendor/bin/phpstan analyse

# Fix style issues (PHP-CS-Fixer)
./vendor/bin/php-cs-fixer fix
```

---

## Dependency Integration

**Composer Dependencies (Production):**
- `spiral/framework` ^3.0
- `spiral/roadrunner` ^3.0
- `ramsey/uuid` ^4.7
- `psr/log` ^2.0 | ^3.0
- `psr/container` ^2.0

**Development:**
- `phpunit/phpunit` ^10.0
- `phpstan/phpstan` ^1.10
- `friendsofphp/php-cs-fixer` ^3.25

---

## Key Files to Know

| File | Purpose |
|------|---------|
| `CODEBASE_INVENTORY.md` | This detailed inventory |
| `Kernel_Foundation/KERNEL_FOUNDATION_BLUEPRINT_INDEX.md` | Architecture overview |
| `Kernel_Foundation/KERNEL_FOUNDATION_BLUEPRINT_PART2.md` | Domain model design |
| `IMPLEMENTATION_STATUS.md` | Phase completion details |
| `composer.json` | Dependencies & autoload |
| `phpstan.neon` | Static analysis rules (level 9) |
| `phpunit.xml` | Test configuration |

---

## Phase Completion Checklist

✅ Phase 1: Core Exceptions & Result Semantics
✅ Phase 2: Identity & Governance Primitives
⏳ Phase 3: Temporal & Financial Primitives
🔲 Phase 4: Domain Model (AggregateRoot)
🔲 Phase 5+: Application & Infrastructure Layers

**Status:** Production ready for phases 1-2. Ready to begin Phase 3.

---

**Next Task:** Implement Phase 3 temporal and financial value objects per KERNEL_FOUNDATION_BLUEPRINT_PART2.md Section 3.
