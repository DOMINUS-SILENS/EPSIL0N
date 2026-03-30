<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_modes', function (Blueprint $table) {
            $table->id();
            $table->string('mode')->unique();
            $table->boolean('is_active')->default(false);
            $table->json('rules')->nullable();
            $table->timestamp('set_at')->useCurrent();
        });

        // Pre-populate
        DB::table('system_modes')->insert([
            ['mode' => 'NORMAL', 'is_active' => true],
            ['mode' => 'DEGRADED', 'is_active' => false],
            ['mode' => 'SAFE_HALT', 'is_active' => false],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('system_modes');
    }
};
