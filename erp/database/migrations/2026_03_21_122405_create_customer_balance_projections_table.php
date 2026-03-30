<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_balance_projections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('entreprise_id');
            $table->decimal('balance', 18, 4)->default(0);
            $table->unsignedBigInteger('last_sequence');
            $table->unsignedInteger('version')->default(1);
            $table->timestamp('computed_at')->useCurrent();
            $table->unique('customer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_balance_projections');
    }
};
