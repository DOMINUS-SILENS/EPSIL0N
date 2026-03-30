<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2 - Step B: Legacy Data Migration
 * This migration copies and transforms data from legacy tables to canonical tables.
 * It preserves Primary Keys and normalizes timestamps and types.
 */
return new class extends Migration {
    public function up(): void
    {
        // 1. Enterprises
        if (Schema::hasTable('entreprise') && Schema::hasTable('entreprises')) {
            DB::statement("
                INSERT INTO entreprises (id, nom, raison_sociale, adresse, telephone, email, created_at, updated_at)
                SELECT entreprise_id, nom, raison_sociale, adresse, telephone, email, created_at, updated_at
                FROM entreprise
            ");
        }

        // 2. Articles
        if (Schema::hasTable('article') && Schema::hasTable('articles')) {
            DB::statement("
                INSERT INTO articles (
                    id, entreprise_id, designation, abreviation, ean13, barcode, sku, 
                    stock_quantity, min_quantity, optimal_quantity, price_ht, price_ttc, 
                    is_stock_managed, is_active, is_archived, created_at, updated_at, deleted_at
                )
                SELECT 
                    article_id, 
                    COALESCE(entreprise_id, 1), 
                    designation, 
                    article_abreviation, 
                    ean13, 
                    barcode, 
                    article_product_number,
                    CAST(COALESCE(quantite_stock, 0) AS DECIMAL(15,3)),
                    CAST(quantite_min AS DECIMAL(15,3)),
                    CAST(article_quantite_optimale AS DECIMAL(15,3)),
                    0, 0, -- Prices often in article_unite, set defaults
                    COALESCE(is_stock_managed, 1),
                    COALESCE(active, 1),
                    COALESCE(archive, 0),
                    COALESCE(article_created_date, created_at, NOW()),
                    COALESCE(article_updated_date, updated_at, NOW()),
                    NULL
                FROM article
            ");
        }

        // 3. Depots
        if (Schema::hasTable('depot') && Schema::hasTable('depots')) {
            DB::statement("
                INSERT INTO depots (id, entreprise_id, designation, code, address, is_active, created_at, updated_at)
                SELECT 
                    depot_id, 
                    COALESCE(entreprise_id, 1), 
                    designation, 
                    depot_ean13, -- Using ean13 as code if available
                    adresse, 
                    COALESCE(is_used, 1),
                    created_at, 
                    updated_at
                FROM depot
            ");
        }

        // 4. Article Units
        if (Schema::hasTable('article_unite') && Schema::hasTable('article_units')) {
            DB::statement("
                INSERT INTO article_units (
                    id, article_id, barcode, weight, volume, 
                    price_purchase, price_selling, is_default, is_active, created_at, updated_at
                )
                SELECT 
                    article_unite_id, 
                    article_id, 
                    barcode, 
                    article_poids, 
                    article_volume,
                    CAST(COALESCE(article_prix_achat_moyen, 0) AS DECIMAL(18,2)),
                    CAST(COALESCE(article_prix_vente, 0) AS DECIMAL(18,2)),
                    COALESCE(is_default, 0),
                    COALESCE(active, 1),
                    created_at, 
                    updated_at
                FROM article_unite
            ");
        }

        // 5. Stock Balances
        if (Schema::hasTable('balance_stock') && Schema::hasTable('stock_balances')) {
            DB::statement("
                INSERT INTO stock_balances (
                    id, entreprise_id, depot_id, article_id, available_quantity, reserved_quantity, updated_at
                )
                SELECT 
                    id, 
                    COALESCE(entreprise_id, 1), 
                    depot_id, 
                    article_id, 
                    CAST(COALESCE(quantite_physique, 0) AS DECIMAL(15,3)),
                    0, -- reserved_quantity defaults to 0
                    updated_at
                FROM balance_stock
            ");
        }

        // 6. Device Sync States
        if (Schema::hasTable('device_sync_state') && Schema::hasTable('device_sync_states')) {
            DB::statement("
                INSERT INTO device_sync_states (
                    id, entreprise_id, device_id, entity_type, last_sync_at, last_sync_sequence, created_at, updated_at
                )
                SELECT 
                    id, 
                    COALESCE(entreprise_id, 1), 
                    device_id, 
                    entity_type, 
                    last_sync_at, -- Assuming it's already a timestamp or compatible
                    COALESCE(sync_count, 0),
                    created_at, 
                    updated_at
                FROM device_sync_state
            ");
        }
    }

    public function down(): void
    {
        // Data migration down logic not supported for rebuild safety.
    }
};
