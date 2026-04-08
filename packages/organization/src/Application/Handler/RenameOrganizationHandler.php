<?php

declare(strict_types=1);

namespace Spiral\Organization\Application\Handler;

use Spiral\Kernel\Domain\Identity\CausationId;
use Spiral\Kernel\Domain\Identity\TenantId;
use Spiral\Kernel\Domain\Shared\Error\ErrorCode;
use Spiral\Kernel\Domain\Shared\Error\ErrorDetail;
use Spiral\Kernel\Domain\Shared\Result\Result;
use Spiral\Organization\Application\Command\RenameOrganization;
use Spiral\Organization\Domain\OrganizationErrorCodes;
use Spiral\Organization\Domain\Repository\IOrganizationRepository;

/**
 * Handler for RenameOrganization command.
 */
final class RenameOrganizationHandler
{
    public function __construct(
        private readonly IOrganizationRepository $repository
    ) {
    }

    public function handle(RenameOrganization $command, TenantId $tenantId): Result
    {
        if ($command->newName === '') {
            return Result::failure(
                ErrorDetail::withContextData(
                    code: ErrorCode::fromString(OrganizationErrorCodes::VALIDATION_NAME_EMPTY),
                    message: 'Organization name cannot be empty',
                    contextData: ['field' => 'name']
                )
            );
        }

        // Load the aggregate
        $loadResult = $this->repository->load($tenantId, $command->organizationId);
        if ($loadResult->isFailure()) {
            return $loadResult;
        }

        $organization = $loadResult->unwrap();

        // Apply the change
        $causationId = CausationId::fromString($command->causationId->toString());
        $renameResult = $organization->rename(
            $command->correlationId,
            $causationId,
            $command->newName
        );

        if ($renameResult->isFailure()) {
            return $renameResult;
        }

        // Persist
        return $this->repository->save($organization);
    }
}
