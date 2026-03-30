<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('article')) {
            Schema::create('article', function (Blueprint $table) {
                $table->id('article_id');
                $table->unsignedBigInteger('entreprise_id');
                $table->text('designation');
                $table->string('article_abreviation', 10)->nullable();
                $table->unsignedBigInteger('article_lang_id')->nullable();
                $table->unsignedBigInteger('designation_lang_text_id')->nullable();
                $table->unsignedBigInteger('article_famille_id')->nullable();
                $table->unsignedBigInteger('article_marque_id')->nullable();
                $table->unsignedBigInteger('article_classe_id')->nullable();
                $table->unsignedBigInteger('article_nature_id')->nullable();
                $table->unsignedBigInteger('article_type_id')->nullable();
                $table->unsignedBigInteger('article_sous_famille_id')->nullable();
                $table->unsignedBigInteger('article_parfume_id')->nullable();
                $table->unsignedBigInteger('article_contenance_id')->nullable();
                $table->unsignedBigInteger('article_modele_id')->nullable();
                $table->string('article_matricule', 22)->nullable();
                $table->string('article_description', 100)->nullable();
                $table->string('article_product_number', 30)->nullable();
                $table->string('article_serial_number', 30)->nullable();
                $table->unsignedBigInteger('article_created_by')->nullable();
                $table->dateTime('article_created_date')->nullable();
                $table->unsignedBigInteger('article_updated_by')->nullable();
                $table->dateTime('article_updated_date')->nullable();
                $table->string('ean13', 15)->nullable();
                $table->string('barcode', 30)->nullable();
                $table->string('article_qr_code', 30)->nullable();
                $table->double('quantite_stock')->nullable();
                $table->double('article_quantite_optimale')->nullable();
                $table->double('article_quantite_theorique')->nullable();
                $table->double('quantite_min')->nullable();
                $table->unsignedBigInteger('article_project_id')->nullable();
                $table->double('article_project_modele_quantite')->nullable();
                $table->unsignedBigInteger('article_comptable_compte_id_achat')->nullable();
                $table->unsignedBigInteger('article_comptable_compte_id_vente')->nullable();
                $table->tinyInteger('taxe_tva_status_id')->unsigned()->nullable();
                $table->tinyInteger('article_online_show')->unsigned()->nullable();
                $table->string('article_online_reference', 22)->nullable();
                $table->text('article_online_description')->nullable();
                $table->unsignedBigInteger('article_online_page_id')->nullable();
                $table->unsignedBigInteger('article_online_famille_id')->nullable();
                $table->tinyInteger('active')->unsigned()->default(1);
                $table->tinyInteger('is_stock_managed')->unsigned()->default(1);
                $table->tinyInteger('archive')->unsigned()->default(0);
                $table->unsignedBigInteger('taxe_id')->nullable();
                $table->tinyInteger('article_manage_stock')->unsigned()->default(0);
                $table->string('article_default_photo', 256)->nullable();
                $table->timestamps();

                $table->primary('article_id');
                $table->index('entreprise_id');
                $table->index('article_famille_id');
                $table->index('article_marque_id');
                $table->index('taxe_tva_status_id');
                $table->index('article_online_show');
                $table->index('active');
                $table->index('article_created_date');
                $table->index('article_updated_date');
            });

        }
    }

    public function down(): void
    {
        Schema::dropIfExists('article');
    }
};
