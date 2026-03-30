<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('article_mouvement_mouvement_ligne')) {
            Schema::create('article_mouvement_mouvement_ligne', function (Blueprint $table) {
                $table->unsignedBigInteger('article_mouvement_id');
                $table->unsignedBigInteger('mouvement_ligne_id');
                $table->unsignedBigInteger('mouvement_operation_type_id')->nullable();
                $table->string('mouvement_operation_type_designation', 256)->nullable();
                $table->timestamps();

                $table->primary(['article_mouvement_id', 'mouvement_ligne_id']);
            });

            // Foreign keys with short names
            Schema::table('article_mouvement_mouvement_ligne', function (Blueprint $table) {
                $table->foreign('article_mouvement_id', 'fk_amml_am')
                    ->references('article_mouvement_id')
                    ->on('article_mouvement')
                    ->onDelete('cascade');

                $table->foreign('mouvement_ligne_id', 'fk_amml_ml')
                    ->references('mouvement_ligne_id')
                    ->on('mouvement_ligne')
                    ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('article_mouvement_mouvement_ligne');
    }
};
