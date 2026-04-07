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
     * @return bool True if unique, false if already taken.
     */
    public function isUnique(TenantId $tenantId, EmailAddress $email): bool;
}
