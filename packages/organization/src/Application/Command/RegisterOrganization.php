<?php

declare(strict_types=1);

namespace Spiral\Organization\Application\Command;

use Spiral\Kernel\Domain\Identity\ActorId;
use Spiral\Kernel\Domain\Identity\CausationId;
use Spiral\Kernel\Domain\Identity\CorrelationId;
use Spiral\Kernel\Domain\Identity\TenantId;
use Spiral\Kernel\Domain\Shared\ValueObject\Temporal\TimezoneId;
use Spiral\Kernel\Domain\Tenancy\EmailAddress;
use Spiral\Kernel\Domain\Tenancy\TenantSlug;

/**
 * Command to register a new organization.
 *
 * This is the entry point for creating a new tenant organization.
 * It carries all the initial state needed for organization creation.
 */
final class RegisterOrganization
{
    /**
     * @param CorrelationId $correlationId Request correlation for tracing
     * @param CausationId $causationId ID of the command causing this action
     * @param ActorId $actorId The user/system executing this command
     * @param TenantId $tenantId The tenant this organization belongs to
     * @param string $name Organization display name
     * @param TenantSlug $slug Human-readable URL identifier
     * @param EmailAddress $contactEmail Primary contact email
     * @param TimezoneId $timezone Organization's primary timezone
     */
    public function __construct(
        public readonly CorrelationId $correlationId,
        public readonly CausationId $causationId,
        public readonly ActorId $actorId,
        public readonly TenantId $tenantId,
        public readonly string $name,
        public readonly TenantSlug $slug,
        public readonly EmailAddress $contactEmail,
        public readonly TimezoneId $timezone
    ) {
    }
}
