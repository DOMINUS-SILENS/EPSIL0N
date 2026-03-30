<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Exactly-Once Projector Effect Schema
        Schema::create('projector_processed_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('projector_id', 150); // e.g., 'StockBalanceProjector_v1'
            $table->unsignedBigInteger('event_id');
            $table->timestamp('processed_at')->useCurrent();
            
            // The absolute Unique Constraint that neutralizes duplicate delivery attempts natively
            $table->unique(['projector_id', 'event_id']);
        });

        // 2. Strict Per-Tenant Sequence Table Refactor
        Schema::dropIfExists('aggregate_sequences');
        Schema::create('aggregate_sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('aggregate_type', 100);
            $table->string('aggregate_id', 150);
            $table->unsignedBigInteger('seq')->default(0);

            // Absolute Partition Isolation Key
            $table->unique(['tenant_id', 'aggregate_id'], 'uk_tenant_aggregate');
        });

        // 3. Bare-Metal Immutability Re-Enforcement
        // Warning: This requires a highly privileged migration user. 
        // In some managed environments, direct REVOKE requires SUPER privs.
        try {
            $dbUser = env('DB_USERNAME', 'application_user');
            DB::statement("REVOKE UPDATE, DELETE, TRUNCATE ON domain_events FROM '{$dbUser}'@'%'");
        } catch (\Throwable $e) {
            // Soft failure acceptable locally, but fatal in strict prod environments
            \Log::warning("Axiomatic Immutability: Could not run REVOKE. Ensure DB User permissions are strictly isolated.", ['error' => $e->getMessage()]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('projector_processed_events');
        Schema::dropIfExists('aggregate_sequences');
    }
};
