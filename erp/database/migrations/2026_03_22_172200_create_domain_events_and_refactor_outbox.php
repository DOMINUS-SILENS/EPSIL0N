<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('dead_letters');
        Schema::dropIfExists('domain_outbox');
        
        // Pure Immutable Source of Truth
        Schema::create('domain_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('event_store_id')->nullable()->index(); // Link to sharded store (nullable for legacy)
            $table->string('aggregate_type', 100);
            $table->string('aggregate_id', 150);
            $table->unsignedBigInteger('sequence');
            $table->string('event_type', 100);
            $table->json('payload');
            $table->timestamp('event_time')->useCurrent();
            $table->timestamp('recorded_at')->useCurrent();
            
            // Infinite deterministic partition boundary
            $table->unique(['tenant_id', 'aggregate_id', 'sequence']);
        });

        // Delivery Metadata Only
        Schema::create('domain_outbox', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id');
            $table->string('status', 30)->default('pending'); // pending, processing, failed
            $table->unsignedInteger('attempts')->default(0);
            $table->unsignedInteger('max_attempts')->default(5);
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->text('last_error')->nullable();

            $table->foreign('event_id')->references('id')->on('domain_events')->onDelete('cascade');
        });

        // Permanently failed delivery separation
        Schema::create('dead_letters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id');
            $table->text('last_error')->nullable();
            $table->unsignedInteger('final_attempts')->default(0);
            $table->timestamp('failed_at')->useCurrent();
            
            $table->foreign('event_id')->references('id')->on('domain_events')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dead_letters');
        Schema::dropIfExists('domain_outbox');
        Schema::dropIfExists('domain_events');
    }
};
