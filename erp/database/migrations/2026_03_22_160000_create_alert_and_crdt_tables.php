<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Alert Rules table
        Schema::create('alert_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entreprise_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('metric_type'); // stock_balance, credit_limit, sales_rate, etc.
            $table->json('conditions'); // threshold, operator, aggregation, time_window
            $table->json('actions'); // webhooks, emails, etc.
            $table->string('severity')->default('warning'); // info, warning, critical
            $table->boolean('enabled')->default(true);
            $table->unsignedInteger('cooldown_minutes')->default(60);
            $table->timestamp('last_triggered_at')->nullable();
            $table->unsignedInteger('trigger_count')->default(0);
            $table->timestamps();

            $table->index(['entreprise_id', 'enabled', 'last_triggered_at']);
        });

        // Alert History Log
        Schema::create('alert_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entreprise_id');
            $table->foreignId('alert_rule_id')->constrained()->onDelete('cascade');
            $table->decimal('metric_value', 15, 4);
            $table->decimal('threshold', 15, 4);
            $table->string('status'); // triggered, acknowledged, resolved
            $table->json('context')->nullable(); // additional data at time of trigger
            $table->timestamps();

            $table->index(['entreprise_id', 'created_at']);
        });

        // CRDT State for mobile offline sync
        Schema::create('crdt_states', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entreprise_id');
            $table->string('entity_type'); // visit, order_line, stock_adjustment
            $table->unsignedBigInteger('entity_id');
            $table->string('replica_id'); // mobile device UUID
            $table->json('vector_clock');
            $table->json('state'); // CRDT payload
            $table->timestamp('timestamp');
            $table->timestamps();

            $table->unique(['entreprise_id', 'entity_type', 'entity_id', 'replica_id']);
            $table->index(['entreprise_id', 'entity_type', 'replica_id']);
        });

        // CRDT Operations Queue (pending sync)
        Schema::create('crdt_operations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entreprise_id');
            $table->string('operation_type'); // gc_inc, lww_set, or_add
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->string('replica_id');
            $table->json('vector_clock');
            $table->json('payload');
            $table->string('status')->default('pending'); // pending, synced, conflict
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->index(['entreprise_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crdt_operations');
        Schema::dropIfExists('crdt_states');
        Schema::dropIfExists('alert_history');
        Schema::dropIfExists('alert_rules');
    }
};
