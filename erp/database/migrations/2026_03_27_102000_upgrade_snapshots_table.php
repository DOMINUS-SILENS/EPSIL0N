<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aggregate_snapshots', function (Blueprint $table) {
            $table->unsignedBigInteger('last_sequence')->default(0)->after('last_event_id');
            $table->string('schema_version', 10)->default('1.0');
            $table->string('payload_hash')->nullable();
        });
    }

    public function down(): void
    {
        // ...
    }
};
