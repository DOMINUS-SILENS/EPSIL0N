<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entreprise_id')->index();
            $table->unsignedBigInteger('project_id')->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status')->default('planned'); // planned, active, completed, cancelled
            $table->unsignedBigInteger('last_event_id')->nullable();
            $table->timestamps();

            $table->unique(['entreprise_id', 'project_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
