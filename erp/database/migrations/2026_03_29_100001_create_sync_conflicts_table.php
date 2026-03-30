<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sync Conflicts - Stores conflicts for manual resolution
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_conflicts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entreprise_id')->index();
            $table->string('event_id', 255)->index();
            $table->string('aggregate_type', 100);
            $table->string('aggregate_id', 255);
            $table->json('client_payload');
            $table->json('server_payload')->nullable();
            $table->timestamp('client_timestamp');
            $table->timestamp('server_timestamp');
            $table->string('conflict_type', 50); // field_conflict, status_transition_conflict, etc.
            $table->enum('status', ['pending', 'resolved', 'ignored'])->default('pending');
            $table->string('resolution_strategy', 50)->nullable(); // client_wins, server_wins, merge
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['entreprise_id', 'status'], 'idx_conflicts_status');
            $table->index(['entreprise_id', 'created_at'], 'idx_conflicts_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_conflicts');
    }
};
