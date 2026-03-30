<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        if (!Schema::hasTable('article_mouvement')) {
            Schema::create('article_mouvement', function (Blueprint $table) {
                $table->id('article_mouvement_id');
                $table->unsignedBigInteger('entreprise_id')->nullable();
                $table->unsignedBigInteger('founisseur_id')->nullable();
                $table->unsignedBigInteger('article_id')->nullable();
                $table->unsignedBigInteger('article_reference_id')->nullable();
                $table->string('barcode', 30)->nullable();
                $table->string('article_qr_code', 30)->nullable();
                $table->dateTime('article_mouvement_date')->nullable();
                $table->date('article_mouvement_date_day')->nullable();
                $table->dateTime('article_production_date')->nullable();
                $table->date('article_production_date_day')->nullable();
                $table->dateTime('article_expiration_date')->nullable();
                $table->date('article_expiration_date_day')->nullable();
                $table->unsignedBigInteger('article_etat_id')->nullable();
                $table->string('article_serial_number', 30)->nullable();
                $table->unsignedBigInteger('depot_id_source')->nullable();
                $table->unsignedBigInteger('depot_id_destination')->nullable();
                $table->unsignedBigInteger('rangement_id')->nullable();
                $table->string('rangement_path', 256)->nullable();
                $table->unsignedInteger('article_mouvement_unite_id')->nullable();
                $table->double('article_mouvement_quantite')->nullable();
                $table->double('article_mouvement_quantite_retour')->default(0);
                $table->tinyInteger('article_mouvement_vente_operation_type_id')->unsigned()->nullable();
                $table->double('article_mouvement_quantite_restante')->nullable();
                $table->double('article_mouvement_quantite_totale')->default(0);
                $table->tinyInteger('article_mouvement_epuise')->unsigned()->nullable();
                $table->unsignedBigInteger('article_mouvement_operation_type_id')->nullable();
                $table->unsignedBigInteger('article_mouvement_created_by')->nullable();
                $table->dateTime('article_mouvement_created_date')->nullable();
                $table->date('article_mouvement_created_date_day')->nullable();
                $table->unsignedBigInteger('article_mouvement_id_destoquage')->nullable();
                $table->unsignedBigInteger('article_mouvement_lot_debut')->nullable();
                $table->unsignedBigInteger('article_mouvement_lot_fin')->nullable();
                $table->unsignedBigInteger('article_mouvement_ordre')->nullable();
                $table->unsignedBigInteger('mouvement_id_aquisition')->nullable();
                $table->unsignedBigInteger('mouvement_ligne_id_aquisition')->nullable();
                $table->dateTime('mouvement_ligne_aquisition_date')->nullable();
                $table->date('mouvement_ligne_aquisition_date_day')->nullable();
                $table->unsignedBigInteger('mouvement_id_destockage')->nullable();
                $table->unsignedBigInteger('mouvement_ligne_id_destockage')->nullable();
                $table->double('montant_achat_unitaire')->nullable();
                $table->double('montant_achat_total')->nullable();
                $table->double('montant_achat_monnaie')->nullable();
                $table->double('cout_commande')->nullable();
                $table->double('cout_stockage')->nullable();
                $table->double('cout_emballage')->nullable();
                $table->double('cout_livraison')->nullable();
                $table->unsignedBigInteger('validated_stock_by')->nullable();
                $table->dateTime('validated_stock_date')->nullable();
                $table->date('validated_stock_date_day')->nullable();
                $table->tinyInteger('is_packaged')->unsigned()->nullable();
                $table->unsignedBigInteger('package_type_id')->nullable();
                $table->tinyInteger('archived')->unsigned()->nullable();
                $table->tinyInteger('ressource')->unsigned()->nullable();
                $table->unsignedBigInteger('ressource_id')->nullable();
                $table->unsignedBigInteger('ressource_magasinage_by')->nullable();
                $table->dateTime('article_mouvement_stock_entree_date')->nullable();
                $table->date('article_mouvement_stock_entree_date_day')->nullable();
                $table->dateTime('article_mouvement_stock_sortie_date')->nullable();
                $table->date('article_mouvement_stock_sortie_date_day')->nullable();
                $table->tinyInteger('article_mouvement_stock_sortie')->unsigned()->nullable();
                $table->unsignedBigInteger('lang_id')->nullable();
                $table->unsignedBigInteger('article_mouvement_designation_lang_text_id')->nullable();
                $table->tinyInteger('article_mouvement_show_quantity')->unsigned()->nullable();
                $table->tinyInteger('article_mouvement_show_supplier')->unsigned()->nullable();
                $table->tinyInteger('article_mouvement_hidden')->unsigned()->nullable();
                $table->double('article_mouvement_weight')->nullable();
                $table->double('article_mouvement_volume')->nullable();
                $table->unsignedBigInteger('logistique_tracking_number')->nullable();
                $table->unsignedBigInteger('logistique_next_depot_id')->nullable();
                $table->unsignedBigInteger('logistique_final_depot_id')->nullable();
                $table->tinyInteger('logistique_is_delivered')->unsigned()->nullable();
                $table->tinyInteger('logistique_deliver_validated_customer')->unsigned()->nullable();
                $table->dateTime('logistique_deliver_validated_date')->nullable();
                $table->date('logistique_deliver_validated_date_day')->nullable();
                $table->string('logistique_deliver_validated_signature', 256)->nullable();
                $table->tinyInteger('logistique_customer_notified')->unsigned()->nullable();
                $table->unsignedBigInteger('comptabilite_type')->nullable();
                $table->unsignedBigInteger('entrepot_source')->nullable();
                $table->unsignedBigInteger('entrepot_destination')->nullable();
                $table->integer('stock_operation_type')->unsigned()->nullable();
                $table->double('montant_achat_unit_pondere')->nullable();
                $table->double('montant_achat_valeur_total_stock')->nullable();
                $table->double('montant_vente_unitaire')->nullable();
                $table->double('montant_vente_total')->nullable();
                $table->string('article_mouvement_couleur', 30)->nullable();
                $table->string('article_mouvement_taille', 30)->nullable();
                $table->string('article_mouvement_version', 30)->nullable();
                $table->string('article_mouvement_commentaire', 50)->nullable();
                $table->unsignedBigInteger('relation_article_mouvement_id')->nullable();
                $table->double('package_cost')->default(0);
                $table->tinyInteger('relation_etat_id')->unsigned()->nullable();
                $table->tinyInteger('sync_id')->unsigned()->nullable();
                $table->tinyInteger('sync_data')->unsigned()->nullable();

                $table->timestamps();

                $table->primary('article_mouvement_id');
                $table->index('article_id');
                $table->index('entreprise_id');
                $table->index('depot_id_source');
                $table->index('depot_id_destination');
                $table->index('article_mouvement_unite_id');
                $table->index('stock_operation_type');
                $table->index('article_mouvement_created_date_day');
                $table->index('mouvement_id_aquisition');
                $table->index('mouvement_id_destockage');
                $table->index('relation_article_mouvement_id');
            });

            // Foreign keys
            Schema::table('article_mouvement', function (Blueprint $table) {
                $table->foreign('article_id')->references('article_id')->on('article')->onDelete('set null');
                $table->foreign('entreprise_id')->references('entreprise_id')->on('entreprise')->onDelete('cascade');
                $table->foreign('depot_id_source')->references('depot_id')->on('depot')->onDelete('set null');
                $table->foreign('depot_id_destination')->references('depot_id')->on('depot')->onDelete('set null');
                $table->foreign('article_mouvement_unite_id')->references('article_unite_id')->on('article_unite')->onDelete('set null');

            // Additional foreign keys can be added as needed
            });

        }
    }

    public function down(): void
    {
        Schema::dropIfExists('article_mouvement');
    }
};
