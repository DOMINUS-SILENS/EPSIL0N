<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Performance indexes for SFA Mobile Sync system
 * Optimizes event sourcing, stock movements, and order queries
 */
return new class extends Migration {
    public function up(): void
    {
        // 1. Event Store - Critical for aggregate reconstruction and SSE
        Schema::table('event_store', function (Blueprint $table) {
            // Composite index for aggregate lookups (CQRS read path)
            $this->createIndexIfNotExists($table, ['aggregate_type', 'aggregate_id'], 'idx_ev_aggregate_lookup');
            // Index for shard-local sequence queries
            $this->createIndexIfNotExists($table, ['shard_id', 'local_sequence'], 'idx_ev_shard_sequence');
            // Index for event type filtering
            $this->createIndexIfNotExists($table, ['event_type', 'created_at'], 'idx_ev_type_time');
            // Index for correlation tracking
            $this->createIndexIfNotExists($table, ['correlation_id'], 'idx_ev_correlation');
        });

        // 2. Domain Events - Source of truth for projectors
        Schema::table('domain_events', function (Blueprint $table) {
            // Multi-tenant aggregate lookups
            $this->createIndexIfNotExists($table, ['tenant_id', 'aggregate_type', 'aggregate_id', 'id'], 'idx_de_tenant_aggregate');
            // Event type for selective projections
            $this->createIndexIfNotExists($table, ['event_type', 'event_time'], 'idx_de_type_time');
            // Event time for chronological queries
            $this->createIndexIfNotExists($table, ['event_time', 'id'], 'idx_de_event_time');
        });

        // 3. Domain Outbox - Critical polling table for projectors
        Schema::table('domain_outbox', function (Blueprint $table) {
            // Status + time for pending event polling
            $this->createIndexIfNotExists($table, ['status', 'next_retry_at'], 'idx_do_status_retry');
            // Event relation
            $this->createIndexIfNotExists($table, ['event_id'], 'idx_do_event');
        });

        // 4. Aggregate Sequences - Causal validation
        Schema::table('aggregate_sequences', function (Blueprint $table) {
            // Sequence lookup by aggregate type
            $this->createIndexIfNotExists($table, ['aggregate_type', 'aggregate_id'], 'idx_as_type_agg');
        });

        // 5. Article Mouvement (Stock Movements) - High volume table
        Schema::table('article_mouvement', function (Blueprint $table) {
            // Stock balance lookups
            $this->createIndexIfNotExists($table, ['entreprise_id', 'depot_id_source', 'article_id', 'created_at'], 'idx_am_stock_lookup');
            // Event sourcing audit trail
            $this->createIndexIfNotExists($table, ['relation_article_mouvement_id'], 'idx_am_event');
            // Depot stock queries
            $this->createIndexIfNotExists($table, ['depot_id_source', 'stock_operation_type', 'created_at'], 'idx_am_depot_type');
            // Article history
            $this->createIndexIfNotExists($table, ['article_id', 'created_at'], 'idx_am_article_time');
        });

        // 6. Balance Stock - Current stock levels
        Schema::table('balance_stock', function (Blueprint $table) {
            // Already has primary key on (article_id, date_day)
            // Additional index for date-based queries
            $this->createIndexIfNotExists($table, ['date_day', 'article_id'], 'idx_bs_date_article');
        });

        // 7. Orders - SFA order lookups
        Schema::table('orders', function (Blueprint $table) {
            // Customer order history
            $this->createIndexIfNotExists($table, ['customer_id', 'status', 'created_at'], 'idx_ord_customer_status');
            // Sales rep order tracking
            $this->createIndexIfNotExists($table, ['created_by', 'created_at'], 'idx_ord_creator_time');
            // Status-based workflows
            $this->createIndexIfNotExists($table, ['status', 'created_at'], 'idx_ord_status_time');
        });

        // 8. Order Lines
        Schema::table('order_lines', function (Blueprint $table) {
            // Product order analysis
            $this->createIndexIfNotExists($table, ['product_id', 'created_at'], 'idx_ol_product_time');
            // Order line lookups
            $this->createIndexIfNotExists($table, ['order_id', 'product_id'], 'idx_ol_order_product');
        });

        // 9. Projector Checkpoints - Ensure fast lookups
        Schema::table('projector_checkpoints', function (Blueprint $table) {
            $this->createIndexIfNotExists($table, ['projector_name', 'source_type'], 'idx_pc_projector_source');
            $this->createIndexIfNotExists($table, ['last_outbox_id'], 'idx_pc_outbox');
            $this->createIndexIfNotExists($table, ['last_global_sequence'], 'idx_pc_sequence');
        });

        // 10. Articles - Product catalog lookups
        Schema::table('article', function (Blueprint $table) {
            // Active products by family
            $this->createIndexIfNotExists($table, ['entreprise_id', 'active', 'article_famille_id'], 'idx_art_ent_active_fam');
            // EAN/barcode lookups for mobile scanning
            $this->createIndexIfNotExists($table, ['ean13', 'active'], 'idx_art_ean');
            $this->createIndexIfNotExists($table, ['barcode', 'active'], 'idx_art_barcode');
            // Brand lookups
            $this->createIndexIfNotExists($table, ['article_marque_id', 'active'], 'idx_art_marque');
        });

        // 11. Depot - Warehouse lookups
        Schema::table('depot', function (Blueprint $table) {
            // Already has index on entreprise_id, depot_parent_id
            // Additional index for active warehouse lookups
            $this->createIndexIfNotExists($table, ['entreprise_id', 'is_used'], 'idx_dep_ent_used');
        });

        // 12. Article Unite - Unit conversions
        Schema::table('article_unite', function (Blueprint $table) {
            $this->createIndexIfNotExists($table, ['article_id', 'active'], 'idx_au_article');
        });
    }

    /**
     * Helper method to create index only if it doesn't exist
     */
    private function createIndexIfNotExists(Blueprint $table, array $columns, string $indexName): void
    {
        $tableName = $table->getTable();
        $indexExists = $this->indexExists($tableName, $indexName);
        
        if (!$indexExists) {
            $table->index($columns, $indexName);
        }
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
        
        // Default: assume index doesn't exist for SQLite or other drivers
        return false;
    }

    public function down(): void
    {
        // Event Store
        Schema::table('event_store', function (Blueprint $table) {
            $this->dropIndexIfExists($table, 'idx_ev_aggregate_lookup');
            $this->dropIndexIfExists($table, 'idx_ev_shard_sequence');
            $this->dropIndexIfExists($table, 'idx_ev_type_time');
            $this->dropIndexIfExists($table, 'idx_ev_correlation');
        });

        // Domain Events
        Schema::table('domain_events', function (Blueprint $table) {
            $this->dropIndexIfExists($table, 'idx_de_tenant_aggregate');
            $this->dropIndexIfExists($table, 'idx_de_type_time');
            $this->dropIndexIfExists($table, 'idx_de_event_time');
        });

        // Domain Outbox
        Schema::table('domain_outbox', function (Blueprint $table) {
            $this->dropIndexIfExists($table, 'idx_do_status_retry');
            $this->dropIndexIfExists($table, 'idx_do_event');
        });

        // Aggregate Sequences
        Schema::table('aggregate_sequences', function (Blueprint $table) {
            $this->dropIndexIfExists($table, 'idx_as_type_agg');
        });

        // Article Mouvement
        Schema::table('article_mouvement', function (Blueprint $table) {
            $this->dropIndexIfExists($table, 'idx_am_stock_lookup');
            $this->dropIndexIfExists($table, 'idx_am_event');
            $this->dropIndexIfExists($table, 'idx_am_depot_type');
            $this->dropIndexIfExists($table, 'idx_am_article_time');
        });

        // Balance Stock
        Schema::table('balance_stock', function (Blueprint $table) {
            $this->dropIndexIfExists($table, 'idx_bs_date_article');
        });

        // Orders
        Schema::table('orders', function (Blueprint $table) {
            $this->dropIndexIfExists($table, 'idx_ord_customer_status');
            $this->dropIndexIfExists($table, 'idx_ord_creator_time');
            $this->dropIndexIfExists($table, 'idx_ord_status_time');
        });

        // Order Lines
        Schema::table('order_lines', function (Blueprint $table) {
            $this->dropIndexIfExists($table, 'idx_ol_product_time');
            $this->dropIndexIfExists($table, 'idx_ol_order_product');
        });

        // Projector Checkpoints
        Schema::table('projector_checkpoints', function (Blueprint $table) {
            $this->dropIndexIfExists($table, 'idx_pc_projector_source');
            $this->dropIndexIfExists($table, 'idx_pc_outbox');
            $this->dropIndexIfExists($table, 'idx_pc_sequence');
        });

        // Articles
        Schema::table('article', function (Blueprint $table) {
            $this->dropIndexIfExists($table, 'idx_art_ent_active_fam');
            $this->dropIndexIfExists($table, 'idx_art_ean');
            $this->dropIndexIfExists($table, 'idx_art_barcode');
            $this->dropIndexIfExists($table, 'idx_art_marque');
        });

        // Depot
        Schema::table('depot', function (Blueprint $table) {
            $this->dropIndexIfExists($table, 'idx_dep_ent_used');
        });

        // Article Unite
        Schema::table('article_unite', function (Blueprint $table) {
            $this->dropIndexIfExists($table, 'idx_au_article');
        });
    }

    /**
     * Helper method to drop index only if it exists
     */
    private function dropIndexIfExists(Blueprint $table, string $indexName): void
    {
        $tableName = $table->getTable();
        $indexExists = $this->indexExists($tableName, $indexName);
        
        if ($indexExists) {
            $table->dropIndex($indexName);
        }
    }
};
