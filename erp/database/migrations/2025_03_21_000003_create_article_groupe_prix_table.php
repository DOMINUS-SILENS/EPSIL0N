<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('article_groupe_prix')) {
            Schema::create('article_groupe_prix', function (Blueprint $table) {
                $table->id('article_groupe_prix_id');
                $table->unsignedBigInteger('entreprise_id');
                $table->string('article_groupe_prix_designation', 256);
                $table->integer('article_groupe_prix_pourcentage');
                $table->string('column_name', 56)->nullable(); // from trigger
                $table->timestamps();

                $table->index('entreprise_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('article_groupe_prix');
    }
};
