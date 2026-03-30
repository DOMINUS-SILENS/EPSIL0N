<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('event_shard_sequences')) {
            Schema::create('event_shard_sequences', function (Blueprint $table) {
                $table->tinyInteger('shard_id')->unsigned()->primary();
                $table->unsignedBigInteger('seq')->default(0);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('event_shard_sequences');
    }
};
