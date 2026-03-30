<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_balances', function (Blueprint $table) {
            $table->unsignedBigInteger('entreprise_id'); // Paritition key
            $table->unsignedBigInteger('article_id');
            $table->decimal('quantity', 15, 4)->default(0);
            $table->decimal('reserved_quantity', 15, 4)->default(0);
            $table->unsignedBigInteger('last_event_id')->nullable();
            
            $table->primary(['entreprise_id', 'article_id']);
            $table->index(['entreprise_id', 'last_event_id']);
            
            $table->timestamps();
        });

        try {
            DB::statement('ALTER TABLE stock_balances PARTITION BY HASH(entreprise_id) PARTITIONS 16');
        } catch (\Exception $e) {
            // Ignore if already grouped
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_balances');
    }
};
