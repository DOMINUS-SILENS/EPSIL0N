<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2C - Hardening: Replay Guard Table
 * This table ensures that each event is processed exactly once per projector
 * in the canonical schema transition.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('canonical_projection_events')) {
            Schema::create('canonical_projection_events', function (Blueprint $table) {
                $table->uuid('event_id');
                $table->string('projector');
                $table->string('aggregate_id')->nullable();
                $table->timestamp('processed_at')->useCurrent();
                
                $table->primary(['event_id', 'projector']);
                $table->index(['projector', 'processed_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('canonical_projection_events');
    }
};
