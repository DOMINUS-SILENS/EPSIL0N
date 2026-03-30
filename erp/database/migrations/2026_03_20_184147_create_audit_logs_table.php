// database/migrations/2025_01_01_000006_create_audit_logs_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entreprise_id');
            $table->unsignedBigInteger('sequence');
            $table->string('previous_hash', 64);
            $table->string('row_hash', 64);
            $table->string('action', 50);
            $table->string('model', 100);
            $table->unsignedBigInteger('model_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('reason')->nullable();
            $table->string('trace_id', 36)->nullable();
            $table->timestamp('event_time')->useCurrent();
            $table->timestamp('recorded_at')->useCurrent();
            $table->unique(['entreprise_id', 'sequence']);
            $table->index('trace_id');
            $table->index(['model', 'model_id']);
            $table->index('previous_hash');
        });

        // Add partitioning on entreprise_id using KEY (hash) partitioning
        // This requires running raw SQL after creation
        //  DB::statement('ALTER TABLE audit_logs PARTITION BY KEY(entreprise_id) PARTITIONS 16');
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
