<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add generated columns for frequently queried JSON fields
 * This allows MySQL to index and query JSON data efficiently
 */
return new class extends Migration
{
    public function up(): void
    {
        // Event Store - Extract tenant_id from metadata for faster queries
        Schema::table('event_store', function (Blueprint $table) {
            // Check if tenant_id column exists
            if (!$this->columnExists('event_store', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')
                    ->virtualAs("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.tenant_id')), 1)")
                    ->after('metadata')
                    ->index('idx_ev_tenant');
            }
        });

        // Domain Events - Add generated columns for common filters
        Schema::table('domain_events', function (Blueprint $table) {
            if (!$this->columnExists('domain_events', 'payload_event_id')) {
                // Extract key fields from payload for indexing if frequently queried
                $table->string('payload_event_id', 50)
                    ->virtualAs("JSON_UNQUOTE(JSON_EXTRACT(payload, '$.eventId'))")
                    ->nullable()
                    ->after('payload');

                // Index for event lookups by device event ID
                $table->index('payload_event_id', 'idx_de_payload_event');
            }
        });
    }

    public function down(): void
    {
        // Event Store
        Schema::table('event_store', function (Blueprint $table) {
            if ($this->columnExists('event_store', 'tenant_id')) {
                $table->dropIndex('idx_ev_tenant');
                $table->dropColumn('tenant_id');
            }
        });

        // Domain Events
        Schema::table('domain_events', function (Blueprint $table) {
            if ($this->columnExists('domain_events', 'payload_event_id')) {
                $table->dropIndex('idx_de_payload_event');
                $table->dropColumn('payload_event_id');
            }
        });
    }

    /**
     * Check if column exists on table
     */
    private function columnExists(string $tableName, string $columnName): bool
    {
        $driver = Schema::getConnection()->getDriverName();
        
        if ($driver === 'mysql') {
            $columns = \DB::select("SHOW COLUMNS FROM `{$tableName}` WHERE Field = ?", [$columnName]);
            return count($columns) > 0;
        }
        
        if ($driver === 'pgsql') {
            $columns = \DB::select(
                "SELECT column_name FROM information_schema.columns WHERE table_name = ? AND column_name = ?",
                [$tableName, $columnName]
            );
            return count($columns) > 0;
        }
        
        return false;
    }
};
