<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projector_checkpoints', function (Blueprint $table) {
            $table->id();
            $table->string('projector_name');
            $table->string('source_type')->default('event_store'); // EVENT_STORE vs OUTBOX
            $table->unsignedBigInteger('last_outbox_id')->nullable();
            $table->unsignedBigInteger('last_global_sequence')->nullable();
            $table->timestamp('last_processed_at')->nullable();
            $table->timestamps();

            $table->unique(['projector_name', 'source_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projector_checkpoints');
    }
};
