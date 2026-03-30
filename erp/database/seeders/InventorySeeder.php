<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * InventorySeeder - Stock initialization and movements
 */
class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // Initialize stock per article/depot
        $stockData = [
            // Laptop: 50 in main storage, 10 in shipping
            ['article_id' => 1, 'unite_id' => 1, 'depot_id' => 2, 'quantite' => 50],
            ['article_id' => 1, 'unite_id' => 1, 'depot_id' => 4, 'quantite' => 10],

            // Mouse: 200 in storage, 50 in shipping
            ['article_id' => 2, 'unite_id' => 1, 'depot_id' => 2, 'quantite' => 200],
            ['article_id' => 2, 'unite_id' => 1, 'depot_id' => 4, 'quantite' => 50],

            // Keyboard: 120 in storage
            ['article_id' => 3, 'unite_id' => 1, 'depot_id' => 2, 'quantite' => 120],

            // Cheese: 80 in refrigerated zone
            ['article_id' => 4, 'unite_id' => 1, 'depot_id' => 3, 'quantite' => 80],

            // Milk: 150 in refrigerated zone
            ['article_id' => 5, 'unite_id' => 1, 'depot_id' => 3, 'quantite' => 150],
        ];

        foreach ($stockData as $stock) {
            DB::table('article_unite_depot')->insertOrIgnore($stock);
        }

        // Stock Movements (Inbound: purchase reception)
        $movements = [
            [
                'article_mouvement_id' => Str::uuid(),
                'entreprise_id' => 1,
                'article_id' => 1,
                'barcode' => 'LAPTOP-001',
                'article_mouvement_date' => $now->copy()->subDays(90),
                'article_production_date' => $now->copy()->subDays(100),
                'depot_id_source' => 1,
                'depot_id_destination' => 2,
                'article_mouvement_quantite' => 50,
                'article_mouvement_operation_type_id' => 1, // Reception
                'montant_achat_unitaire' => 800,
                'montant_achat_total' => 40000,
                'article_mouvement_created_by' => 1,
                'article_mouvement_created_date' => $now->copy()->subDays(90),
                'created_at' => $now->copy()->subDays(90),
                'updated_at' => $now->copy()->subDays(90),
            ],
            [
                'article_mouvement_id' => Str::uuid(),
                'entreprise_id' => 1,
                'article_id' => 2,
                'barcode' => 'MOUSE-001',
                'article_mouvement_date' => $now->copy()->subDays(85),
                'article_production_date' => $now->copy()->subDays(95),
                'depot_id_source' => 1,
                'depot_id_destination' => 2,
                'article_mouvement_quantite' => 200,
                'article_mouvement_operation_type_id' => 1,
                'montant_achat_unitaire' => 20,
                'montant_achat_total' => 4000,
                'article_mouvement_created_by' => 1,
                'article_mouvement_created_date' => $now->copy()->subDays(85),
                'created_at' => $now->copy()->subDays(85),
                'updated_at' => $now->copy()->subDays(85),
            ],
            [
                'article_mouvement_id' => Str::uuid(),
                'entreprise_id' => 1,
                'article_id' => 3,
                'barcode' => 'KEYBOARD-001',
                'article_mouvement_date' => $now->copy()->subDays(75),
                'article_production_date' => $now->copy()->subDays(85),
                'depot_id_source' => 1,
                'depot_id_destination' => 2,
                'article_mouvement_quantite' => 120,
                'article_mouvement_operation_type_id' => 1,
                'montant_achat_unitaire' => 70,
                'montant_achat_total' => 8400,
                'article_mouvement_created_by' => 1,
                'article_mouvement_created_date' => $now->copy()->subDays(75),
                'created_at' => $now->copy()->subDays(75),
                'updated_at' => $now->copy()->subDays(75),
            ],
            [
                'article_mouvement_id' => Str::uuid(),
                'entreprise_id' => 1,
                'article_id' => 4,
                'barcode' => 'CHEESE-001',
                'article_mouvement_date' => $now->copy()->subDays(14),
                'article_production_date' => $now->copy()->subDays(7),
                'article_expiration_date' => $now->copy()->addDays(14),
                'depot_id_source' => 1,
                'depot_id_destination' => 3,
                'article_mouvement_quantite' => 80,
                'article_mouvement_operation_type_id' => 1,
                'frigo' => true,
                'montant_achat_unitaire' => 4,
                'montant_achat_total' => 320,
                'article_mouvement_created_by' => 1,
                'article_mouvement_created_date' => $now->copy()->subDays(14),
                'created_at' => $now->copy()->subDays(14),
                'updated_at' => $now->copy()->subDays(14),
            ],
            [
                'article_mouvement_id' => Str::uuid(),
                'entreprise_id' => 1,
                'article_id' => 5,
                'barcode' => 'MILK-001',
                'article_mouvement_date' => $now->copy()->subDays(7),
                'article_production_date' => $now->copy()->subDays(3),
                'article_expiration_date' => $now->copy()->addDays(21),
                'depot_id_source' => 1,
                'depot_id_destination' => 3,
                'article_mouvement_quantite' => 150,
                'article_mouvement_operation_type_id' => 1,
                'frigo' => true,
                'montant_achat_unitaire' => 0.70,
                'montant_achat_total' => 105,
                'article_mouvement_created_by' => 1,
                'article_mouvement_created_date' => $now->copy()->subDays(7),
                'created_at' => $now->copy()->subDays(7),
                'updated_at' => $now->copy()->subDays(7),
            ],
            // Outbound: Sales/Shipments
            [
                'article_mouvement_id' => Str::uuid(),
                'entreprise_id' => 1,
                'article_id' => 1,
                'barcode' => 'LAPTOP-001',
                'article_mouvement_date' => $now->copy()->subDays(10),
                'depot_id_source' => 2,
                'depot_id_destination' => 4,
                'article_mouvement_quantite' => 2,
                'article_mouvement_operation_type_id' => 2, // Outbound/Sale
                'montant_vente_unitaire' => 1299,
                'montant_vente_total' => 2598,
                'article_mouvement_created_by' => 1,
                'article_mouvement_created_date' => $now->copy()->subDays(10),
                'created_at' => $now->copy()->subDays(10),
                'updated_at' => $now->copy()->subDays(10),
            ],
        ];

        foreach ($movements as $movement) {
            DB::table('article_mouvement')->insertOrIgnore($movement);
        }

        // Stock Moves (simplified tracking)
        $stockMoves = [
            [
                'product_id' => 1,
                'warehouse_id' => 2,
                'type' => 'in',
                'qty' => 50,
                'reference' => 'PO-001',
                'moved_at' => $now->copy()->subDays(90),
                'created_at' => $now->copy()->subDays(90),
                'updated_at' => $now->copy()->subDays(90),
            ],
            [
                'product_id' => 1,
                'warehouse_id' => 4,
                'type' => 'out',
                'qty' => 2,
                'reference' => 'SO-2026-0001',
                'moved_at' => $now->copy()->subDays(10),
                'created_at' => $now->copy()->subDays(10),
                'updated_at' => $now->copy()->subDays(10),
            ],
        ];

        foreach ($stockMoves as $move) {
            DB::table('stock_moves')->insertOrIgnore($move);
        }

        // Stock Balances
        $balances = [
            [
                'entreprise_id' => 1,
                'article_id' => 1,
                'quantity' => 60,
                'reserved_quantity' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'entreprise_id' => 1,
                'article_id' => 2,
                'quantity' => 250,
                'reserved_quantity' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'entreprise_id' => 1,
                'article_id' => 3,
                'quantity' => 120,
                'reserved_quantity' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'entreprise_id' => 1,
                'article_id' => 4,
                'quantity' => 80,
                'reserved_quantity' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'entreprise_id' => 1,
                'article_id' => 5,
                'quantity' => 150,
                'reserved_quantity' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($balances as $balance) {
            DB::table('stock_balances')->insertOrIgnore($balance);
        }
    }
}
