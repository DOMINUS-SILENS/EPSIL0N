<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anomalies', function (Blueprint $table) {
            $table->id();
            $table->string('type');           // duplicate_command, excessive_retry, etc.
            $table->json('context');
            $table->timestamp('detected_at')->useCurrent();
            $table->boolean('resolved')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anomalies');
    }
};
