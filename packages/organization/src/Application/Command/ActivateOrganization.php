<?php

declare(strict_types=1);

namespace Spiral\Organization\Application\Command;

use Spiral\Kernel\Domain\Identity\CausationId;
use Spiral\Kernel\Domain\Identity\CorrelationId;
use Spiral\Organization\Domain\ValueObject\OrganizationId;

/**
 * Command to activate an organization.
 */
final class ActivateOrganization
{
    public function __construct(
        public readonly CorrelationId $correlationId,
        public readonly CausationId $causationId,
        public readonly OrganizationId $organizationId
    ) {
    }
}
