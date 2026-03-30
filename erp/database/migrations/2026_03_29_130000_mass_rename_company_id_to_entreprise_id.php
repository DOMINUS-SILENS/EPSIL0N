<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $tablesWithVirtual = [
            'article', 'article_mouvement', 'depot', 'article_famille', 
            'article_marque', 'article_unite', 'article_groupe_prix', 
            'mouvement', 'mouvement_ligne', 'article_unite_depot'
        ];

        $tablesWithReal = [
            'customers', 'balance_stock', 'audit_logs', 'device_sync_state',
            'purchase_orders', 'projects', 'employees', 'vehicles',
            'idempotency_keys', 'api_idempotency_keys', 'aggregate_sequences', 'users',
            'credit_reservations', 'stock_reservations', 'journal_entries',
            'sync_conflicts', 'alert_rules', 'domain_events', 'event_store',
            'orders', 'order_lines', 'domain_outbox', 'failed_outbox_events',
            'integration_outbox', 'aggregate_snapshots', 'sagas', 'saga_steps',
            'saga_state', 'contracts', 'intents', 'anomalies', 'decision_audit',
            'projection_snapshots', 'customer_balance_projections'
        ];

        // 1. Drop Virtual Columns first
        foreach ($tablesWithVirtual as $table) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) {
                    if (Schema::hasColumn($table->getTable(), 'entreprise_id')) {
                        // In MySQL, we drop the virtual column
                        $table->dropColumn('entreprise_id');
                    }
                });
            }
        }

        // 2. Rename or Add Real Columns
        foreach ($tablesWithReal as $table) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) {
                    $tableName = $table->getTable();
                    $hasEntrepriseId = Schema::hasColumn($tableName, 'entreprise_id');
                    $hasCompanyId = Schema::hasColumn($tableName, 'entreprise_id');
                    $hasTenantId = Schema::hasColumn($tableName, 'tenant_id');
                    
                    if ($hasCompanyId) {
                        if (!$hasEntrepriseId) {
                            $table->renameColumn('entreprise_id', 'entreprise_id');
                            $hasEntrepriseId = true;
                        } else {
                            $table->dropColumn('entreprise_id');
                        }
                    }

                    if ($hasTenantId) {
                        if (!$hasEntrepriseId) {
                            $table->renameColumn('tenant_id', 'entreprise_id');
                            $hasEntrepriseId = true;
                        } else {
                            $table->dropColumn('tenant_id');
                        }
                    }

                    if (!$hasEntrepriseId) {
                        $column = $table->unsignedBigInteger('entreprise_id')->default(1)->index();
                        if (Schema::hasColumn($tableName, 'id')) {
                            $column->after('id');
                        }
                    }
                });
            }
        }
    }

    public function down(): void
    {
        // Reverse normalization if needed (not recommended)
    }
};
