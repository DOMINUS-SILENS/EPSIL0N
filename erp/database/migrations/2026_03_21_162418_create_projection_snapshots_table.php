<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projection_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('projector_name');
            $table->unsignedBigInteger('aggregate_id');
            $table->json('snapshot');
            $table->unsignedBigInteger('last_event_id');
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['projector_name', 'aggregate_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projection_snapshots');
    }
};
