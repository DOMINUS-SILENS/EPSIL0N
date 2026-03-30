<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('mouvement')) {
            Schema::create('mouvement', function (Blueprint $table) {
                $table->id('mouvement_id');
                $table->unsignedBigInteger('entreprise_id')->nullable();
                // Minimal fields – can be extended later
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mouvement');
    }
};
