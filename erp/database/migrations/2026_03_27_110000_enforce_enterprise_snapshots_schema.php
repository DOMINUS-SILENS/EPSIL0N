<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('aggregate_snapshots');

        Schema::create('aggregate_snapshots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_id')->index();
            $table->string('aggregate_id')->index();
            $table->string('aggregate_type')->index();
            $table->unsignedInteger('schema_version')->default(1);
            $table->unsignedBigInteger('last_aggregate_sequence');
            $table->unsignedBigInteger('last_event_store_id')->nullable();
            $table->json('state_json');
            $table->string('state_hash');
            $table->text('signature')->nullable();
            $table->timestamps();

            $table->unique(
                ['tenant_id', 'aggregate_id', 'aggregate_type', 'last_aggregate_sequence'], 
                'agg_snapshot_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aggregate_snapshots');
    }
};
