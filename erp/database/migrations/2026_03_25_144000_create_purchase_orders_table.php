<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entreprise_id')->index();
            $table->unsignedBigInteger('purchase_order_id')->index();
            $table->unsignedBigInteger('supplier_id')->index();
            $table->date('order_date');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('status')->default('draft'); // draft, ordered, received, cancelled
            $table->unsignedBigInteger('last_event_id')->nullable();
            $table->timestamps();

            $table->unique(['entreprise_id', 'purchase_order_id']);
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entreprise_id');
            $table->unsignedBigInteger('purchase_order_id');
            $table->unsignedBigInteger('article_id');
            $table->decimal('quantity', 15, 4);
            $table->decimal('unit_price', 15, 2);
            $table->unsignedBigInteger('last_event_id')->nullable();
            $table->timestamps();

            $table->index(['entreprise_id', 'purchase_order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
    }
};
