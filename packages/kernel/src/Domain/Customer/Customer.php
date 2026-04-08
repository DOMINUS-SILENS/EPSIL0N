<?php

declare(strict_types=1);

namespace Spiral\Kernel\Domain\Customer;

use Spiral\Kernel\Domain\Shared\Aggregate\AggregateRoot;
use Spiral\Kernel\Domain\Shared\Event\DomainEvent;
use Spiral\Kernel\Domain\Shared\Result\Result;
use Spiral\Kernel\Domain\Shared\Error\ErrorDetail;
use Spiral\Kernel\Domain\Shared\Error\ErrorCode;
use Spiral\Kernel\Domain\Customer\Event\CustomerRegistered;
use Spiral\Kernel\Domain\Customer\Event\CustomerEmailVerified;
use Spiral\Kernel\Domain\Customer\Event\CustomerRenamed as EventCustomerRenamed;
use Spiral\Kernel\Domain\Customer\Event\CustomerDeactivated;
use Spiral\Kernel\Domain\Customer\Event\CustomerReactivated;
use Spiral\Kernel\Domain\Customer\CustomerErrorCodes;
use Spiral\Kernel\Domain\Identity\EventId;
use Spiral\Kernel\Domain\Identity\CorrelationId;
use Spiral\Kernel\Domain\Identity\CausationId;
use Spiral\Kernel\Domain\Identity\TenantId;
use Spiral\Kernel\Domain\Tenancy\EmailAddress;
use DateTimeImmutable;

final class Customer extends AggregateRoot
{
    private ?string $name = null;
    private ?string $email = null;
    private bool $verified = false;
    private bool $active = true;
    private ?string $deactivationReason = null;

    public function getName(): ?string { return $this->name; }
    public function getEmail(): ?string { return $this->email; }
    public function isVerified(): bool { return $this->verified; }
    public function isActive(): bool { return $this->active; }
    public function getDeactivationReason(): ?string { return $this->deactivationReason; }

    /**
     * Register a new customer.
     *
     * @param string $customerId The customer aggregate ID
     * @param string $name The customer name
     * @param EmailAddress $email The customer email address
     * @param CorrelationId $correlationId The correlation ID for tracing
     * @param CausationId $causationId The causation ID for tracing
     * @return Result<null>
     */
    public function register(
        string $customerId,
        string $name,
        EmailAddress $email,
        CorrelationId $correlationId,
        CausationId $causationId
    ): Result {
        // State-based check: customer already exists if name is set
        if ($this->name !== null) {
            return Result::failure(ErrorDetail::create(
                ErrorCode::fromString(CustomerErrorCodes::ALREADY_EXISTS),
                'Customer already registered'
            ));
        }

        $this->raise(new CustomerRegistered(
            $this->generateEventId(),
            $this->getTenantId(),
            $correlationId,
            $causationId,
            new DateTimeImmutable(),
            $customerId,
            $name,
            $email->toString()
        ));

        return Result::success(null);
    }

    /**
     * Verify the customer's email address.
     *
     * @param string $customerId The customer aggregate ID
     * @param CorrelationId $correlationId The correlation ID for tracing
     * @param CausationId $causationId The causation ID for tracing
     * @return Result<null>
     */
    public function verifyEmail(
        string $customerId,
        CorrelationId $correlationId,
        CausationId $causationId
    ): Result {
        $this->raise(new CustomerEmailVerified(
            $this->generateEventId(),
            $this->getTenantId(),
            $correlationId,
            $causationId,
            new DateTimeImmutable(),
            $customerId
        ));

        return Result::success(null);
    }

    /**
     * Rename the customer.
     *
     * @param string $customerId The customer aggregate ID
     * @param string $newName The new customer name
     * @param CorrelationId $correlationId The correlation ID for tracing
     * @param CausationId $causationId The causation ID for tracing
     * @return Result<null>
     */
    public function rename(
        string $customerId,
        string $newName,
        CorrelationId $correlationId,
        CausationId $causationId
    ): Result {
        // Global Rule: If inactive, must fail.
        if (!$this->active) {
            return Result::failure(ErrorDetail::create(
                ErrorCode::fromString(CustomerErrorCodes::INACTIVE),
                'Customer is inactive and cannot be renamed'
            ));
        }

        if (!$this->verified) {
            return Result::failure(ErrorDetail::create(
                ErrorCode::fromString(CustomerErrorCodes::NOT_VERIFIED),
                'Customer email must be verified before renaming'
            ));
        }

        if (!preg_match(CustomerErrorCodes::NAME_PATTERN, $newName)) {
            return Result::failure(ErrorDetail::create(
                ErrorCode::fromString(CustomerErrorCodes::VALIDATION_NAME_INVALID),
                'Name must be 2-100 characters and contain no special characters'
            ));
        }

        $this->raise(new EventCustomerRenamed(
            $this->generateEventId(),
            $this->getTenantId(),
            $correlationId,
            $causationId,
            new DateTimeImmutable(),
            $customerId,
            $newName
        ));

        return Result::success(null);
    }

    /**
     * Deactivate the customer.
     *
     * @param string $customerId The customer aggregate ID
     * @param string|null $reason The reason for deactivation
     * @param CorrelationId $correlationId The correlation ID for tracing
     * @param CausationId $causationId The causation ID for tracing
     * @return Result<null>
     */
    public function deactivate(
        string $customerId,
        ?string $reason,
        CorrelationId $correlationId,
        CausationId $causationId
    ): Result {
        $this->raise(new CustomerDeactivated(
            $this->generateEventId(),
            $this->getTenantId(),
            $correlationId,
            $causationId,
            new DateTimeImmutable(),
            $customerId,
            $reason
        ));

        return Result::success(null);
    }

    /**
     * Reactivate the customer.
     *
     * @param string $customerId The customer aggregate ID
     * @param CorrelationId $correlationId The correlation ID for tracing
     * @param CausationId $causationId The causation ID for tracing
     * @return Result<null>
     */
    public function reactivate(
        string $customerId,
        CorrelationId $correlationId,
        CausationId $causationId
    ): Result {
        $this->raise(new CustomerReactivated(
            $this->generateEventId(),
            $this->getTenantId(),
            $correlationId,
            $causationId,
            new DateTimeImmutable(),
            $customerId
        ));

        return Result::success(null);
    }


    public function apply(DomainEvent $event): void
    {
        match (true) {
            $event instanceof EventCustomerRenamed => $this->applyRenamed($event),
            $event instanceof CustomerRegistered => $this->applyRegistered($event),
            $event instanceof CustomerEmailVerified => $this->applyVerified($event),
            $event instanceof CustomerDeactivated => $this->applyDeactivated($event),
            $event instanceof CustomerReactivated => $this->applyReactivated($event),
            default => throw new \RuntimeException(
                sprintf('Unknown event type: %s', $event::class)
            ),
        };
    }


    private function applyRegistered(CustomerRegistered $event): void
    {
        $this->name = $event->name;
        $this->email = $event->email;
        $this->active = true;
        $this->verified = false;
    }

    private function applyVerified(CustomerEmailVerified $event): void
    {
        $this->verified = true;
    }

    private function applyRenamed(EventCustomerRenamed $event): void
    {
        $this->name = $event->newName;
    }

    private function applyDeactivated(CustomerDeactivated $event): void
    {
        $this->active = false;
        $this->deactivationReason = $event->reason;
    }

    private function applyReactivated(CustomerReactivated $event): void
    {
        $this->active = true;
        $this->deactivationReason = null;
    }

    private function generateEventId(): EventId { return \Spiral\Kernel\Domain\Identity\EventId::generate(); }
}
