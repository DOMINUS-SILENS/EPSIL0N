<?php

declare(strict_types=1);

namespace Spiral\Organization\Domain\Repository;

use Spiral\Kernel\Domain\Identity\TenantId;
use Spiral\Kernel\Domain\Shared\Result\Result;
use Spiral\Organization\Domain\Aggregate\Organization;
use Spiral\Organization\Domain\ValueObject\OrganizationId;

/**
 * Repository interface for Organization aggregates.
 *
 * Provides load/save operations with tenant scoping.
 * Implementations must enforce tenant isolation.
 */
interface IOrganizationRepository
{
    /**
     * Load an organization by ID.
     *
     * @param TenantId $tenantId The tenant scope
     * @param OrganizationId $organizationId The organization to load
     * @return Result<Organization> Success with aggregate, or failure if not found
     */
    public function load(TenantId $tenantId, OrganizationId $organizationId): Result;

    /**
     * Save an organization.
     *
     * Persists uncommitted events and updates the aggregate's stream version.
     *
     * @param Organization $organization The aggregate to save
     * @return Result<Organization> Success with saved aggregate, or failure
     */
    public function save(Organization $organization): Result;

    /**
     * Check if an organization exists.
     */
    public function exists(TenantId $tenantId, OrganizationId $organizationId): bool;

    /**
     * Load all organizations for a tenant.
     *
     * @return Result<list<Organization>>
     */
    public function loadAll(TenantId $tenantId): Result;
}
