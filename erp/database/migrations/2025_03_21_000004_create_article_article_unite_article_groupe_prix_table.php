<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        if (!Schema::hasTable('article_unite_article_groupe_prix')) {
            Schema::create('article_unite_article_groupe_prix', function (Blueprint $table) {
                $table->unsignedBigInteger('article_id');
                $table->unsignedInteger('article_unite_id');
                $table->unsignedBigInteger('article_groupe_prix_id');
                $table->double('prix_vente_ht');
                $table->double('prix_pourcentage')->nullable();
                $table->unsignedBigInteger('last_updated_by_id')->nullable();
                $table->timestamps();

                $table->primary(['article_id', 'article_unite_id', 'article_groupe_prix_id']);

                // Foreign keys
                $table->foreign('article_unite_id', 'fk_aaugp_unite')
                    ->references('article_unite_id')->on('article_unite')
                    ->onDelete('cascade');
                $table->foreign('article_groupe_prix_id', 'fk_aaugp_groupe')
                    ->references('article_groupe_prix_id')->on('article_groupe_prix')
                    ->onDelete('cascade');
            // Note: article_id is not a foreign key; it's denormalized
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('article_unite_article_groupe_prix');
    }
};
