<?php

declare(strict_types=1);

namespace Spiral\Organization\Application\Command;

use Spiral\Kernel\Domain\Identity\CausationId;
use Spiral\Kernel\Domain\Identity\CorrelationId;
use Spiral\Organization\Domain\ValueObject\OrganizationId;

/**
 * Command to rename an existing organization.
 */
final class RenameOrganization
{
    public function __construct(
        public readonly CorrelationId $correlationId,
        public readonly CausationId $causationId,
        public readonly OrganizationId $organizationId,
        public readonly string $newName
    ) {
    }
}
