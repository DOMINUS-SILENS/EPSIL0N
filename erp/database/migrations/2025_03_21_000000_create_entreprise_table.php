<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('entreprise')) {
            Schema::create('entreprise', function (Blueprint $table) {
                $table->id('entreprise_id');
                $table->string('nom')->nullable();
                $table->string('raison_sociale')->nullable();
                $table->string('adresse')->nullable();
                $table->string('telephone')->nullable();
                $table->string('email')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('entreprise');
    }
};
