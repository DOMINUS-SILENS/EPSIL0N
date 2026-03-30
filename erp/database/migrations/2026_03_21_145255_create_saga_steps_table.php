<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saga_steps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('saga_id');
            $table->unsignedInteger('step_index');
            $table->string('command_type', 100);
            $table->json('command_payload');
            $table->string('compensation_type', 100)->nullable();
            $table->json('compensation_payload')->nullable();
            $table->enum('status', ['pending', 'executed', 'compensated'])->default('pending');
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();

            $table->unique(['saga_id', 'step_index']);
            $table->foreign('saga_id')->references('id')->on('sagas')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saga_steps');
    }
};
