<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('article_marque')) {
            Schema::create('article_marque', function (Blueprint $table) {
                $table->id('article_marque_id');
                $table->unsignedBigInteger('entreprise_id')->nullable();
                $table->string('article_marque_designation', 256)->nullable();
                $table->unsignedBigInteger('article_marque_created_by')->nullable();
                $table->dateTime('article_marque_created_date')->nullable();
                $table->unsignedBigInteger('article_famille')->nullable();
                $table->string('ressource_icon', 256)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('article_marque');
    }
};
