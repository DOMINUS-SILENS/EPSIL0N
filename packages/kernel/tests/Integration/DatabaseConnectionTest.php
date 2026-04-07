<?php

declare(strict_types=1);

namespace Spiral\Kernel\Tests\Integration;

/**
 * Database connectivity test.
 * Verifies PostgreSQL connection and schema are properly configured.
 *
 * @package Spiral\Kernel\Tests\Integration
 */
final class DatabaseConnectionTest extends IntegrationTestCase
{
    public function testDatabaseConnection(): void
    {
        $this->skipIfDatabaseNotAvailable();

        $pdo = $this->getConnection();
        $stmt = $pdo->query('SELECT version()');
        $this->assertNotFalse($stmt, 'Query should return a statement');
        $result = $stmt->fetchColumn();

        $this->assertIsString($result);
        $this->assertStringContainsString('PostgreSQL', $result);
    }

    public function testEventStoreSchemaExists(): void
    {
        $this->skipIfDatabaseNotAvailable();

        $pdo = $this->getConnection();

        // Check schema exists
        $stmt = $pdo->query("
            SELECT schema_name 
            FROM information_schema.schemata 
            WHERE schema_name = 'event_store'
        ");
        $this->assertNotFalse($stmt, 'Query should return a statement');
        $schema = $stmt->fetch();

        $this->assertNotFalse($schema, 'event_store schema should exist');
    }

    public function testEventsTableExists(): void
    {
        $this->skipIfDatabaseNotAvailable();

        $pdo = $this->getConnection();

        $stmt = $pdo->query("
            SELECT table_name 
            FROM information_schema.tables 
            WHERE table_schema = 'event_store' 
            AND table_name = 'events'
        ");
        $this->assertNotFalse($stmt, 'Query should return a statement');
        $table = $stmt->fetch();

        $this->assertNotFalse($table, 'events table should exist');
    }

    public function testCanInsertAndRetrieveEvent(): void
    {
        $this->skipIfDatabaseNotAvailable();

        $pdo = $this->getConnection();

        $aggregateId = 'test-agg-' . uniqid();
        $tenantId = 'test-tenant';
        $eventType = 'TestEvent';

        // Insert test event
        $sql = <<<'SQL'
            INSERT INTO event_store.events 
            (aggregate_id, aggregate_type, tenant_id, event_type, event_version, payload, occurred_at)
            VALUES (?, ?, ?, ?, ?, ?::jsonb, NOW())
        SQL;
        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            $aggregateId,
            'TestAggregate',
            $tenantId,
            $eventType,
            1,
            '{"test": "data"}'
        ]);

        // Retrieve event
        $sql = <<<'SQL'
            SELECT * FROM event_store.events 
            WHERE aggregate_id = ?
        SQL;
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$aggregateId]);
        $event = $stmt->fetch();

        $this->assertNotFalse($event);
        /** @var array<string, mixed> $event */
        $this->assertSame($aggregateId, $event['aggregate_id']);
        $this->assertSame($tenantId, $event['tenant_id']);
        $this->assertSame($eventType, $event['event_type']);
    }
}
