<?php

declare(strict_types=1);

namespace Spiral\Kernel\Tests\Integration\Tenancy;

use Spiral\Kernel\Tests\Integration\IntegrationTestCase;
use Spiral\Kernel\Domain\Identity\TenantId;

/**
 * Integration tests for Multi-Tenancy isolation.
 *
 * These tests verify:
 * - Tenant isolation in queries
 * - Cross-tenant access prevention
 * - Tenant context propagation
 * - Tenant-specific event streams
 *
 * Requires: PostgreSQL with tenant-aware tables (Phase 5+)
 *
 * @package Spiral\Kernel\Tests\Integration\Tenancy
 */
final class TenancyTest extends IntegrationTestCase
{
    public function testTenantIsolationInQueries(): void
    {
        $this->skipIfDatabaseNotAvailable();

        // TODO: Test that queries are automatically filtered by tenant
    }

    public function testCrossTenantAccessDenied(): void
    {
        $this->skipIfDatabaseNotAvailable();

        // TODO: Test that cross-tenant access is denied without explicit authorization
    }

    public function testTenantContextPropagation(): void
    {
        $this->skipIfDatabaseNotAvailable();

        // TODO: Test that tenant context propagates through command handlers
    }

    public function testTenantSpecificEventStreams(): void
    {
        $this->skipIfDatabaseNotAvailable();

        // TODO: Test that events are stored with tenant ID
    }

    public function testAggregateTenantIdImmutability(): void
    {
        $this->skipIfDatabaseNotAvailable();

        // TODO: Test that aggregate TenantId cannot be changed
    }
}
