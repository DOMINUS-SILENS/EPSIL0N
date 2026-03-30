<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merkle_nodes', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('shard_id')->unsigned();
            $table->unsignedBigInteger('node_index');
            $table->string('hash', 64);
            $table->timestamps();
            $table->index(['shard_id', 'node_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merkle_nodes');
    }
};
