<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_reservations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entreprise_id');
            $table->string('name')->nullable();
            $table->decimal('credit_limit', 18, 4)->default(0);
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('order_id');
            $table->decimal('amount', 18, 4);
            $table->timestamp('expires_at');
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'expired'])->default('pending');
            $table->unsignedBigInteger('sequence');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['customer_id', 'status']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_reservations');
    }
};
