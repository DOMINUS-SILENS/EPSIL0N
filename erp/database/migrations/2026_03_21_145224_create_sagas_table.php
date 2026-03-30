<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sagas', function (Blueprint $table) {
            $table->id();
            $table->string('saga_type', 100);
            $table->string('saga_id', 100);          // business identifier
            $table->enum('state', ['pending', 'completed', 'compensating', 'failed'])->default('pending');
            $table->json('context');
            $table->unsignedInteger('current_step')->default(0);
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->unique(['saga_type', 'saga_id']);
            $table->index('state');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sagas');
    }
};
