<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_store', function (Blueprint $table) {
            $table->string('signature', 64)->nullable()->after('merkle_root');
        });
    }

    public function down(): void
    {
        Schema::table('event_store', function (Blueprint $table) {
            $table->dropColumn('signature');
        });
    }
};
