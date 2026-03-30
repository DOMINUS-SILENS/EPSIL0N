<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saga_states', function (Blueprint $table) {
            $table->unsignedBigInteger('last_event_id')->nullable()->after('correlation_id');
            $table->timestamp('timeout_at')->nullable()->after('last_event_id');
            // Causal index to prevent executing older events out of order or multiple times
            $table->index('last_event_id');
        });
    }

    public function down(): void
    {
        Schema::table('saga_states', function (Blueprint $table) {
            $table->dropIndex(['last_event_id']);
            $table->dropColumn(['last_event_id', 'timeout_at']);
        });
    }
};
