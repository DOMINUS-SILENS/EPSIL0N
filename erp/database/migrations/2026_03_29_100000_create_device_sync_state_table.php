<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Device Sync State - Tracks per-device sync positions for delta sync
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_sync_state', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entreprise_id')->index();
            $table->string('device_id', 255)->index();
            $table->string('entity_type', 100); // orders, customers, articles, etc.
            $table->timestamp('last_sync_at')->useCurrent();
            $table->string('last_event_id', 255)->nullable();
            $table->unsignedInteger('sync_count')->default(0);
            $table->timestamps();

            // Composite index for efficient lookups
            $table->index(['entreprise_id', 'device_id', 'entity_type'], 'idx_device_sync_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_sync_state');
    }
};
