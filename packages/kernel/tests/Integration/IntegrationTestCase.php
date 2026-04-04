<?php

declare(strict_types=1);

namespace Spiral\Kernel\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Base test case for integration tests.
 * Integration tests verify that multiple components work together correctly.
 *
 * These tests may require:
 * - Database connections (PostgreSQL)
 * - Event store connections
 * - External service mocks
 *
 * @package Spiral\Kernel\Tests\Integration
 */
abstract class IntegrationTestCase extends TestCase
{
    /**
     * Skip test if database is not available.
     */
    protected function skipIfDatabaseNotAvailable(): void
    {
        // TODO: Implement database availability check when infrastructure is ready
        // For now, skip tests that require database
        $this->markTestSkipped('Database integration tests require PostgreSQL setup (Phase 5+)');
    }

    /**
     * Skip test if event store is not available.
     */
    protected function skipIfEventStoreNotAvailable(): void
    {
        // TODO: Implement event store availability check when infrastructure is ready
        $this->markTestSkipped('Event store integration tests require infrastructure setup (Phase 5+)');
    }
}
