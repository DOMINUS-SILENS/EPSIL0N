<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('balance_stock')) {
            Schema::create('balance_stock', function (Blueprint $table) {
                $table->unsignedBigInteger('article_id');
                $table->date('date_day');
                $table->double('quantite_new')->nullable();
                $table->double('quantite_old')->nullable();
                $table->double('quantite_entre')->nullable();
                $table->double('quantite_retour')->nullable();
                $table->double('quantite_sortie')->nullable();
                $table->double('sorties')->default(0);
                $table->double('quantite_physique')->nullable();
                $table->double('quantite_theorique')->nullable();
                $table->double('ecart_jour')->default(0);
                $table->timestamps();

                $table->primary(['article_id', 'date_day']);
            });

            // No foreign key – we'll rely on application logic
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('balance_stock');
    }
};
