<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Normalize 'customers' table
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                if (Schema::hasColumn('customers', 'entreprise_id') && !Schema::hasColumn('customers', 'entreprise_id')) {
                    $table->renameColumn('entreprise_id', 'entreprise_id');
                } elseif (!Schema::hasColumn('customers', 'entreprise_id')) {
                    $table->unsignedBigInteger('entreprise_id')->default(1)->after('id');
                }
            });
        }

        // 2. Normalize 'orders' table
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (!Schema::hasColumn('orders', 'entreprise_id')) {
                    $table->unsignedBigInteger('entreprise_id')->default(1)->after('id');
                    $table->index('entreprise_id', 'idx_ord_tenant');
                }
            });
        }

        // 3. Normalize 'order_lines'
        if (Schema::hasTable('order_lines')) {
            Schema::table('order_lines', function (Blueprint $table) {
                if (!Schema::hasColumn('order_lines', 'entreprise_id')) {
                    $table->unsignedBigInteger('entreprise_id')->default(1)->after('id');
                }
            });
        }

        // 4. Normalize 'device_sync_state' table
        if (Schema::hasTable('device_sync_state')) {
            Schema::table('device_sync_state', function (Blueprint $table) {
                // Handle Tenant column
                if (Schema::hasColumn('device_sync_state', 'entreprise_id') && !Schema::hasColumn('device_sync_state', 'entreprise_id')) {
                    $table->renameColumn('entreprise_id', 'entreprise_id');
                } elseif (!Schema::hasColumn('device_sync_state', 'entreprise_id')) {
                    $table->unsignedBigInteger('entreprise_id')->default(1)->after('id');
                }

                // Handle Sync Columns
                if (Schema::hasColumn('device_sync_state', 'last_sync_at') && !Schema::hasColumn('device_sync_state', 'last_sync_timestamp')) {
                    $table->renameColumn('last_sync_at', 'last_sync_timestamp');
                } elseif (!Schema::hasColumn('device_sync_state', 'last_sync_timestamp')) {
                    $table->bigInteger('last_sync_timestamp')->nullable()->after('entity_type');
                }

                if (Schema::hasColumn('device_sync_state', 'last_event_id') && !Schema::hasColumn('device_sync_state', 'last_sync_sequence')) {
                    $table->renameColumn('last_event_id', 'last_sync_sequence');
                } elseif (!Schema::hasColumn('device_sync_state', 'last_sync_sequence')) {
                    $table->bigInteger('last_sync_sequence')->nullable()->after('last_sync_timestamp');
                }
            });
        }
    }

    public function down(): void
    {
        // Revert logic omitted for brevity/simplicity in this fix phase
    }
};
