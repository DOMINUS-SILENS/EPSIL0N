# EPSILONE KERNEL — PHYSICALLY BUILDABLE SCAFFOLD

**Status:** Exact file-by-file, folder-by-folder, namespace-by-namespace creation order
**Purpose:** This document answers: "What files do I create, in what order, with what exact code?"
**Target:** Executable immediately. No design. Just build.

---

# PART 0 — PHYSICAL STRUCTURE SKELETON

## Create the repo structure

```bash
mkdir -p packages/kernel/src
mkdir -p packages/kernel/tests/Unit
mkdir -p packages/kernel/tests/Integration
mkdir -p packages/kernel/tests/Fixture
mkdir -p packages/kernel/resources/sql
mkdir -p packages/kernel/resources/config
```

## Create base config files

### File 1: `packages/kernel/composer.json`

```json
{
  "name": "spiral-kernel/epsilone",
  "description": "EPSILONE ERP Kernel Foundation",
  "type": "library",
  "license": "MIT",
  "require": {
    "php": "^8.3",
    "spiral/framework": "^3.0",
    "spiral/roadrunner": "^2025.1",
     "nyholm/psr7": "^1.8",
    "ramsey/uuid": "^4.7"
  },
  "require-dev": {
    "phpunit/phpunit": "^11.0",
    "spiral/dev-tool": "^3.0",
    "phpstan/phpstan": "^1.10"
  },
  "autoload": {
    "psr-4": {
      "Spiral\\Kernel\\": "src/"
    }
  },
  "autoload-dev": {
    "psr-4": {
      "Spiral\\Kernel\\Tests\\": "tests/"
    }
  }
}
```

### File 2: `packages/kernel/phpunit.xml`

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/11.0/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
         failOnRisky="true"
         failOnWarning="true">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
    </testsuites>
    <coverage processUncoveredFiles="true">
        <include>
            <directory suffix=".php">src</directory>
        </include>
    </coverage>
</phpunit>
```

### File 3: `packages/kernel/phpstan.neon`

```neon
parameters:
    level: 9
    paths:
        - src
        - tests
    excludePaths:
        - src/Support
    ignoreErrors:
        -
            message: '#Property .* is never read#'
            path: src/Domain/**
```

### File 4: `packages/kernel/.env.example`

```env
DB_DRIVER=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=epsilone_kernel
DB_USER=postgres
DB_PASSWORD=password
```

---

# PHASE 1 — FAILURE & RESULT SEMANTICS

These files establish the law for all error handling and business outcomes.
**All other code depends on this.**

## File 5: `packages/kernel/src/Support/Exception/KernelException.php`

```php
<?php

namespace Spiral\Kernel\Support\Exception;

class KernelException extends \Exception
{
    protected string $code = 'KERNEL_ERROR';

    public function __construct(string $message = '', array $context = [])
    {
        parent::__construct($message);
    }
}
```

## File 6: `packages/kernel/src/Support/Exception/ValidationException.php`

```php
<?php

namespace Spiral\Kernel\Support\Exception;

class ValidationException extends KernelException
{
    protected string $code = 'VALIDATION_ERROR';

    public function __construct(
        string $message,
        protected array $errors = []
    ) {
        parent::__construct($message);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
```

## File 7: `packages/kernel/src/Support/Exception/ConcurrencyConflictException.php`

```php
<?php

namespace Spiral\Kernel\Support\Exception;

class ConcurrencyConflictException extends KernelException
{
    protected string $code = 'CONCURRENCY_CONFLICT';
}
```

## File 8: `packages/kernel/src/Support/Exception/AuthorizationException.php`

```php
<?php

namespace Spiral\Kernel\Support\Exception;

class AuthorizationException extends KernelException
{
    protected string $code = 'AUTHORIZATION_DENIED';
}
```

## File 9: `packages/kernel/src/Support/Exception/NotFoundException.php`

```php
<?php

namespace Spiral\Kernel\Support\Exception;

class NotFoundException extends KernelException
{
    protected string $code = 'NOT_FOUND';
}
```

## File 10: `packages/kernel/src/Support/Exception/TenantIsolationViolationException.php`

```php
<?php

namespace Spiral\Kernel\Support\Exception;

class TenantIsolationViolationException extends KernelException
{
    protected string $code = 'TENANT_ISOLATION_VIOLATION';
}
```

## File 11: `packages/kernel/src/Support/Exception/ClosedPeriodViolationException.php`

```php
<?php

namespace Spiral\Kernel\Support\Exception;

class ClosedPeriodViolationException extends KernelException
{
    protected string $code = 'CLOSED_PERIOD_VIOLATION';
}
```

## File 12: `packages/kernel/src/Support/Exception/BusinessRuleViolationException.php`

```php
<?php

namespace Spiral\Kernel\Support\Exception;

class BusinessRuleViolationException extends KernelException
{
    protected string $code = 'BUSINESS_RULE_VIOLATION';
}
```

## File 13: `packages/kernel/src/Support/Exception/InvalidStateTransitionException.php`

```php
<?php

namespace Spiral\Kernel\Support\Exception;

class InvalidStateTransitionException extends KernelException
{
    protected string $code = 'INVALID_STATE_TRANSITION';
}
```

## File 14: `packages/kernel/src/Domain/Shared/Result/Result.php`

**This is the canonical business outcome container.**

```php
<?php

namespace Spiral\Kernel\Domain\Shared\Result;

/**
 * @template TData
 * @template TError
 */
final class Result
{
    private function __construct(
        private bool $isSuccess,
        private mixed $data,
        private mixed $error
    ) {}

    /**
     * @template T
     * @param T $data
     * @return Result<T, never>
     */
    public static function success(mixed $data): self
    {
        return new self(true, $data, null);
    }

    /**
     * @template E
     * @param E $error
     * @return Result<never, E>
     */
    public static function failure(mixed $error): self
    {
        return new self(false, null, $error);
    }

    public function isSuccess(): bool
    {
        return $this->isSuccess;
    }

    public function isFailed(): bool
    {
        return !$this->isSuccess;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function getError(): mixed
    {
        return $this->error;
    }

    public function getOrThrow(\Exception $exception = null): mixed
    {
        if (!$this->isSuccess) {
            throw $exception ?? new \RuntimeException('Result was a failure');
        }
        return $this->data;
    }
}
```

---

# PHASE 2 — IDENTITY & BASE VALUE OBJECTS

These are the primitives everything depends on.

## File 15: `packages/kernel/src/Domain/Shared/ValueObject/ValueObject.php`

```php
<?php

namespace Spiral\Kernel\Domain\Shared\ValueObject;

abstract class ValueObject
{
    /**
     * Return all components that determine equality.
     */
    abstract protected function getEqualityComponents(): iterable;

    public function equals(self $other): bool
    {
        if ($other::class !== static::class) {
            return false;
        }

        $thisComponents = \iterator_to_array($this->getEqualityComponents());
        $otherComponents = \iterator_to_array($other->getEqualityComponents());

        return $thisComponents === $otherComponents;
    }

    public function hashCode(): int
    {
        $hash = 1;
        foreach ($this->getEqualityComponents() as $component) {
            $componentHash = match (true) {
                $component === null => 0,
                \is_object($component) => \spl_object_hash($component),
                \is_bool($component) => $component ? 1 : 0,
                \is_int($component) => $component,
                \is_float($component) => (int)$component,
                default => \crc32((string)$component),
            };
            $hash = 31 * $hash + $componentHash;
        }
        return $hash;
    }
}
```

## File 16: `packages/kernel/src/Domain/Shared/Identity/TenantId.php`

```php
<?php

namespace Spiral\Kernel\Domain\Shared\Identity;

use Spiral\Kernel\Domain\Shared\ValueObject\ValueObject;
use Spiral\Kernel\Support\Exception\ValidationException;

final class TenantId extends ValueObject
{
    private string $value;

    public function __construct(string $value)
    {
        if (!$this->isValidUuid($value)) {
            throw new ValidationException("Invalid TenantId: {$value}");
        }
        $this->value = $value;
    }

    public static function generate(): self
    {
        return new self(\Ramsey\Uuid\Uuid::uuid4()->toString());
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    protected function getEqualityComponents(): iterable
    {
        yield $this->value;
    }

    private function isValidUuid(string $uuid): bool
    {
        return \Ramsey\Uuid\Uuid::isValid($uuid);
    }
}
```

## File 17: `packages/kernel/src/Domain/Shared/Identity/UserId.php`

```php
<?php

namespace Spiral\Kernel\Domain\Shared\Identity;

use Spiral\Kernel\Domain\Shared\ValueObject\ValueObject;
use Spiral\Kernel\Support\Exception\ValidationException;

final class UserId extends ValueObject
{
    private string $value;

    public function __construct(string $value)
    {
        if (!\Ramsey\Uuid\Uuid::isValid($value)) {
            throw new ValidationException("Invalid UserId: {$value}");
        }
        $this->value = $value;
    }

    public static function generate(): self
    {
        return new self(\Ramsey\Uuid\Uuid::uuid4()->toString());
    }

    public function toString(): string
    {
        return $this->value;
    }

    protected function getEqualityComponents(): iterable
    {
        yield $this->value;
    }
}
```

## File 18: `packages/kernel/src/Domain/Shared/Identity/ActorId.php`

```php
<?php

namespace Spiral\Kernel\Domain\Shared\Identity;

use Spiral\Kernel\Domain\Shared\ValueObject\ValueObject;
use Spiral\Kernel\Support\Exception\ValidationException;

final class ActorId extends ValueObject
{
    private string $value;

    public function __construct(string $value)
    {
        if (!\Ramsey\Uuid\Uuid::isValid($value)) {
            throw new ValidationException("Invalid ActorId: {$value}");
        }
        $this->value = $value;
    }

    public static function generate(): self
    {
        return new self(\Ramsey\Uuid\Uuid::uuid4()->toString());
    }

    public function toString(): string
    {
        return $this->value;
    }

    protected function getEqualityComponents(): iterable
    {
        yield $this->value;
    }
}
```

## File 19: `packages/kernel/src/Domain/Shared/Identity/EventId.php`

```php
<?php

namespace Spiral\Kernel\Domain\Shared\Identity;

use Spiral\Kernel\Domain\Shared\ValueObject\ValueObject;
use Spiral\Kernel\Support\Exception\ValidationException;

final class EventId extends ValueObject
{
    private string $value;

    public function __construct(string $value)
    {
        if (!\Ramsey\Uuid\Uuid::isValid($value)) {
            throw new ValidationException("Invalid EventId: {$value}");
        }
        $this->value = $value;
    }

    public static function generate(): self
    {
        return new self(\Ramsey\Uuid\Uuid::uuid4()->toString());
    }

    public function toString(): string
    {
        return $this->value;
    }

    protected function getEqualityComponents(): iterable
    {
        yield $this->value;
    }
}
```

## File 20: `packages/kernel/src/Domain/Shared/Identity/DocumentId.php`

```php
<?php

namespace Spiral\Kernel\Domain\Shared\Identity;

use Spiral\Kernel\Domain\Shared\ValueObject\ValueObject;
use Spiral\Kernel\Support\Exception\ValidationException;

final class DocumentId extends ValueObject
{
    private string $value;

    public function __construct(string $value)
    {
        if (!\Ramsey\Uuid\Uuid::isValid($value)) {
            throw new ValidationException("Invalid DocumentId: {$value}");
        }
        $this->value = $value;
    }

    public static function generate(): self
    {
        return new self(\Ramsey\Uuid\Uuid::uuid4()->toString());
    }

    public function toString(): string
    {
        return $this->value;
    }

    protected function getEqualityComponents(): iterable
    {
        yield $this->value;
    }
}
```

## File 21: `packages/kernel/src/Domain/Shared/Identity/CorrelationId.php`

```php
<?php

namespace Spiral\Kernel\Domain\Shared\Identity;

use Spiral\Kernel\Domain\Shared\ValueObject\ValueObject;

final class CorrelationId extends ValueObject
{
    private string $value;

    public function __construct(string $value = null)
    {
        $this->value = $value ?? \Ramsey\Uuid\Uuid::uuid4()->toString();
    }

    public static function generate(): self
    {
        return new self();
    }

    public function toString(): string
    {
        return $this->value;
    }

    protected function getEqualityComponents(): iterable
    {
        yield $this->value;
    }
}
```

## File 22: `packages/kernel/src/Domain/Shared/Identity/CausationId.php`

```php
<?php

namespace Spiral\Kernel\Domain\Shared\Identity;

use Spiral\Kernel\Domain\Shared\ValueObject\ValueObject;

final class CausationId extends ValueObject
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function toString(): string
    {
        return $this->value;
    }

    protected function getEqualityComponents(): iterable
    {
        yield $this->value;
    }
}
```

## File 23: `packages/kernel/src/Domain/Shared/Governance/TenantSlug.php`

```php
<?php

namespace Spiral\Kernel\Domain\Shared\Governance;

use Spiral\Kernel\Domain\Shared\ValueObject\ValueObject;
use Spiral\Kernel\Support\Exception\ValidationException;

final class TenantSlug extends ValueObject
{
    private string $value;

    public function __construct(string $slug)
    {
        $slug = \strtolower($slug);

        // Allow: single alphanumeric OR alphanumeric-dash-alphanumeric
        if (!\preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/', $slug)) {
            throw new ValidationException('Invalid tenant slug: ' . $slug);
        }
        $this->value = $slug;
    }

    public function toString(): string
    {
        return $this->value;
    }

    protected function getEqualityComponents(): iterable
    {
        yield $this->value;
    }
}
```

## File 24: `packages/kernel/src/Domain/Shared/Governance/EmailAddress.php`

```php
<?php

namespace Spiral\Kernel\Domain\Shared\Governance;

use Spiral\Kernel\Domain\Shared\ValueObject\ValueObject;
use Spiral\Kernel\Support\Exception\ValidationException;

final class EmailAddress extends ValueObject
{
    private string $value;

    public function __construct(string $email)
    {
        if (!\filter_var($email, \FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException("Invalid email: {$email}");
        }
        $this->value = \strtolower($email);
    }

    public function toString(): string
    {
        return $this->value;
    }

    protected function getEqualityComponents(): iterable
    {
        yield $this->value;
    }
}
```

## File 25: `packages/kernel/src/Domain/Shared/Governance/ResourceReference.php`

```php
<?php

namespace Spiral\Kernel\Domain\Shared\Governance;

use Spiral\Kernel\Domain\Shared\ValueObject\ValueObject;

final class ResourceReference extends ValueObject
{
    public function __construct(
        private string $context,
        private string $resourceType,
        private string $resourceId
    ) {}

    public function getContext(): string
    {
        return $this->context;
    }

    public function getResourceType(): string
    {
        return $this->resourceType;
    }

    public function getResourceId(): string
    {
        return $this->resourceId;
    }

    public function toString(): string
    {
        return "{$this->context}:{$this->resourceType}:{$this->resourceId}";
    }

    protected function getEqualityComponents(): iterable
    {
        yield $this->context;
        yield $this->resourceType;
        yield $this->resourceId;
    }
}
```

---

# PHASE 3 — TEMPORAL & NUMERIC PRIMITIVES

## File 26: `packages/kernel/src/Domain/Temporal/Timestamp.php`

```php
<?php

namespace Spiral\Kernel\Domain\Temporal;

use Spiral\Kernel\Domain\Shared\ValueObject\ValueObject;

final class Timestamp extends ValueObject
{
    private \DateTimeImmutable $dt;

    public function __construct(\DateTimeInterface $dt = null)
    {
        if ($dt === null) {
            $dt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        }
        $this->dt = $dt->setTimezone(new \DateTimeZone('UTC'));
    }

    public static function now(): self
    {
        return new self();
    }

    public function toIso8601(): string
    {
        return $this->dt->format('c');
    }

    public function toDateTime(): \DateTimeImmutable
    {
        return $this->dt;
    }

    public function getUnixTimestamp(): int
    {
        return (int)$this->dt->format('U');
    }

    protected function getEqualityComponents(): iterable
    {
        yield $this->dt->format('Y-m-d H:i:s.u');
    }
}
```

## File 27: `packages/kernel/src/Domain/Temporal/BusinessDate.php`

```php
<?php

namespace Spiral\Kernel\Domain\Temporal;

use Spiral\Kernel\Domain\Shared\ValueObject\ValueObject;
use Spiral\Kernel\Support\Exception\ValidationException;

final class BusinessDate extends ValueObject
{
    private \DateTimeImmutable $date;

    public function __construct(string $iso8601)
    {
        try {
            $this->date = new \DateTimeImmutable($iso8601);
        } catch (\Exception $e) {
            throw new ValidationException("Invalid BusinessDate: {$iso8601}");
        }
    }

    public static function fromDateTime(\DateTimeInterface $dt): self
    {
        return new self($dt->format('Y-m-d'));
    }

    public static function today(): self
    {
        return self::fromDateTime(new \DateTimeImmutable('now'));
    }

    public function format(string $format = 'Y-m-d'): string
    {
        return $this->date->format($format);
    }

    public function toDateTime(): \DateTimeImmutable
    {
        return $this->date->setTime(0, 0, 0);
    }

    public function isBefore(self $other): bool
    {
        return $this->date < $other->date;
    }

    public function isAfter(self $other): bool
    {
        return $this->date > $other->date;
    }

    protected function getEqualityComponents(): iterable
    {
        yield $this->date->format('Y-m-d');
    }
}
```

## File 28: `packages/kernel/src/Domain/Temporal/BusinessPeriod.php`

```php
<?php

namespace Spiral\Kernel\Domain\Temporal;

use Spiral\Kernel\Domain\Shared\ValueObject\ValueObject;
use Spiral\Kernel\Support\Exception\ValidationException;

final class BusinessPeriod extends ValueObject
{
    public function __construct(
        private BusinessDate $openDate,
        private BusinessDate $closeDate,
        private bool $isClosed = false
    ) {
        if ($closeDate->isBefore($openDate)) {
            throw new ValidationException('Close date must be >= open date');
        }
    }

    public function getOpenDate(): BusinessDate
    {
        return $this->openDate;
    }

    public function getCloseDate(): BusinessDate
    {
        return $this->closeDate;
    }

    public function isClosed(): bool
    {
        return $this->isClosed;
    }

    public function contains(BusinessDate $date): bool
    {
        return !$date->isBefore($this->openDate) && !$date->isAfter($this->closeDate);
    }

    protected function getEqualityComponents(): iterable
    {
        yield $this->openDate;
        yield $this->closeDate;
        yield $this->isClosed;
    }
}
```

## File 29: `packages/kernel/src/Domain/Temporal/TimezoneId.php`

```php
<?php

namespace Spiral\Kernel\Domain\Temporal;

use Spiral\Kernel\Domain\Shared\ValueObject\ValueObject;
use Spiral\Kernel\Support\Exception\ValidationException;

final class TimezoneId extends ValueObject
{
    private string $value;

    public function __construct(string $tzIdentifier = 'UTC')
    {
        if (!\DateTimeZone::isValidId($tzIdentifier)) {
            throw new ValidationException("Invalid timezone: {$tzIdentifier}");
        }
        $this->value = $tzIdentifier;
    }

    public static function utc(): self
    {
        return new self('UTC');
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function toDateTimeZone(): \DateTimeZone
    {
        return new \DateTimeZone($this->value);
    }

    protected function getEqualityComponents(): iterable
    {
        yield $this->value;
    }
}
```

## File 30: `packages/kernel/src/Domain/Shared/Financial/Money.php`

```php
<?php

namespace Spiral\Kernel\Domain\Shared\Financial;

use Spiral\Kernel\Domain\Shared\ValueObject\ValueObject;
use Spiral\Kernel\Support\Exception\ValidationException;

final class Money extends ValueObject
{
    private int $minorAmount;

    public function __construct(int $minorAmount, CurrencyCode $currency)
    {
        $this->minorAmount = $minorAmount;
        $this->currency = $currency;
    }

    public static function fromMajor(int|float $majorAmount, CurrencyCode $currency): self
    {
        $cents = (int)\round($majorAmount * 100);
        return new self($cents, $currency);
    }

    public function getMinorAmount(): int
    {
        return $this->minorAmount;
    }

    public function getMajorAmount(): float
    {
        return $this->minorAmount / 100;
    }

    public function get CurrencyCode(): CurrencyCode
    {
        return $this->currency;
    }

    public function add(self $other): self
    {
        if (!$this->currency->equals($other->currency)) {
            throw new ValidationException('Cannot add money of different currencies');
        }
        return new self($this->minorAmount + $other->minorAmount, $this->currency);
    }

    public function subtract(self $other): self
    {
        if (!$this->currency->equals($other->currency)) {
            throw new ValidationException('Cannot subtract money of different currencies');
        }
        return new self($this->minorAmount - $other->minorAmount, $this->currency);
    }

    public function multiply(int|float $factor): self
    {
        $result = (int)\round($this->minorAmount * $factor);
        return new self($result, $this->currency);
    }

    public function isPositive(): bool
    {
        return $this->minorAmount > 0;
    }

    public function isNegative(): bool
    {
        return $this->minorAmount < 0;
    }

    public function isZero(): bool
    {
        return $this->minorAmount === 0;
    }

    protected function getEqualityComponents(): iterable
    {
        yield $this->minorAmount;
        yield $this->currency;
    }

    public function __construct(
        int $minorAmount,
        private CurrencyCode $currency
    ) {
        $this->minorAmount = $minorAmount;
    }
}
```

## File 31: `packages/kernel/src/Domain/Shared/Financial/CurrencyCode.php`

```php
<?php

namespace Spiral\Kernel\Domain\Shared\Financial;

use Spiral\Kernel\Domain\Shared\ValueObject\ValueObject;
use Spiral\Kernel\Support\Exception\ValidationException;

final class CurrencyCode extends ValueObject
{
    private string $code;

    public function __construct(string $code)
    {
        $code = \strtoupper($code);
        if (\strlen($code) !== 3 || !\ctype_alpha($code)) {
            throw new ValidationException("Invalid currency code: {$code}");
        }
        $this->code = $code;
    }

    public function toString(): string
    {
        return $this->code;
    }

    protected function getEqualityComponents(): iterable
    {
        yield $this->code;
    }
}
```

## File 32: `packages/kernel/src/Domain/Shared/Measurement/Quantity.php`

```php
<?php

namespace Spiral\Kernel\Domain\Shared\Measurement;

use Spiral\Kernel\Domain\Shared\ValueObject\ValueObject;
use Spiral\Kernel\Support\Exception\ValidationException;

final class Quantity extends ValueObject
{
    public function __construct(
        private int $minorAmount,
        private UnitOfMeasure $unit
    ) {}

    public function getAmount(): int
    {
        return $this->minorAmount;
    }

    public function getUnit(): UnitOfMeasure
    {
        return $this->unit;
    }

    public function add(self $other): self
    {
        if (!$this->unit->equals($other->unit)) {
            throw new ValidationException('Cannot add quantities with different units');
        }
        return new self($this->minorAmount + $other->minorAmount, $this->unit);
    }

    public function subtract(self $other): self
    {
        if (!$this->unit->equals($other->unit)) {
            throw new ValidationException('Cannot subtract quantities with different units');
        }
        return new self($this->minorAmount - $other->minorAmount, $this->unit);
    }

    public function multiplyBy(int|float $factor): self
    {
        $result = (int)\round($this->minorAmount * $factor);
        return new self($result, $this->unit);
    }

    public function isNegative(): bool
    {
        return $this->minorAmount < 0;
    }

    public function isPositive(): bool
    {
        return $this->minorAmount > 0;
    }

    public function isZero(): bool
    {
        return $this->minorAmount === 0;
    }

    protected function getEqualityComponents(): iterable
    {
        yield $this->minorAmount;
        yield $this->unit;
    }
}
```

## File 33: `packages/kernel/src/Domain/Shared/Measurement/UnitOfMeasure.php`

```php
<?php

namespace Spiral\Kernel\Domain\Shared\Measurement;

use Spiral\Kernel\Domain\Shared\ValueObject\ValueObject;

final class UnitOfMeasure extends ValueObject
{
    public function __construct(
        private string $symbol,
        private string $baseUnit
    ) {}

    public function getSymbol(): string
    {
        return $this->symbol;
    }

    public function getBaseUnit(): string
    {
        return $this->baseUnit;
    }

    public function equals(self $other): bool
    {
        return $this->symbol === $other->symbol && $this->baseUnit === $other->baseUnit;
    }

    protected function getEqualityComponents(): iterable
    {
        yield $this->symbol;
        yield $this->baseUnit;
    }
}
```

---

# PHASE 4 — EVENT LAW

## File 34: `packages/kernel/src/Domain/Shared/Event/DomainEvent.php`

**This is the canonical event law. Nothing else is an event.**

```php
<?php

namespace Spiral\Kernel\Domain\Shared\Event;

use Spiral\Kernel\Domain\Shared\Identity\{
    EventId,
    TenantId,
    CorrelationId,
    CausationId
};
use Spiral\Kernel\Domain\Temporal\Timestamp;

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

## File 35: `packages/kernel/src/Domain/Shared/Event/StoredEvent.php`

```php
<?php

namespace Spiral\Kernel\Domain\Shared\Event;

use Spiral\Kernel\Domain\Shared\Identity\{
    EventId,
    TenantId,
    CorrelationId,
    CausationId
};
use Spiral\Kernel\Domain\Temporal\Timestamp;

final class StoredEvent
{
    public function __construct(
        public readonly EventId $eventId,
        public readonly string $aggregateId,
        public readonly TenantId $tenantId,
        public readonly CorrelationId $correlationId,
        public readonly CausationId $causationId,
        public readonly Timestamp $occurredAt,
        public readonly int $schemaVersion,
        public readonly string $eventType,
        public readonly array $eventPayload,
        public readonly int $sequenceNumber,
        public readonly Timestamp $storedAt
    ) {}
}
```

---

# PHASE 5 — AGGREGATE LAW

## File 36: `packages/kernel/src/Domain/Shared/Aggregate/AggregateRoot.php`

**This is the covenant every aggregate must obey.**

```php
<?php

namespace Spiral\Kernel\Domain\Shared\Aggregate;

use Spiral\Kernel\Domain\Shared\Identity\TenantId;
use Spiral\Kernel\Domain\Shared\Event\DomainEvent;

abstract class AggregateRoot
{
    protected int $version = 0;
    protected bool $isDeleted = false;

    /** @var DomainEvent[] */
    private array $uncommittedEvents = [];

    protected function __construct(
        protected string $id,
        protected TenantId $tenantId
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getTenantId(): TenantId
    {
        return $this->tenantId;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function isDeleted(): bool
    {
        return $this->isDeleted;
    }

    /**
     * Subclasses must implement state reconstruction from event.
     */
    abstract protected function when(DomainEvent $event): void;

    /**
     * Emit an event: apply it AND record it for persistence.
     */
    protected function raise(DomainEvent $event): void
    {
        $this->when($event);
        $this->uncommittedEvents[] = $event;
    }

    /**
     * Return all unsaved events, then clear the buffer.
     */
    public function releaseEvents(): array
    {
        $events = $this->uncommittedEvents;
        $this->uncommittedEvents = [];
        return $events;
    }

    /**
     * Get uncommitted events without clearing.
     */
    public function getUncommittedEvents(): array
    {
        return $this->uncommittedEvents;
    }

    /**
     * Hook: called before persistence.
     */
    public function onBeforePersist(): void
    {
    }

    /**
     * Hook: called after rehydration.
     */
    public function onAfterRehydrate(): void
    {
    }
}
```

## File 37: `packages/kernel/src/Domain/Shared/Aggregate/Entity.php`

```php
<?php

namespace Spiral\Kernel\Domain\Shared\Aggregate;

abstract class Entity
{
    protected function __construct(protected string $id) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function equals(self $other): bool
    {
        return $other::class === static::class && $other->id === $this->id;
    }
}
```

---

# PHASE 6 — DOMAIN CONTRACTS (INFRASTRUCTURE-FACING INTERFACES)

## File 38: `packages/kernel/src/Infrastructure/Contract/Persistence/IRepository.php`

```php
<?php

namespace Spiral\Kernel\Infrastructure\Contract\Persistence;

use Spiral\Kernel\Domain\Shared\Aggregate\AggregateRoot;
use Spiral\Kernel\Domain\Shared\Identity\TenantId;

/**
 * @template TAgg of AggregateRoot
 * @template TId
 */
interface IRepository
{
    /**
     * Get aggregate by ID, scoped to tenant.
     *
     * @throws \Spiral\Kernel\Support\Exception\NotFoundException
     * @throws \Spiral\Kernel\Support\Exception\TenantIsolationViolationException
     */
    public function getById(string $id, TenantId $tenantId): AggregateRoot;

    /**
     * Insert new aggregate.
     *
     * @throws \Spiral\Kernel\Support\Exception\ValidationException
     */
    public function add(AggregateRoot $aggregate): void;

    /**
     * Persist changes to aggregate (version check included).
     *
     * @throws \Spiral\Kernel\Support\Exception\ConcurrencyConflictException
     */
    public function save(AggregateRoot $aggregate): void;

    /**
     * Remove aggregate (soft or hard, domain-dependent).
     */
    public function remove(AggregateRoot $aggregate): void;

    /**
     * Check if aggregate exists.
     */
    public function exists(string $id, TenantId $tenantId): bool;
}
```

## File 39: `packages/kernel/src/Infrastructure/Contract/Persistence/IUnitOfWork.php`

```php
<?php

namespace Spiral\Kernel\Infrastructure\Contract\Persistence;

interface IUnitOfWork
{
    /**
     * Execute operation transactionally.
     * All persistence operations inside $operation are atomic:
     * - aggregate persistence
     * - event persistence
     * - outbox persistence
     * - audit persistence
     *
     * All or nothing.
     */
    public function transactional(callable $operation): mixed;

    /**
     * Get repository for aggregate type (scoped to transaction).
     */
    public function getRepository(string $aggregateClass): IRepository;
}
```

## File 40: `packages/kernel/src/Infrastructure/Contract/Eventing/IEventStore.php`

```php
<?php

namespace Spiral\Kernel\Infrastructure\Contract\Eventing;

use Spiral\Kernel\Domain\Shared\Event\DomainEvent;
use Spiral\Kernel\Domain\Shared\Event\StoredEvent;
use Spiral\Kernel\Domain\Shared\Identity\TenantId;

interface IEventStore
{
    /**
     * Append events to stream (optimistic version check).
     *
     * @throws \Spiral\Kernel\Support\Exception\ConcurrencyConflictException
     */
    public function append(
        TenantId $tenantId,
        string $streamId,
        int $expectedVersion,
        DomainEvent ...$events
    ): void;

    /**
     * Load event stream for aggregate.
     */
    public function getStream(
        TenantId $tenantId,
        string $streamId,
        int $fromVersion = 0
    ): array;

    /**
     * Load event by ID (for debugging/audit).
     */
    public function getEventById(string $eventId): ?StoredEvent;

    /**
     * Get all events for tenant in order (for projections, sagas).
     */
    public function getAllEventsByTenant(
        TenantId $tenantId,
        int $afterSequenceNumber = 0
    ): array;
}
```

## File 41: `packages/kernel/src/Infrastructure/Contract/Eventing/ISnapshotStore.php`

```php
<?php

namespace Spiral\Kernel\Infrastructure\Contract\Eventing;

use Spiral\Kernel\Domain\Shared\Identity\TenantId;

interface ISnapshotStore
{
    /**
     * Save aggregate state snapshot.
     */
    public function save(
        TenantId $tenantId,
        string $aggregateId,
        int $version,
        mixed $state
    ): void;

    /**
     * Load snapshot (return null if doesn't exist).
     */
    public function load(TenantId $tenantId, string $aggregateId): ?array;

    /**
     * Get version of snapshot.
     */
    public function getSnapshotVersion(TenantId $tenantId, string $aggregateId): ?int;
}
```

## File 42: `packages/kernel/src/Infrastructure/Contract/Eventing/IOutboxStore.php`

```php
<?php

namespace Spiral\Kernel\Infrastructure\Contract\Eventing;

use Spiral\Kernel\Domain\Shared\Identity\TenantId;

interface IOutboxStore
{
    /**
     * Enqueue message for async publishing (called during aggregate save).
     */
    public function enqueue(OutboxMessage $message): void;

    /**
     * Dequeue messages for publishing.
     */
    public function dequeue(int $batchSize = 100): array;

    /**
     * Mark message as published.
     */
    public function markPublished(string $messageId): void;

    /**
     * Mark message as failed (will retry).
     */
    public function markFailed(string $messageId, string $error): void;
}

final class OutboxMessage
{
    public function __construct(
        public readonly string $id,
        public readonly TenantId $tenantId,
        public readonly string $messageType,
        public readonly array $payload
    ) {}
}
```

## File 43: `packages/kernel/src/Infrastructure/Contract/Eventing/IProcessedMessageStore.php`

```php
<?php

namespace Spiral\Kernel\Infrastructure\Contract\Eventing;

interface IProcessedMessageStore
{
    /**
     * Check if we've already processed this message (idempotency).
     */
    public function hasProcessed(string $messageId): bool;

    /**
     * Mark message as processed.
     */
    public function markProcessed(string $messageId): void;
}
```

## File 44: `packages/kernel/src/Infrastructure/Contract/Eventing/IIdempotencyStore.php`

```php
<?php

namespace Spiral\Kernel\Infrastructure\Contract\Eventing;

interface IIdempotencyStore
{
    /**
     * Check if command with this key was already executed.
     */
    public function hasKey(string $key): bool;

    /**
     * Store result of command execution (for replay).
     */
    public function storeResult(string $key, mixed $result, int $ttlSeconds = 3600): void;

    /**
     * Retrieve cached result (for idempotency).
     */
    public function getResult(string $key): mixed;
}
```

## File 45: `packages/kernel/src/Infrastructure/Contract/Security/ISecurityContext.php`

```php
<?php

namespace Spiral\Kernel\Infrastructure\Contract\Security;

use Spiral\Kernel\Domain\Shared\Identity\{ActorId, TenantId};

interface ISecurityContext
{
    public function getActorId(): ActorId;

    public function getTenantId(): TenantId;

    public function isAuthenticated(): bool;
}
```

## File 46: `packages/kernel/src/Infrastructure/Contract/Security/IAuthorizationService.php`

```php
<?php

namespace Spiral\Kernel\Infrastructure\Contract\Security;

use Spiral\Kernel\Support\Exception\AuthorizationException;

interface IAuthorizationService
{
    /**
     * Authorize action.
     *
     * @throws AuthorizationException
     */
    public function authorize(IActionRequirement $requirement, ISecurityContext $context): void;

    /**
     * Check if authorized (without throwing).
     */
    public function canAuthorize(IActionRequirement $requirement, ISecurityContext $context): bool;
}

interface IActionRequirement
{
}
```

## File 47: `packages/kernel/src/Infrastructure/Contract/Temporal/IBusinessCalendar.php`

```php
<?php

namespace Spiral\Kernel\Infrastructure\Contract\Temporal;

use Spiral\Kernel\Domain\Shared\Identity\TenantId;
use Spiral\Kernel\Domain\Temporal\{BusinessDate, BusinessPeriod};

interface IBusinessCalendar
{
    /**
     * Check if date is open for posting.
     */
    public function canPost(BusinessDate $date, TenantId $tenantId): bool;

    /**
     * Get period containing date.
     */
    public function getPeriod(BusinessDate $date, TenantId $tenantId): BusinessPeriod;

    /**
     * Check if period is closed.
     */
    public function isPeriodClosed(BusinessPeriod $period, TenantId $tenantId): bool;
}
```

## File 48: `packages/kernel/src/Infrastructure/Contract/Clock/IClock.php`

```php
<?php

namespace Spiral\Kernel\Infrastructure\Contract\Clock;

use Spiral\Kernel\Domain\Temporal\{BusinessDate, Timestamp, TimezoneId};

interface IClock
{
    public function now(): Timestamp;

    public function businessDate(TimezoneId $tz = null): BusinessDate;
}
```

## File 49: `packages/kernel/src/Infrastructure/Contract/Audit/IAuditTrail.php`

```php
<?php

namespace Spiral\Kernel\Infrastructure\Contract\Audit;

use Spiral\Kernel\Domain\Shared\Identity\{ActorId, TenantId, CorrelationId};
use Spiral\Kernel\Domain\Temporal\{BusinessDate, Timestamp};

interface IAuditTrail
{
    /**
     * Record audit entry for state change.
     */
    public function record(AuditEntry $entry): void;

    /**
     * Retrieve audit entries for aggregate.
     */
    public function getEntriesForAggregate(string $aggregateId, TenantId $tenantId): array;

    /**
     * Retrieve entries by correlation (entire transaction).
     */
    public function getEntriesByCorrelation(CorrelationId $correlationId): array;
}

final class AuditEntry
{
    public function __construct(
        public readonly ActorId $actorId,
        public readonly TenantId $tenantId,
        public readonly string $commandType,
        public readonly string $aggregateId,
        public readonly string $aggregateType,
        public readonly CorrelationId $correlationId,
        public readonly ?array $stateBefore,
        public readonly ?array $stateAfter,
        public readonly Timestamp $occurredAt,
        public readonly BusinessDate $businessDate,
        public readonly ?string $reason = null,
        public readonly string $outcome = 'SUCCESS',
    ) {}
}
```

---

# PHASE 7 — APPLICATION CONTRACTS

## File 50: `packages/kernel/src/Application/Contract/ICommand.php`

```php
<?php

namespace Spiral\Kernel\Application\Contract;

/**
 * @template TResult
 */
interface ICommand
{
    public function getIdempotencyKey(): ?string;
}
```

## File 51: `packages/kernel/src/Application/Contract/IQuery.php`

```php
<?php

namespace Spiral\Kernel\Application\Contract;

/**
 * @template TResult
 */
interface IQuery
{
}
```

## File 52: `packages/kernel/src/Application/Contract/ICommandHandler.php`

```php
<?php

namespace Spiral\Kernel\Application\Contract;

use Spiral\Kernel\Domain\Shared\Result\Result;

/**
 * @template TCommand of ICommand
 * @template TResult
 */
interface ICommandHandler
{
    /**
     * Handle command.
     * Return Result (success or business failure).
     * Throw exception only for infrastructure/programming faults.
     */
    public function handle(ICommand $command): Result;
}
```

## File 53: `packages/kernel/src/Application/Contract/IQueryHandler.php`

```php
<?php

namespace Spiral\Kernel\Application\Contract;

use Spiral\Kernel\Domain\Shared\Result\Result;

/**
 * @template TQuery of IQuery
 * @template TResult
 */
interface IQueryHandler
{
    /**
     * Handle query (read-only, idempotent).
     */
    public function handle(IQuery $query): Result;
}
```

##File 54: `packages/kernel/src/Application/Contract/IValidator.php`

```php
<?php

namespace Spiral\Kernel\Application\Contract;

interface IValidator
{
    /**
     * Validate input.
     * Return ValidationResult (may have errors).
     */
    public function validate(mixed $input): ValidationResult;
}

final class ValidationResult
{
    /** @var ValidationError[] */
    private array $errors = [];

    public function addError(string $field, string $code, string $message): self
    {
        $this->errors[] = new ValidationError($field, $code, $message);
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

final class ValidationError
{
    public function __construct(
        public readonly string $field,
        public readonly string $code,
        public readonly string $message
    ) {}
}
```

---

# CONTINUE: PHASE 8-14 STRUCTURE (FOLDERS ONLY)

Create these folder structures (files will follow in next phase):

```bash
# Phase 8: Delivery Safety (already partially done above)
mkdir -p packages/kernel/src/Infrastructure/Contract/Eventing

# Phase 9: Kernel-Owned Governance Models
mkdir -p packages/kernel/src/Domain/Governance/Tenant
mkdir -p packages/kernel/src/Domain/Governance/Approval
mkdir -p packages/kernel/src/Domain/Governance/Workflow

# Phase 10: Behaviors / Policies / Sagas
mkdir -p packages/kernel/src/Application/Behavior/Validation
mkdir -p packages/kernel/src/Application/Behavior/Authorization
mkdir -p packages/kernel/src/Application/Behavior/Audit
mkdir -p packages/kernel/src/Application/Behavior/Idempotency
mkdir -p packages/kernel/src/Application/Policy
mkdir -p packages/kernel/src/Application/Saga

# Phase 11: Diagnostics Contracts
mkdir -p packages/kernel/src/Infrastructure/Contract/Diagnostics

# Phase 12: PostgreSQL Implementations
mkdir -p packages/kernel/src/Infrastructure/Persistence/EventStore
mkdir -p packages/kernel/src/Infrastructure/Persistence/Repository
mkdir -p packages/kernel/src/Infrastructure/Persistence/Snapshot
mkdir -p packages/kernel/src/Infrastructure/Persistence/UnitOfWork
mkdir -p packages/kernel/src/Infrastructure/Eventing/Outbox
mkdir -p packages/kernel/src/Infrastructure/Eventing/Inbox
mkdir -p packages/kernel/src/Infrastructure/Serialization

# Phase 13: Spiral Integration
mkdir -p packages/kernel/src/Infrastructure/Spiral/Bootloader
mkdir -p packages/kernel/src/Infrastructure/Spiral/Middleware
mkdir -p packages/kernel/src/Infrastructure/Spiral/Interceptor

# Phase 14: Diagnostics Implementation
mkdir -p packages/kernel/src/Diagnostics/Replay
mkdir -p packages/kernel/src/Diagnostics/Verification
mkdir -p packages/kernel/tests/Unit/Domain
mkdir -p packages/kernel/tests/Unit/Application
mkdir -p packages/kernel/tests/Integration/EventStore
mkdir -p packages/kernel/tests/Integration/Concurrency
mkdir -p packages/kernel/tests/Fixture/Aggregate
mkdir -p packages/kernel/tests/Fixture/Event
```

---

# SUMMARY — FILES 1-54 CREATED

| Phase | Count | Files | Status |
|-------|-------|-------|--------|
| 0 | 4 | composer.json, phpunit.xml, phpstan.neon, .env.example | ✓ Complete |
| 1 | 9 | Exception taxonomy + Result | ✓ Complete |
| 2 | 10 | ValueObject base + 8 Identity VOs + Governance VOs | ✓ Complete |
| 3 | 8 | Timestamp, BusinessDate, BusinessPeriod, TimezoneId, Money, CurrencyCode, Quantity, UnitOfMeasure | ✓ Complete |
| 4 | 2 | DomainEvent, StoredEvent | ✓ Complete |
| 5 | 2 | AggregateRoot, Entity | ✓ Complete |
| 6 | 12 | IRepository, IUnitOfWork, IEventStore, ISnapshotStore, IOutboxStore, IProcessedMessageStore, IIdempotencyStore, ISecurityContext, IAuthorizationService, IBusinessCalendar, IClock, IAuditTrail | ✓ Complete |
| 7 | 5 | ICommand, IQuery, ICommandHandler, IQueryHandler, IValidator | ✓ Complete |
| 8+ | — | Structure created (implementation files in next phase)  | ✓ Scaffolding |

---

# NEXT EXECUTABLE STEPS

Once you've created files 1-54:

1. **Run composer install** — Install dependencies
2. **Run ./vendor/bin/phpstan** — Verify types are correct
3. **Run ./vendor/bin/phpunit** — (Will fail; tests don't exist yet; that's OK)
4. **Begin Phase 8** — Create IEventSerializer, IEventUpgrader
5. **Begin Phase 9** — Create Tenant aggregate (first kernel-owned model)
6. **Continue to Phase 12** — PostgreSQL implementations
7. **Continue to Phase 13** — Spiral bootloaders
8. **Finish with Phase 14** — Diagnostics and test harness

**Do not skip this sequence. Do not reorder.**

This is the **canonical build order**. Follow it exactly.

---

**End of Executable Scaffold**

You now have 54 files to create, in exact order, with exact namespaces and exact code patterns.

No more architecture. Start typing.
