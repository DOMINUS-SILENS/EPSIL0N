<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entreprise_id')->index();
            $table->unsignedBigInteger('vehicle_id')->index();
            $table->string('license_plate');
            $table->string('model')->nullable();
            $table->string('year')->nullable();
            $table->string('status')->default('active'); // active, maintenance, sold
            $table->unsignedBigInteger('last_event_id')->nullable();
            $table->timestamps();

            $table->unique(['entreprise_id', 'vehicle_id']);
            $table->unique(['entreprise_id', 'license_plate']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
