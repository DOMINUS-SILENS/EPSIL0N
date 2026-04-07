<?php

declare(strict_types=1);

namespace Spiral\Kernel\Domain\Shared\Exception;

use Spiral\Kernel\Domain\Identity\TenantId;
use Spiral\Kernel\Support\Exception\DomainException;

/**
 * Thrown when tenant isolation is violated at runtime.
 *
 * This is a CRITICAL security exception. It indicates an attempt to:
 * - Access data from a different tenant
 * - Cross tenant boundaries without authorization
 * - Manipulate tenant context
 *
 * This exception should NEVER be caught and ignored. It indicates
 * a fundamental security breach that must be logged and investigated.
 */
final class TenantIsolationViolationException extends DomainException
{
    public function __construct(
        private readonly TenantId $requestedTenantId,
        private readonly TenantId $actualTenantId,
        private readonly string $operation,
        private readonly ?string $resourceId = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            message: \sprintf(
                'Tenant isolation violation: attempted %s for tenant %s while operating in tenant %s',
                $operation,
                $requestedTenantId->toString(),
                $actualTenantId->toString(),
            ),
            previous: $previous,
        );
    }

    public function getErrorCode(): string
    {
        return 'TENANT_ISOLATION_VIOLATION';
    }

    public function getRequestedTenantId(): TenantId
    {
        return $this->requestedTenantId;
    }

    public function getActualTenantId(): TenantId
    {
        return $this->actualTenantId;
    }

    public function getOperation(): string
    {
        return $this->operation;
    }

    public function getResourceId(): ?string
    {
        return $this->resourceId;
    }

    public function getContext(): array
    {
        return [
            'requested_tenant_id' => $this->requestedTenantId->toString(),
            'actual_tenant_id' => $this->actualTenantId->toString(),
            'operation' => $this->operation,
            'resource_id' => $this->resourceId,
        ];
    }
}
