<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add fields to domain_events
        Schema::table('domain_events', function (Blueprint $table) {
            if (!Schema::hasColumn('domain_events', 'source_device_id')) {
                $table->string('source_device_id', 255)->nullable()->after('recorded_at');
            }
            if (!Schema::hasColumn('domain_events', 'source_user_id')) {
                $table->string('source_user_id', 255)->nullable()->after('source_device_id');
            }
        });

        // 2. Idempotency Keys
        if (!Schema::hasTable('idempotency_keys')) {
            Schema::create('idempotency_keys', function (Blueprint $table) {
                $table->string('key', 255)->primary();
                $table->dateTime('created_at')->useCurrent();
            });
        }

        // 3. Aggregate Sequences
        if (!Schema::hasTable('aggregate_sequences')) {
            Schema::create('aggregate_sequences', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('entreprise_id');
                $table->string('aggregate_type', 255);
                $table->string('aggregate_id', 255);
                $table->unsignedInteger('current_sequence')->default(0);
                $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();

                $table->unique(['entreprise_id', 'aggregate_type', 'aggregate_id'], 'unique_aggregate');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('aggregate_sequences');
        Schema::dropIfExists('idempotency_keys');
        
        Schema::table('domain_events', function (Blueprint $table) {
            $table->dropColumn(['source_device_id', 'source_user_id']);
        });
    }
};
