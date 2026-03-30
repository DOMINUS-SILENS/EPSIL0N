<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saga_steps', function (Blueprint $table) {
            $table->unsignedInteger('retry_count')->default(0);
            $table->unsignedInteger('max_retries')->default(3);
            $table->string('status', 30)->default('pending')->change();
            $table->text('last_error')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('saga_steps', function (Blueprint $table) {
            $table->dropColumn(['retry_count', 'max_retries', 'last_error']);
        });
    }
};
