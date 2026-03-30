<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('failed_outbox_events', function (Blueprint $table) {
            $table->string('failure_class', 50)->default('TRANSIENT');
            $table->string('failure_reason_code', 100)->nullable();
            $table->integer('attempt_count')->default(1);
            $table->timestamp('first_failed_at')->nullable();
            $table->text('operator_note')->nullable();
            $table->string('status', 50)->default('failed'); // failed, replayed, dropped
            $table->timestamp('replayed_at')->nullable();
            
            $table->renameColumn('failed_at', 'last_failed_at');
            $table->renameColumn('error_message', 'failure_reason');
        });
    }

    public function down(): void
    {
        // Safe down logic
    }
};
