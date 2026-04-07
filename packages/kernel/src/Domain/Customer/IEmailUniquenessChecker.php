<?php

declare(strict_types=1);

namespace Spiral\Kernel\Domain\Customer;

use Spiral\Kernel\Domain\Identity\TenantId;
use Spiral\Kernel\Domain\Tenancy\EmailAddress;

interface IEmailUniquenessChecker
{
    /**
     * Verifies if an email is unique within a specific tenant's scope.
     *
     * Multi-tenant isolation is CRITICAL: email uniqueness is enforced
     * independently per tenant. A user in tenant A can register the same
     * email address as a user in tenant B without conflict.
     *
     * This contract is a domain service that bridges the aggregate and
     * infrastructure layers. The implementation may check against a
     * projection, read model, or materialized view optimized for lookups.
     *
     * @param TenantId $tenantId The tenant scope for the uniqueness check
     * @param EmailAddress $email The email to verify
     * @return bool True if unique within the tenant, false if already taken
     */
    public function isUnique(TenantId $tenantId, EmailAddress $email): bool;
}
