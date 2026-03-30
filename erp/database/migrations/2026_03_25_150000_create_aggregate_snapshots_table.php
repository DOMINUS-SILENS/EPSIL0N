<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aggregate_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('aggregate_type');
            $table->string('aggregate_id'); // Varchar to support UUIDs/String IDs
            $table->string('tenant_id')->nullable();
            $table->json('data');
            $table->unsignedBigInteger('last_event_id');
            $table->integer('version')->default(1);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['aggregate_type', 'aggregate_id', 'tenant_id'], 'idx_aggregate_snapshot');
            $table->unique(['aggregate_type', 'aggregate_id', 'tenant_id'], 'uk_aggregate_snapshot');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aggregate_snapshots');
    }
};
