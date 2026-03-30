<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('device_sync_state')) {
            Schema::table('device_sync_state', function (Blueprint $table) {
                if (!Schema::hasColumn('device_sync_state', 'entreprise_id')) {
                    $table->unsignedBigInteger('entreprise_id')->default(1)->after('id');
                }
                
                // Also ensure sync columns exist if they were missed
                if (!Schema::hasColumn('device_sync_state', 'last_sync_timestamp')) {
                    if (Schema::hasColumn('device_sync_state', 'last_sync_at')) {
                        $table->renameColumn('last_sync_at', 'last_sync_timestamp');
                    } else {
                        $table->bigInteger('last_sync_timestamp')->nullable()->after('entity_type');
                    }
                }

                if (!Schema::hasColumn('device_sync_state', 'last_sync_sequence')) {
                    if (Schema::hasColumn('device_sync_state', 'last_event_id')) {
                        $table->renameColumn('last_event_id', 'last_sync_sequence');
                    } else {
                        $table->bigInteger('last_sync_sequence')->nullable()->after('last_sync_timestamp');
                    }
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('device_sync_state')) {
            Schema::table('device_sync_state', function (Blueprint $table) {
                if (Schema::hasColumn('device_sync_state', 'entreprise_id')) {
                    $table->dropColumn('entreprise_id');
                }
            });
        }
    }
};
