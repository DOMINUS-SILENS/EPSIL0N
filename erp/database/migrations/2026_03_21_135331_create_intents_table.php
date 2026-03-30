<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intents', function (Blueprint $table) {
            $table->id();
            $table->string('command_type');
            $table->string('verifier_class'); // FQCN of class implementing IntentVerifier
            $table->json('parameters')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intents');
    }
};
