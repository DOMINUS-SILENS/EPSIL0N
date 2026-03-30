<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        if (!Schema::hasTable('article_unite')) {
            Schema::create('article_unite', function (Blueprint $table) {
                $table->increments('article_unite_id');
                // Inside the table creation, after defining columns:
                $table->unique(['article_id', 'article_unite_id']);
                $table->unsignedBigInteger('article_id');
                $table->string('barcode', 30)->nullable();
                $table->string('article_qr_code', 256)->nullable();
                $table->double('article_poids')->nullable();
                $table->double('article_volume')->nullable();
                $table->double('article_longueur')->nullable();
                $table->double('article_largeur')->nullable();
                $table->double('article_hauteur')->nullable();
                $table->double('article_prix_revient')->nullable();
                $table->double('article_prix_achat_moyen')->nullable();
                $table->double('article_prix_vente')->default(0);
                $table->double('article_prix_min_autorise')->nullable();
                $table->tinyInteger('article_prix_online_show')->unsigned()->nullable();
                $table->double('article_prix_online_prix')->nullable();
                $table->tinyInteger('is_article_prix_change_autorised')->unsigned()->nullable();
                $table->double('article_taux_fidelite')->nullable();
                $table->double('article_montant_fidelite')->nullable();
                $table->tinyInteger('active')->unsigned()->default(1);
                $table->tinyInteger('is_default')->unsigned()->nullable();
                $table->double('article_unite_quantite')->nullable();
                $table->double('artilce_unite_quantite_min')->nullable();
                $table->unsignedInteger('artilce_unite_quantite_in_use')->nullable();
                $table->unsignedInteger('quantite_per_unit')->nullable();
                $table->double('article_unite_quantite_virtuel')->nullable();
                $table->unsignedBigInteger('bouteille_id')->nullable();
                $table->unsignedBigInteger('caisse_id')->nullable();
                $table->unsignedInteger('bouteille_quantite')->nullable();
                $table->unsignedInteger('caisse_quantite')->nullable();
                $table->timestamps();

                $table->primary('article_unite_id');
                $table->index('article_id');
                $table->index('barcode');
                $table->index('is_default');
                $table->index('active');
            });

            // Foreign key
            Schema::table('article_unite', function (Blueprint $table) {
                $table->foreign('article_id')->references('article_id')->on('article')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('article_unite');
    }
};
