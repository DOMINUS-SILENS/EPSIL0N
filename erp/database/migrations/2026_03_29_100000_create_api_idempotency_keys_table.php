<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('api_idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->string('endpoint');
            $table->string('client_mutation_id');
            $table->string('device_id')->nullable();
            $table->string('aggregate_id')->nullable();
            $table->char('payload_hash', 64);
            $table->enum('status', ['processing', 'completed', 'failed'])->default('processing');
            $table->integer('response_code')->nullable();
            $table->json('response_body')->nullable();
            
            $table->timestamp('locked_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            // Indexes required by Gate A
            $table->unique(['endpoint', 'client_mutation_id'], 'uniq_endpoint_mutation');
            $table->index(['status', 'created_at'], 'idx_status_created');
            $table->index(['device_id', 'created_at'], 'idx_device_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_idempotency_keys');
    }
};
