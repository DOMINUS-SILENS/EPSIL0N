<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dead_letters', function (Blueprint $table) {
            $table->string('failure_class')->default('UNKNOWN_ERROR')->after('event_id');
        });
    }

    public function down(): void
    {
        Schema::table('dead_letters', function (Blueprint $table) {
            $table->dropColumn('failure_class');
        });
    }
};
