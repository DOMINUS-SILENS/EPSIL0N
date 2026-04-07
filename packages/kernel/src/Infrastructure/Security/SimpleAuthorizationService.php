<?php

declare(strict_types=1);

namespace Spiral\Kernel\Infrastructure\Security;

use Spiral\Kernel\Domain\Identity\ActorId;
use Spiral\Kernel\Domain\Identity\TenantId;
use Spiral\Kernel\Infrastructure\Contract\Security\IAuthorizationService;
use Spiral\Kernel\Support\Exception\AuthorizationException;

/**
 * Basic implementation of IAuthorizationService for initial orchestration membrane.
 * In a real scenario, this would integrate with a JWT token or session.
 */
final class SimpleAuthorizationService implements IAuthorizationService
{
    public function __construct(
        private readonly ActorId $currentActorId,
        private readonly TenantId $currentTenantId,
    ) {}

    public function check(string $action, string $resourceType, ?string $resourceId = null): void
    {
        // Basic implementation: allow all for now, but ensure the interface is exercised.
        // In production, this would check a permission matrix or RBAC.
    }

    public function getCurrentActorId(): ActorId
    {
        return $this->currentActorId;
    }

    public function getCurrentTenantId(): TenantId
    {
        return $this->currentTenantId;
    }
}
