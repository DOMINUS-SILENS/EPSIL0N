<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saga_states', function (Blueprint $table) {
            $table->uuid('saga_id')->primary();
            $table->string('saga_type');
            $table->string('status', 50)->default('started'); // started, completed, failed
            $table->json('state');
            $table->uuid('correlation_id')->index()->nullable(); // ID to group related events
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saga_states');
    }
};
