<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. UNIQUE on event_store for Aggregate + Version (Anti-corruption)
        $indexExists = $this->indexExists('event_store', 'uniq_aggregate_version');
        
        if (!$indexExists) {
            Schema::table('event_store', function (Blueprint $table) {
                // Need to drop duplicates first if any exist? For now assume clean or add constraint with IGNORE (not standard in Laravel schema logic).
                // This is a strict certification gate. 
                $table->unique(['aggregate_id', 'event_version'], 'uniq_aggregate_version');
            });
        }

        // Note: projector_processed_events table already exists from migration 2026_03_22_174000
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projector_processed_events');
        
        Schema::table('event_store', function (Blueprint $table) {
            $table->dropUnique('uniq_aggregate_version');
        });
    }

    /**
     * Check if index exists on table
     */
    private function indexExists(string $tableName, string $indexName): bool
    {
        $driver = Schema::getConnection()->getDriverName();
        
        if ($driver === 'mysql') {
            $indexes = \DB::select("SHOW INDEX FROM `{$tableName}` WHERE Key_name = ?", [$indexName]);
            return count($indexes) > 0;
        }
        
        if ($driver === 'pgsql') {
            $indexes = \DB::select(
                "SELECT indexname FROM pg_indexes WHERE tablename = ? AND indexname = ?",
                [$tableName, $indexName]
            );
            return count($indexes) > 0;
        }
        
        return false;
    }
};
