<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2 - Step A: Canonical Schema Creation (v2 HARDENED)
 * This migration creates the NEW canonical tables for the EPSILON ERP.
 * It follows the rules defined in the Canonical Schema Specification v1/v2.
 * NO DATA MIGRATION IS PERFORMED HERE.
 */
return new class extends Migration {
    public function up(): void
    {
        // 1. Enterprises (FK target)
        if (!Schema::hasTable('entreprises')) {
            Schema::create('entreprises', function (Blueprint $table) {
                $table->id();
                $table->string('nom')->nullable();
                $table->string('raison_sociale')->nullable();
                $table->string('adresse')->nullable();
                $table->string('telephone')->nullable();
                $table->string('email')->nullable();
                $table->timestamps();
            });
        }

        // 2. Articles
        if (!Schema::hasTable('articles')) {
            Schema::create('articles', function (Blueprint $table) {
                $table->id(); // Preserves legacy article_id
                $table->unsignedBigInteger('entreprise_id')->index();
                $table->text('designation');
                $table->string('abreviation', 50)->nullable();
                $table->string('ean13', 32)->nullable();
                $table->string('barcode', 64)->nullable();
                $table->string('sku', 64)->nullable();
                $table->decimal('stock_quantity', 15, 3)->default(0);
                $table->decimal('min_quantity', 15, 3)->nullable();
                $table->decimal('optimal_quantity', 15, 3)->nullable();
                $table->decimal('price_purchase', 18, 2)->default(0);
                $table->decimal('price_selling', 18, 2)->default(0);
                $table->boolean('is_stock_managed')->default(true);
                $table->boolean('is_active')->default(true);
                $table->boolean('is_archived')->default(false);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['entreprise_id', 'ean13']);
                $table->index(['entreprise_id', 'barcode']);
            });
        }

        // 3. Depots
        if (!Schema::hasTable('depots')) {
            Schema::create('depots', function (Blueprint $table) {
                $table->id(); // Preserves legacy depot_id
                $table->unsignedBigInteger('entreprise_id')->index();
                $table->string('designation', 255);
                $table->string('code', 50)->nullable();
                $table->text('address')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 4. Stock Balances
        if (!Schema::hasTable('stock_balances')) {
            Schema::create('stock_balances', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('entreprise_id')->index();
                $table->unsignedBigInteger('depot_id');
                $table->unsignedBigInteger('article_id');
                $table->decimal('available_quantity', 15, 3)->default(0);
                $table->decimal('reserved_quantity', 15, 3)->default(0);
                $table->timestamps();

                $table->unique(['entreprise_id', 'depot_id', 'article_id'], 'idx_stock_balance_unique');
                $table->index(['entreprise_id', 'depot_id', 'available_quantity'], 'idx_stock_available');
            });
        }

        // 5. Canonical Customers
        if (!Schema::hasTable('canonical_customers')) {
            Schema::create('canonical_customers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('entreprise_id')->index();
                $table->string('name', 255);
                $table->string('phone', 30)->nullable();
                $table->string('email', 100)->lowercase()->nullable();
                $table->decimal('credit_limit', 18, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['entreprise_id', 'name']);
            });
        }

        // 6. Canonical Orders (UUID strategy preserved)
        if (!Schema::hasTable('canonical_orders')) {
            Schema::create('canonical_orders', function (Blueprint $table) {
                $table->uuid('id')->primary(); // PRESERVED UUID STRATEGY
                $table->unsignedBigInteger('entreprise_id')->index();
                $table->unsignedBigInteger('customer_id');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->string('status', 50)->default('draft');
                $table->decimal('subtotal_amount', 18, 2)->default(0);
                $table->decimal('discount_amount', 18, 2)->default(0);
                $table->decimal('tax_amount', 18, 2)->default(0);
                $table->decimal('total_amount', 18, 2)->default(0);
                $table->decimal('balance_due', 18, 2)->default(0);
                $table->timestamp('ordered_at')->nullable();
                $table->string('client_mutation_id', 128)->nullable();
                $table->timestamps();

                $table->index(['entreprise_id', 'status']);
                $table->index(['customer_id', 'status']);
            });
        }

        // 7. Canonical Order Lines
        if (!Schema::hasTable('canonical_order_lines')) {
            Schema::create('canonical_order_lines', function (Blueprint $table) {
                $table->id();
                $table->uuid('order_id')->index();
                $table->unsignedBigInteger('article_id')->index();
                $table->decimal('quantity', 15, 3)->default(0.000);
                $table->decimal('unit_price', 18, 2)->default(0.00);
                $table->decimal('line_total', 18, 2)->default(0.00);
                $table->timestamps();
            });
        }

        // 8. Device Sync States (DATETIME fix)
        if (!Schema::hasTable('device_sync_states')) {
            Schema::create('device_sync_states', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('entreprise_id')->index();
                $table->string('device_id', 128);
                $table->string('entity_type', 100);
                $table->datetime('last_sync_at')->nullable(); // HARDENED DATETIME
                $table->bigInteger('last_sync_sequence')->unsigned()->default(0);
                $table->timestamps();

                $table->unique(['entreprise_id', 'device_id', 'entity_type'], 'idx_device_sync_state_unique');
            });
        }

        // 9. Article Units
        if (!Schema::hasTable('article_units')) {
            Schema::create('article_units', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('article_id')->index();
                $table->string('barcode', 64)->nullable();
                $table->decimal('weight', 15, 3)->nullable();
                $table->decimal('volume', 15, 3)->nullable();
                $table->decimal('price_purchase', 18, 2)->default(0);
                $table->decimal('price_selling', 18, 2)->default(0);
                $table->boolean('is_default')->default(false);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // NOOP for safety.
    }
};
