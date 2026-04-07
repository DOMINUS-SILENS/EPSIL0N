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

class Customer extends AggregateRoot
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
        if ($this->getStreamVersion() !== -1 && $this->getVersion() > 0) {
            return Result::failure(ErrorDetail::create(
                ErrorCode::fromString('DOMAIN.CUSTOMER.ALREADY_EXISTS'),
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
                ErrorCode::fromString('DOMAIN.CUSTOMER.INACTIVE'),
                'Customer is inactive and cannot be renamed'
            ));
        }

        if (!$this->verified) {
            return Result::failure(ErrorDetail::create(
                ErrorCode::fromString('DOMAIN.CUSTOMER.NOT_VERIFIED'),
                'Customer email must be verified before renaming'
            ));
        }

        if (!preg_match('/^[a-zA-Z0-9\s]{2,100}$/', $newName)) {
            return Result::failure(ErrorDetail::create(
                ErrorCode::fromString('VALIDATION.NAME_INVALID'),
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

    /**
     * Command dispatcher for domain commands.
     *
     * Handles both Domain-level commands (simple) and Application-level commands (with tracing).
     *
     * @param object $command
     * @return Result<array<object>|null>
     */
    public function decide(object $command): Result
    {
        // Domain commands (simple, for testing and internal use)
        if ($command instanceof CreateCustomer) {
            return $this->handleCreateCustomer($command);
        }

        if ($command instanceof RenameCustomer) {
            return $this->handleRenameCustomer($command);
        }

        // Application commands (full event sourcing with tracing)
        if ($command instanceof \Spiral\Kernel\Application\Command\Customer\RegisterCustomer) {
            [$correlationId, $causationId] = $this->extractTracingContext($command->correlationId, $command->causationId);
            return $this->register(
                $command->aggregateId,
                $command->name,
                EmailAddress::fromString($command->email),
                $correlationId,
                $causationId,
            );
        }

        if ($command instanceof \Spiral\Kernel\Application\Command\Customer\VerifyCustomerEmail) {
            [$correlationId, $causationId] = $this->extractTracingContext($command->correlationId, $command->causationId);
            return $this->verifyEmail(
                $command->aggregateId,
                $correlationId,
                $causationId,
            );
        }

        if ($command instanceof \Spiral\Kernel\Application\Command\Customer\RenameCustomer) {
            [$correlationId, $causationId] = $this->extractTracingContext($command->correlationId, $command->causationId);
            return $this->rename(
                $command->aggregateId,
                $command->newName,
                $correlationId,
                $causationId,
            );
        }

        if ($command instanceof \Spiral\Kernel\Application\Command\Customer\DeactivateCustomer) {
            [$correlationId, $causationId] = $this->extractTracingContext($command->correlationId, $command->causationId);
            return $this->deactivate(
                $command->aggregateId,
                $command->reason,
                $correlationId,
                $causationId,
            );
        }

        if ($command instanceof \Spiral\Kernel\Application\Command\Customer\ReactivateCustomer) {
            [$correlationId, $causationId] = $this->extractTracingContext($command->correlationId, $command->causationId);
            return $this->reactivate(
                $this->getId(),
                $correlationId,
                $causationId,
            );
        }

        return Result::failure(ErrorDetail::create(
            ErrorCode::fromString(CustomerErrorCodes::UNKNOWN_COMMAND),
            'Unknown command type: ' . \get_class($command),
        ));
    }

    /**
     * Handle domain-level CreateCustomer command.
     *
     * Returns events without applying them (functional design for testing).
     *
     * @param CreateCustomer $command
     * @return Result<array<object>>
     */
    private function handleCreateCustomer(CreateCustomer $command): Result
    {
        // State-based check: customer already exists if name is set
        if ($this->name !== null) {
            return Result::failure(ErrorDetail::create(
                ErrorCode::fromString('DOMAIN.CUSTOMER.ALREADY_EXISTS'),
                'Customer already created'
            ));
        }

        $event = new CustomerCreated(
            EventId::generate(),
            $this->getTenantId(),
            CorrelationId::generate(),
            CausationId::generate(),
            new \DateTimeImmutable(),
            $command->aggregate_id,
            $command->name
        );
        // Note: Do NOT call raise() here - events are returned for caller to apply
        return Result::success([$event]);
    }

    /**
     * Handle domain-level RenameCustomer command.
     *
     * Returns events without applying them (functional design for testing).
     * Note: Domain commands use simplified business rules for testing.
     *
     * @param RenameCustomer $command
     * @return Result<array<object>>
     */
    private function handleRenameCustomer(RenameCustomer $command): Result
    {
        // Check if customer exists (state-based check for functional design)
        if ($this->name === null) {
            return Result::failure(ErrorDetail::create(
                ErrorCode::fromString('DOMAIN.CUSTOMER.NOT_FOUND'),
                'Customer does not exist'
            ));
        }

        // Domain commands use simplified rules - no verification requirement
        if (!preg_match('/^[a-zA-Z0-9\s]{2,100}$/', $command->newName)) {
            return Result::failure(ErrorDetail::create(
                ErrorCode::fromString('VALIDATION.NAME_INVALID'),
                'Name must be 2-100 characters and contain no special characters'
            ));
        }

        $event = new CustomerRenamed(
            EventId::generate(),
            $this->getTenantId(),
            CorrelationId::generate(),
            CausationId::generate(),
            new \DateTimeImmutable(),
            $command->aggregate_id,
            $command->newName
        );
        // Note: Do NOT call raise() here - events are returned for caller to apply
        return Result::success([$event]);
    }

    public function apply(DomainEvent $event): void
    {
        match (true) {
            // Domain events (simple, for testing)
            $event instanceof CustomerCreated => $this->applyCreated($event),
            $event instanceof CustomerRenamed => $this->applyDomainRenamed($event),
            // Event-sourced events (full metadata)
            $event instanceof EventCustomerRenamed => $this->applyRenamed($event),
            $event instanceof CustomerRegistered => $this->applyRegistered($event),
            $event instanceof CustomerEmailVerified => $this->applyVerified($event),
            $event instanceof CustomerDeactivated => $this->applyDeactivated($event),
            $event instanceof CustomerReactivated => $this->applyReactivated($event),
            default => null,
        };
    }

    private function applyCreated(CustomerCreated $event): void
    {
        $this->name = $event->name;
    }

    private function applyDomainRenamed(CustomerRenamed $event): void
    {
        $this->name = $event->newName;
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

    /**
     * Extract and validate tracing context from application command.
     *
     * Converts string correlation and causation IDs with type validation.
     *
     * @param string $correlationId The correlation ID string
     * @param string $causationId The causation ID string
     * @return array{0: CorrelationId, 1: CausationId}
     */
    private function extractTracingContext(string $correlationId, string $causationId): array
    {
        /** @var non-empty-string $correlationIdStr */
        $correlationIdStr = $correlationId;
        /** @var non-empty-string $causationIdStr */
        $causationIdStr = $causationId;

        return [
            CorrelationId::fromString($correlationIdStr),
            CausationId::fromString($causationIdStr),
        ];
    }

    private function generateEventId(): EventId { return \Spiral\Kernel\Domain\Identity\EventId::generate(); }
}
