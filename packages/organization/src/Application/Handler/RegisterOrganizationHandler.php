<?php

declare(strict_types=1);

namespace Spiral\Organization\Application\Handler;

use Spiral\Kernel\Domain\Identity\CausationId;
use Spiral\Kernel\Domain\Shared\Error\ErrorCode;
use Spiral\Kernel\Domain\Shared\Error\ErrorDetail;
use Spiral\Kernel\Domain\Shared\Result\Result;
use Spiral\Organization\Application\Command\RegisterOrganization;
use Spiral\Organization\Domain\Aggregate\Organization;
use Spiral\Organization\Domain\Repository\IOrganizationRepository;
use Spiral\Organization\Domain\ValueObject\OrganizationId;

/**
 * Handler for RegisterOrganization command.
 *
 * Creates a new organization aggregate and persists it.
 */
final class RegisterOrganizationHandler
{
    public function __construct(
        private readonly IOrganizationRepository $repository
    ) {
    }

    public function handle(RegisterOrganization $command): Result
    {
        // Validate business rules
        if ($command->name === '') {
            return Result::failure(
                ErrorDetail::create(
                    code: ErrorCode::validation('ORGANIZATION.NAME_EMPTY'),
                    message: 'Organization name cannot be empty',
                    fieldErrors: ['name' => ['Name is required']]
                )
            );
        }

        // Generate organization ID
        $organizationId = OrganizationId::generate();

        // Create causation ID from command
        $causationId = CausationId::fromString(
            $command->causationId->toString()
        );

        // Create the aggregate (this raises OrganizationRegistered event)
        $organization = Organization::register(
            tenantId: $command->tenantId,
            correlationId: $command->correlationId,
            causationId: $causationId,
            organizationId: $organizationId,
            name: $command->name,
            slug: $command->slug,
            contactEmail: $command->contactEmail,
            timezone: $command->timezone
        );

        // Persist
        $saveResult = $this->repository->save($organization);
        if ($saveResult->isFailure()) {
            return $saveResult;
        }

        return Result::success($organization);
    }
}
