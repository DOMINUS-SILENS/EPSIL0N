<?php

declare(strict_types=1);

namespace Spiral\Kernel\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Base test case for integration tests.
 * Integration tests verify that multiple components work together correctly.
 *
 * These tests require:
 * - PostgreSQL with event store tables (Phase 5+)
 *
 * @package Spiral\Kernel\Tests\Integration
 */
abstract class IntegrationTestCase extends TestCase
{
    private static ?\PDO $pdo = null;

    /**
     * Get database connection for integration tests.
     */
    protected function getConnection(): \PDO
    {
        if (self::$pdo === null) {
            $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
            $port = $_ENV['DB_PORT'] ?? '5432';
            $db = $_ENV['DB_DATABASE'] ?? 'epsilone_kernel_test';
            $user = $_ENV['DB_USER'] ?? 'postgres';
            $pass = $_ENV['DB_PASSWORD'] ?? 'password';

            $dsn = "pgsql:host={$host};port={$port};dbname={$db}";
            self::$pdo = new \PDO($dsn, $user, $pass, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]);
        }

        return self::$pdo;
    }

    /**
     * Skip test if database is not available.
     */
    protected function skipIfDatabaseNotAvailable(): void
    {
        try {
            $this->getConnection()->query('SELECT 1');
        } catch (\PDOException $e) {
            $this->markTestSkipped('Database not available: ' . $e->getMessage());
        }
    }

    /**
     * Skip test if event store is not available.
     */
    protected function skipIfEventStoreNotAvailable(): void
    {
        $this->skipIfDatabaseNotAvailable();
        // Additional event store checks can be added here
    }

    /**
     * Clean up test data after each test.
     */
    protected function tearDown(): void
    {
        // Clean up test data between tests
        if (self::$pdo !== null) {
            self::$pdo->exec('
                TRUNCATE TABLE event_streams CASCADE;
                TRUNCATE TABLE domain_events CASCADE;
                TRUNCATE TABLE projection_customers CASCADE;
                TRUNCATE TABLE mobile_sync_feed CASCADE;
                TRUNCATE TABLE device_offsets CASCADE;
            ');
        }
        parent::tearDown();
    }
}
