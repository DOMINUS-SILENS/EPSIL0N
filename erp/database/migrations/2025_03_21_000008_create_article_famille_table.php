<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('article_famille')) {
            Schema::create('article_famille', function (Blueprint $table) {
                $table->id('article_famille_id');
                $table->unsignedBigInteger('entreprise_id')->nullable();
                $table->string('article_famille_designation', 256)->nullable();
                $table->unsignedBigInteger('article_famille_parent_id')->nullable();
                $table->integer('article_famille_parent_left')->nullable();
                $table->integer('article_famille_parent_right')->nullable();
                $table->tinyInteger('article_famille_online_show')->unsigned()->nullable();
                $table->text('article_famille_online_description')->nullable();
                $table->tinyInteger('active')->unsigned()->nullable();
                $table->string('famille_codification', 256)->nullable();
                $table->unsignedBigInteger('mouvement_type_groupe_id')->nullable();
                $table->double('ordre')->nullable();
                $table->integer('nature_id')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('article_famille');
    }
};
