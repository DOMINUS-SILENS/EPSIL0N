<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('domain_outbox', function (Blueprint $table) {
            // Ensure status can accommodate processing, failed, dead
            $table->string('status', 30)->default('pending')->change();
            
            $table->unsignedInteger('attempts')->default(0);
            $table->unsignedInteger('max_attempts')->default(5);
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->text('last_error')->nullable();
        });

        Schema::create('dead_letters', function (Blueprint $table) {
            $table->id();
            $table->string('aggregate_type', 100);
            $table->string('aggregate_id', 150); // using string to support UUIDs heavily used in app
            $table->unsignedBigInteger('sequence');
            $table->string('event_type', 100);
            $table->json('payload');
            $table->text('last_error')->nullable();
            $table->unsignedInteger('final_attempts')->default(0);
            $table->timestamp('failed_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::table('domain_outbox', function (Blueprint $table) {
            $table->dropColumn(['attempts', 'max_attempts', 'next_retry_at', 'processed_at', 'last_error']);
        });
        Schema::dropIfExists('dead_letters');
    }
};
