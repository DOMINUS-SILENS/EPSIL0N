<?php

declare(strict_types=1);

namespace Spiral\Kernel\Tests\Integration\Concurrency;

use Spiral\Kernel\Tests\Integration\IntegrationTestCase;
use Spiral\Kernel\Support\Exception\ConcurrencyConflictException;

/**
 * Integration tests for Optimistic Concurrency Control.
 *
 * These tests verify:
 * - Version tracking on aggregates
 * - Concurrent update detection
 * - Conflict exception handling
 * - Retry mechanisms
 *
 * Requires: PostgreSQL with event store tables (Phase 5+)
 *
 * @package Spiral\Kernel\Tests\Integration\Concurrency
 */
final class ConcurrencyTest extends IntegrationTestCase
{
    public function testVersionIncrementsOnSave(): void
    {
        $this->skipIfDatabaseNotAvailable();

        // TODO: Test that aggregate version increments after each save
    }

    public function testConcurrentUpdateThrowsException(): void
    {
        $this->skipIfDatabaseNotAvailable();

        // TODO: Test that concurrent updates throw ConcurrencyConflictException
        // $this->expectException(ConcurrencyConflictException::class);
    }

    public function testRetryAfterConflict(): void
    {
        $this->skipIfDatabaseNotAvailable();

        // TODO: Test successful retry after concurrency conflict
    }

    public function testVersionMismatchDetection(): void
    {
        $this->skipIfDatabaseNotAvailable();

        // TODO: Test that version mismatch is correctly detected
    }

    public function testOptimisticLockWithMultipleEvents(): void
    {
        $this->skipIfDatabaseNotAvailable();

        // TODO: Test concurrency with multiple events in single transaction
    }
}
