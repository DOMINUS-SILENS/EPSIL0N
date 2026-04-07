<?php

declare(strict_types=1);

/**
 * Migration: Create mobile sync tables for offline queue and vector clocks.
 *
 * Creates:
 * - mobile_offline_queue: Stores offline events pending sync
 * - mobile_sync_checkpoints: Tracks last sync version per device
 * - mobile_device_priorities: Device priority configuration for conflict resolution
 */

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class CreateMobileSyncTables extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates tables for offline mobile sync with vector clock support';
    }

    public function up(Schema $schema): void
    {
        // Mobile offline queue - stores events created offline
        $queueTable = $schema->createTable('mobile_offline_queue');
        
        $queueTable->addColumn('id', 'uuid', [
            'comment' => 'Queue item ID (UUID v4)',
        ]);
        
        $queueTable->addColumn('tenant_id', 'uuid', [
            'comment' => 'Tenant ID for multi-tenant isolation',
        ]);
        
        $queueTable->addColumn('device_id', 'uuid', [
            'comment' => 'Device that created this event',
        ]);
        
        $queueTable->addColumn('event_type', 'string', [
            'length' => 255,
            'comment' => 'Event type/class name',
        ]);
        
        $queueTable->addColumn('event_payload', 'json', [
            'comment' => 'Serialized event data',
        ]);
        
        $queueTable->addColumn('sync_version', 'json', [
            'comment' => 'Vector clock at time of creation',
        ]);
        
        $queueTable->addColumn('status', 'string', [
            'length' => 20,
            'default' => 'pending',
            'comment' => 'Sync status: pending, syncing, synced, conflict, rejected, merged',
        ]);
        
        $queueTable->addColumn('queued_at', 'datetime_immutable', [
            'comment' => 'When event was queued offline',
        ]);
        
        $queueTable->addColumn('synced_at', 'datetime_immutable', [
            'notnull' => false,
            'comment' => 'When event was synced to server',
        ]);
        
        $queueTable->addColumn('error_message', 'text', [
            'notnull' => false,
            'comment' => 'Error message if rejected',
        ]);
        
        $queueTable->addColumn('conflict_data', 'json', [
            'notnull' => false,
            'comment' => 'Conflict details for manual resolution',
        ]);
        
        // Primary key
        $queueTable->setPrimaryKey(['id']);
        
        // Indexes for common queries
        $queueTable->addIndex(['tenant_id', 'device_id'], 'idx_offline_queue_tenant_device');
        $queueTable->addIndex(['tenant_id', 'device_id', 'status'], 'idx_offline_queue_status');
        $queueTable->addIndex(['queued_at'], 'idx_offline_queue_queued_at');
        
        // Foreign key to tenants (if applicable)
        // $queueTable->addForeignKeyConstraint('tenants', ['tenant_id'], ['id'], ['onDelete' => 'CASCADE']);


        // Mobile sync checkpoints - tracks last sync version per device
        $checkpointTable = $schema->createTable('mobile_sync_checkpoints');
        
        $checkpointTable->addColumn('id', 'uuid', [
            'comment' => 'Checkpoint ID',
        ]);
        
        $checkpointTable->addColumn('tenant_id', 'uuid', [
            'comment' => 'Tenant ID',
        ]);
        
        $checkpointTable->addColumn('device_id', 'uuid', [
            'comment' => 'Device ID',
        ]);
        
        $checkpointTable->addColumn('sync_version', 'json', [
            'comment' => 'Last synced vector clock',
        ]);
        
        $checkpointTable->addColumn('synced_at', 'datetime_immutable', [
            'comment' => 'When last sync occurred',
        ]);
        
        $checkpointTable->addColumn('events_synced', 'integer', [
            'default' => 0,
            'comment' => 'Total events synced for this device',
        ]);
        
        $checkpointTable->addColumn('events_conflicted', 'integer', [
            'default' => 0,
            'comment' => 'Total conflicts encountered',
        ]);
        
        // Primary key
        $checkpointTable->setPrimaryKey(['id']);
        
        // Unique constraint for tenant + device
        $checkpointTable->addUniqueConstraint(['tenant_id', 'device_id'], 'uniq_checkpoint_tenant_device');
        
        // Index for tenant queries
        $checkpointTable->addIndex(['tenant_id'], 'idx_checkpoint_tenant');


        // Mobile device priorities - device priority for conflict resolution
        $priorityTable = $schema->createTable('mobile_device_priorities');
        
        $priorityTable->addColumn('id', 'uuid', [
            'comment' => 'Priority config ID',
        ]);
        
        $priorityTable->addColumn('tenant_id', 'uuid', [
            'comment' => 'Tenant ID',
        ]);
        
        $priorityTable->addColumn('device_id', 'uuid', [
            'comment' => 'Device ID',
        ]);
        
        $priorityTable->addColumn('priority', 'integer', [
            'default' => 0,
            'comment' => 'Priority level (higher = more authority in conflicts)',
        ]);
        
        $priorityTable->addColumn('device_type', 'string', [
            'length' => 50,
            'notnull' => false,
            'comment' => 'Device type: mobile, tablet, desktop, pos, etc.',
        ]);
        
        $priorityTable->addColumn('created_at', 'datetime_immutable', [
            'comment' => 'When this config was created',
        ]);
        
        $priorityTable->addColumn('updated_at', 'datetime_immutable', [
            'comment' => 'When this config was last updated',
        ]);
        
        // Primary key
        $priorityTable->setPrimaryKey(['id']);
        
        // Unique constraint for tenant + device
        $priorityTable->addUniqueConstraint(['tenant_id', 'device_id'], 'uniq_priority_tenant_device');
        
        // Index for tenant queries
        $priorityTable->addIndex(['tenant_id'], 'idx_priority_tenant');
        $priorityTable->addIndex(['priority'], 'idx_priority_level');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('mobile_device_priorities');
        $schema->dropTable('mobile_sync_checkpoints');
        $schema->dropTable('mobile_offline_queue');
    }
}
