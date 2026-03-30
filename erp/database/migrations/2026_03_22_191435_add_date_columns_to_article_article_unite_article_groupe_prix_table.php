<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('article_unite_article_groupe_prix', function (Blueprint $table) {
            $table->date('date_debut')->nullable()->after('prix_pourcentage');
            $table->date('date_fin')->nullable()->after('date_debut');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('article_unite_article_groupe_prix', function (Blueprint $table) {
            $table->dropColumn(['date_debut', 'date_fin']);
        });
    }
};
