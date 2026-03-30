<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('failed_outbox_events', function (Blueprint $table) {
            $table->id();
            $table->string('aggregate_type');
            $table->string('aggregate_id');
            $table->string('event_type');
            $table->json('payload');
            $table->string('error_message')->nullable();
            $table->json('error_context')->nullable();
            $table->unsignedBigInteger('original_event_id')->nullable();
            $table->timestamp('failed_at')->useCurrent();
            
            $table->index(['aggregate_type', 'aggregate_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('failed_outbox_events');
    }
};
