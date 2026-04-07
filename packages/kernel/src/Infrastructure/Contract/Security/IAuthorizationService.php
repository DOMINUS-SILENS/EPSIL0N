<?php

declare(strict_types=1);

namespace Spiral\Kernel\Infrastructure\Contract\Security;

use Spiral\Kernel\Domain\Identity\ActorId;
use Spiral\Kernel\Domain\Identity\TenantId;

interface IAuthorizationService
{
    /**
     * Verifies if the current actor has permission to perform a specific action on a resource.
     *
     * @throws \Spiral\Kernel\Support\Exception\AuthorizationException
     */
    public function check(string $action, string $resourceType, ?string $resourceId = null): void;

    public function getCurrentActorId(): ActorId;

    public function getCurrentTenantId(): TenantId;
}
