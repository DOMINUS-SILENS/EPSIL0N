<?php

declare(strict_types=1);

namespace Spiral\Organization\Application\Handler;

use Spiral\Kernel\Domain\Identity\CausationId;
use Spiral\Kernel\Domain\Identity\TenantId;
use Spiral\Kernel\Domain\Shared\Result\Result;
use Spiral\Organization\Application\Command\DeactivateOrganization;
use Spiral\Organization\Domain\Repository\IOrganizationRepository;

/**
 * Handler for DeactivateOrganization command.
 */
final class DeactivateOrganizationHandler
{
    public function __construct(
        private readonly IOrganizationRepository $repository
    ) {
    }

    public function handle(DeactivateOrganization $command, TenantId $tenantId): Result
    {
        // Load the aggregate
        $loadResult = $this->repository->load($tenantId, $command->organizationId);
        if ($loadResult->isFailure()) {
            return $loadResult;
        }

        $organization = $loadResult->unwrap();

        // Apply the change
        $causationId = CausationId::fromString($command->causationId->toString());
        $organization->deactivate(
            $command->correlationId,
            $causationId,
            $command->reason
        );

        // Persist
        return $this->repository->save($organization);
    }
}
