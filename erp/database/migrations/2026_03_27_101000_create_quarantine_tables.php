<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quarantined_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id')->unique();
            $table->string('failure_reason');
            $table->text('operator_note')->nullable();
            $table->timestamps();
        });

        Schema::create('quarantined_aggregates', function (Blueprint $table) {
            $table->id();
            $table->string('aggregate_type');
            $table->string('aggregate_id');
            $table->string('quarantine_reason');
            $table->text('operator_note')->nullable();
            $table->timestamps();
            
            $table->unique(['aggregate_type', 'aggregate_id']);
        });

        Schema::create('quarantined_projectors', function (Blueprint $table) {
            $table->id();
            $table->string('projector_name')->unique();
            $table->string('quarantine_reason');
            $table->boolean('is_halted')->default(true);
            $table->text('operator_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quarantined_events');
        Schema::dropIfExists('quarantined_aggregates');
        Schema::dropIfExists('quarantined_projectors');
    }
};
