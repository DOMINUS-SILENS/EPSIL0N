<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('depot')) {
            Schema::create('depot', function (Blueprint $table) {
                $table->id('depot_id');
                $table->unsignedBigInteger('entreprise_id');
                $table->string('designation', 256)->nullable();
                $table->unsignedBigInteger('depot_parent_id')->nullable();
                $table->integer('depot_parent_left')->nullable();
                $table->integer('depot_parent_right')->nullable();
                $table->unsignedBigInteger('depot_manager_id')->nullable();
                $table->unsignedBigInteger('depot_vendeur_id')->nullable();
                $table->string('depot_emplacement_text', 256)->nullable();
                $table->string('depot_ean13', 20)->nullable();
                $table->string('depot_code_barre', 20)->nullable();
                $table->string('depot_path', 256)->nullable();
                $table->unsignedBigInteger('depot_point_id')->nullable();
                $table->unsignedBigInteger('depot_owner_id')->nullable();
                $table->tinyInteger('depot_immobilier_usage_type')->unsigned()->nullable();
                $table->double('depot_surface')->nullable();
                $table->double('depot_volume')->nullable();
                $table->double('depot_poid_max')->nullable();
                $table->tinyInteger('is_used')->unsigned()->nullable();
                $table->double('depot_current_fill_percent')->nullable();
                $table->tinyInteger('depot_need_defrag')->unsigned()->nullable();
                $table->integer('depot_niveau_rangement')->nullable();
                $table->integer('depot_ordre_rangement')->nullable();
                $table->double('depot_distance_from_entree')->nullable();
                $table->double('depot_height_from_feet')->nullable();
                $table->tinyInteger('depot_is_departement')->unsigned()->nullable();
                $table->unsignedBigInteger('departement_id')->nullable();
                $table->unsignedBigInteger('depot_type')->nullable();
                $table->string('adresse', 256)->nullable();
                $table->integer('commune_id')->nullable();
                $table->integer('wilaya_id')->nullable();
                $table->double('longitude')->nullable();
                $table->double('latitude')->nullable();
                $table->tinyInteger('frigo')->unsigned()->nullable();
                $table->float('temperature_min')->nullable();
                $table->float('temperature_max')->nullable();
                $table->tinyInteger('depot_gardé')->unsigned()->nullable();
                $table->tinyInteger('depot_blindé')->unsigned()->nullable();
                $table->tinyInteger('depot_barreaudage')->unsigned()->nullable();
                $table->double('longueur')->nullable();
                $table->double('largeur')->nullable();
                $table->double('hauteur')->nullable();
                $table->tinyInteger('vehicule')->unsigned()->nullable();
                $table->unsignedBigInteger('vehicule_id')->nullable();
                $table->integer('ordre_passage')->nullable();
                $table->double('temps_accessibilite')->nullable();
                $table->double('cout_accessibilite')->nullable();
                $table->unsignedBigInteger('entrepot_id')->nullable();
                $table->timestamps();

                $table->primary('depot_id');
                $table->index('entreprise_id');
                $table->index('depot_parent_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('depot');
    }
};
