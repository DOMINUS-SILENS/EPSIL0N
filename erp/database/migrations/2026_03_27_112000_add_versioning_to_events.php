<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_store', function (Blueprint $table) {
            $table->unsignedInteger('event_version')->default(1)->after('event_type');
            $table->uuid('causation_id')->nullable()->after('correlation_id');
        });

        Schema::table('domain_events', function (Blueprint $table) {
            $table->unsignedInteger('event_version')->default(1)->after('event_type');
            $table->uuid('correlation_id')->nullable()->after('event_type');
            $table->uuid('causation_id')->nullable()->after('correlation_id');
        });
    }

    public function down(): void
    {
        Schema::table('event_store', function (Blueprint $table) {
            $table->dropColumn(['event_version', 'causation_id']);
        });

        Schema::table('domain_events', function (Blueprint $table) {
            $table->dropColumn(['event_version', 'correlation_id', 'causation_id']);
        });
    }
};
