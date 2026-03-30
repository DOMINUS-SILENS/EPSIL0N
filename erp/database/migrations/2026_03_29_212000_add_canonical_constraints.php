<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2 - Step C: Add Canonical Constraints
 * This migration adds Foreign Keys, Unique Constraints, and Indexes 
 * to the canonical tables AFTER data has been migrated.
 */
return new class extends Migration {
    public function up(): void
    {
        // 1. Articles
        Schema::table('articles', function (Blueprint $table) {
            $table->foreign('entreprise_id')->references('id')->on('entreprises')->onDelete('cascade');
        });

        // 2. Depots
        Schema::table('depots', function (Blueprint $table) {
            $table->foreign('entreprise_id')->references('id')->on('entreprises')->onDelete('cascade');
        });

        // 3. Article Units
        Schema::table('article_units', function (Blueprint $table) {
            $table->foreign('article_id')->references('id')->on('articles')->onDelete('cascade');
        });

        // 4. Stock Balances
        Schema::table('stock_balances', function (Blueprint $table) {
            $table->foreign('entreprise_id')->references('id')->on('entreprises')->onDelete('cascade');
            $table->foreign('depot_id')->references('id')->on('depots')->onDelete('cascade');
            $table->foreign('article_id')->references('id')->on('articles')->onDelete('cascade');
        });

        // 5. Stock Movements
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreign('entreprise_id')->references('id')->on('entreprises')->onDelete('cascade');
            $table->foreign('article_id')->references('id')->on('articles')->onDelete('cascade');
            $table->foreign('depot_id_source')->references('id')->on('depots')->onDelete('set null');
            $table->foreign('depot_id_destination')->references('id')->on('depots')->onDelete('set null');
        });

        // 6. Device Sync States
        Schema::table('device_sync_states', function (Blueprint $table) {
            $table->foreign('entreprise_id')->references('id')->on('entreprises')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        // Dropping constraints is handled via rolling back migrations if really needed.
    }
};
