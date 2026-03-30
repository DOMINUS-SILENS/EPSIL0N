<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        if (!Schema::hasTable('article_unite_depot')) {
            Schema::create('article_unite_depot', function (Blueprint $table) {
                $table->unsignedBigInteger('article_id');
                $table->unsignedInteger('unite_id');
                $table->unsignedBigInteger('depot_id');
                $table->double('quantite')->default(0);
                $table->timestamps();

                $table->primary(['article_id', 'unite_id', 'depot_id']);
                $table->index('depot_id');
                $table->index('unite_id');
            });

            // Foreign keys with custom names
            Schema::table('article_unite_depot', function (Blueprint $table) {
                $table->foreign('unite_id', 'fk_aau_depot_unite')
                    ->references('article_unite_id')
                    ->on('article_unite')
                    ->onDelete('cascade');
                $table->foreign('depot_id', 'fk_aau_depot_depot')
                    ->references('depot_id')
                    ->on('depot')
                    ->onDelete('cascade');
                // article_id is denormalized; we could add a foreign key to article table if desired
                $table->foreign('article_id', 'fk_aau_depot_article')
                    ->references('article_id')
                    ->on('article')
                    ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('article_unite_depot');
    }
};
