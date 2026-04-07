<?php

declare(strict_types=1);

namespace Spiral\Kernel\Application\Handler\Customer;

use Spiral\Kernel\Application\Contract\Handler\ICommandHandler;
use Spiral\Kernel\Application\Contract\Command\ICommand;
use Spiral\Kernel\Application\Command\Customer\RegisterCustomer;
use Spiral\Kernel\Domain\Shared\Result\Result;
use Spiral\Kernel\Domain\Shared\Error\ErrorDetail;
use Spiral\Kernel\Domain\Shared\Error\ErrorCode;
use Spiral\Kernel\Domain\Customer\Customer;
use Spiral\Kernel\Domain\Customer\IEmailUniquenessChecker;
use Spiral\Kernel\Domain\Tenancy\EmailAddress;
use Spiral\Kernel\Domain\Identity\CorrelationId;
use Spiral\Kernel\Domain\Identity\TenantId;
use Spiral\Kernel\Infrastructure\Contract\Security\IAuthorizationService;
use Spiral\Kernel\Application\Contract\Idempotency\IIdempotencyService;
use Spiral\Kernel\Infrastructure\Persistence\EventSourcedRepository;
use Spiral\Kernel\Support\Exception\ConcurrencyConflictException;
use Spiral\Kernel\Support\Exception\KernelException;

/**
 * @implements ICommandHandler<RegisterCustomer, string>
 */
final class RegisterCustomerHandler implements ICommandHandler
{
    /**
     * @param EventSourcedRepository<Customer, TenantId> $repository
     */
    public function __construct(
        private readonly IAuthorizationService $authService,
        private readonly IIdempotencyService $idempotencyService,
        private readonly IEmailUniquenessChecker $uniquenessChecker,
        private readonly EventSourcedRepository $repository
    ) {}

    /**
     * @param RegisterCustomer $command
     * @return Result<string>
     */
    public function handle(ICommand $command): Result
    {
        if (!$command instanceof RegisterCustomer) {
            return Result::failure(ErrorDetail::create(
                ErrorCode::fromString('KERNEL.INVALID_COMMAND'),
                'Handler expected RegisterCustomer command'
            ));
        }

        // 1. Authorization Check
        // Note: In a real system, the action and resource type would be derived from the command
        $this->authService->check('customer.register', 'Customer');

        $tenantId = $this->authService->getCurrentTenantId();

        // 2. Idempotency Check
        $correlationId = $command->correlationId === ''
            ? CorrelationId::generate()
            : CorrelationId::fromString($command->correlationId);
        if ($this->idempotencyService->isProcessed($correlationId)) {
             return Result::failure(ErrorDetail::create(
                ErrorCode::fromString('KERNEL.IDEMPOTENCY_VIOLATION'),
                'Command already processed'
            ));
        }

        // 3. Domain Service Check (Email Uniqueness)
        $email = EmailAddress::fromString($command->email);
        if (!$this->uniquenessChecker->isUnique($tenantId, $email)) {
            return Result::failure(ErrorDetail::create(
                ErrorCode::fromString('DOMAIN.CUSTOMER.EMAIL_TAKEN'),
                'The email address is already registered for this tenant'
            ));
        }

        // 4. Aggregate Operation
        $customer = new Customer($command->aggregateId, $tenantId);
        $causationId = $command->causationId === ''
            ? \Spiral\Kernel\Domain\Identity\CausationId::generate()
            : \Spiral\Kernel\Domain\Identity\CausationId::fromString($command->causationId);
        $result = $customer->register(
            $command->aggregateId,
            $command->name,
            $email,
            $correlationId,
            $causationId
        );

        if ($result->isFailure()) {
            /** @var Result<string> $failureResult */
            $failureResult = $result;
            return $failureResult;
        }

        // 5. EventStore Append (via Repository)
        try {
            $this->repository->save($customer);
        } catch (ConcurrencyConflictException $e) {
            return Result::failure(ErrorDetail::create(
                ErrorCode::fromString('KERNEL.CONCURRENCY_CONFLICT'),
                'Concurrency conflict occurred while persisting customer'
            ));
        } catch (KernelException $e) {
            return Result::failure(ErrorDetail::create(
                ErrorCode::fromString('KERNEL.PERSISTENCE_ERROR'),
                'Failed to persist customer: ' . $e->getMessage()
            ));
        }

        // 6. Mark Idempotency
        $this->idempotencyService->markAsProcessed($correlationId, 'success');

        return Result::success($command->aggregateId);
    }
}
