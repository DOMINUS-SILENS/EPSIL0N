<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create shards table only if it doesn't exist
        if (! Schema::hasTable('event_shards')) {
            Schema::create('event_shards', function (Blueprint $table) {
                $table->tinyInteger('id')->unsigned()->primary();
                $table->string('name');
                $table->timestamp('created_at')->useCurrent();
            });

            // Pre‑populate shards
            for ($i = 0; $i < 16; $i++) {
                DB::table('event_shards')->insert(['id' => $i, 'name' => "shard_{$i}"]);
            }
        }

        // Create event store table only if it doesn't exist
        if (! Schema::hasTable('event_store')) {
            Schema::create('event_store', function (Blueprint $table) {
                $table->id();
                $table->tinyInteger('shard_id')->unsigned();
                $table->unsignedBigInteger('local_sequence');
                $table->unsignedBigInteger('global_sequence')->nullable();
                $table->string('event_type', 100);
                $table->string('aggregate_type', 100);
                $table->unsignedBigInteger('aggregate_id');
                $table->json('payload');
                $table->json('metadata')->nullable();
                $table->string('previous_hash', 64);
                $table->string('merkle_root', 64);
                $table->timestamp('created_at')->useCurrent();
                $table->string('correlation_id', 36)->nullable();
                $table->unique(['shard_id', 'local_sequence']);
                $table->index(['aggregate_type', 'aggregate_id']);
                $table->index('global_sequence');
            });

            // Add partitioning – if it fails, assume already partitioned
            try {
                DB::statement('ALTER TABLE event_store PARTITION BY HASH(shard_id) PARTITIONS 16');
            } catch (Exception $e) {
                // Partition already exists or not supported; ignore
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('event_store');
        Schema::dropIfExists('event_shards');
    }
};
