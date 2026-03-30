<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domain_outbox', function (Blueprint $table) {
            $table->id();
            $table->string('aggregate_type', 100);
            $table->unsignedBigInteger('aggregate_id');
            $table->unsignedBigInteger('sequence');
            $table->string('event_type', 100);
            $table->json('payload');
            $table->enum('status', ['pending', 'processed'])->default('pending');
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['aggregate_type', 'aggregate_id', 'sequence']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_outbox');
    }
};
