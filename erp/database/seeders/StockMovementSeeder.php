<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Depot;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StockMovementSeeder extends Seeder
{
    public function run(): void
    {
        $articleIds = Article::pluck('article_id')->toArray();
        $depotIds = Depot::pluck('depot_id')->toArray();
        $userIds = User::pluck('id')->toArray();
        
        $mainDepotId = $depotIds[0] ?? 1;
        $frigoDepotId = $depotIds[1] ?? 1;
        $rabatDepotId = $depotIds[2] ?? 1;
        $userId = $userIds[0] ?? 1;

        $movements = [
            // Entry movements
            [
                'article_id' => $articleIds[0] ?? 1, // Samsung S24
                'depot_id' => $mainDepotId,
                'type' => 'entry',
                'quantity' => 100,
                'unit_cost' => 4500.00,
                'reference' => 'BL-2024-001',
                'supplier' => 'Samsung Maroc',
                'notes' => 'Réception commande initiale',
                'created_by' => $userId,
                'created_at' => now()->subDays(30),
            ],
            [
                'article_id' => $articleIds[0] ?? 1,
                'depot_id' => $mainDepotId,
                'type' => 'entry',
                'quantity' => 50,
                'unit_cost' => 4450.00,
                'reference' => 'BL-2024-015',
                'supplier' => 'Samsung Maroc',
                'notes' => 'Réapprovisionnement',
                'created_by' => $userId,
                'created_at' => now()->subDays(5),
            ],
            [
                'article_id' => $articleIds[2] ?? 3, // TV LG
                'depot_id' => $mainDepotId,
                'type' => 'entry',
                'quantity' => 30,
                'unit_cost' => 8500.00,
                'reference' => 'BL-2024-003',
                'supplier' => 'LG Electronics',
                'notes' => 'Réception TV',
                'created_by' => $userId,
                'created_at' => now()->subDays(25),
            ],
            [
                'article_id' => $articleIds[4] ?? 5, // Lait Nestlé
                'depot_id' => $frigoDepotId,
                'type' => 'entry',
                'quantity' => 500,
                'unit_cost' => 45.00,
                'reference' => 'BL-2024-008',
                'supplier' => 'Nestlé Maroc',
                'notes' => 'Produits frais',
                'created_by' => $userId,
                'created_at' => now()->subDays(10),
            ],
            // Exit movements (sales)
            [
                'article_id' => $articleIds[0] ?? 1,
                'depot_id' => $mainDepotId,
                'type' => 'exit',
                'quantity' => -25,
                'unit_cost' => 4500.00,
                'reference' => 'VTE-2024-042',
                'customer' => 'Client Premium A',
                'notes' => 'Vente en gros',
                'created_by' => $userId,
                'created_at' => now()->subDays(15),
            ],
            [
                'article_id' => $articleIds[0] ?? 1,
                'depot_id' => $mainDepotId,
                'type' => 'exit',
                'quantity' => -15,
                'unit_cost' => 4500.00,
                'reference' => 'VTE-2024-056',
                'customer' => 'Client Retail B',
                'notes' => 'Vente retail',
                'created_by' => $userId,
                'created_at' => now()->subDays(8),
            ],
            [
                'article_id' => $articleIds[2] ?? 3,
                'depot_id' => $mainDepotId,
                'type' => 'exit',
                'quantity' => -8,
                'unit_cost' => 8500.00,
                'reference' => 'VTE-2024-048',
                'customer' => 'Client Electronique C',
                'notes' => 'Vente TV',
                'created_by' => $userId,
                'created_at' => now()->subDays(12),
            ],
            // Transfers
            [
                'article_id' => $articleIds[0] ?? 1,
                'depot_id' => $mainDepotId,
                'type' => 'transfer_out',
                'quantity' => -20,
                'unit_cost' => 4500.00,
                'reference' => 'TRF-2024-005',
                'destination_depot_id' => $rabatDepotId,
                'notes' => 'Transfert vers Rabat',
                'created_by' => $userId,
                'created_at' => now()->subDays(7),
            ],
            [
                'article_id' => $articleIds[0] ?? 1,
                'depot_id' => $rabatDepotId,
                'type' => 'transfer_in',
                'quantity' => 20,
                'unit_cost' => 4500.00,
                'reference' => 'TRF-2024-005',
                'source_depot_id' => $mainDepotId,
                'notes' => 'Réception transfert de Casablanca',
                'created_by' => $userId,
                'created_at' => now()->subDays(6),
            ],
            // Adjustments
            [
                'article_id' => $articleIds[4] ?? 5,
                'depot_id' => $frigoDepotId,
                'type' => 'adjustment',
                'quantity' => -5,
                'unit_cost' => 45.00,
                'reference' => 'INV-2024-003',
                'notes' => 'Ajustement inventaire - produits endommagés',
                'created_by' => $userId,
                'created_at' => now()->subDays(3),
            ],
        ];

        foreach ($movements as $movement) {
            DB::table('article_movements')->insert($movement);
        }

        // Create stock reservations
        $reservations = [
            [
                'article_id' => $articleIds[0] ?? 1,
                'depot_id' => $mainDepotId,
                'quantity_reserved' => 10,
                'reference_type' => 'customer_order',
                'reference_id' => 1,
                'status' => 'active',
                'expires_at' => now()->addDays(7),
                'created_by' => $userId,
                'created_at' => now(),
            ],
            [
                'article_id' => $articleIds[2] ?? 3,
                'depot_id' => $mainDepotId,
                'quantity_reserved' => 3,
                'reference_type' => 'internal_transfer',
                'reference_id' => 2,
                'status' => 'active',
                'expires_at' => now()->addDays(3),
                'created_by' => $userId,
                'created_at' => now(),
            ],
        ];

        foreach ($reservations as $reservation) {
            DB::table('stock_reservations')->insert($reservation);
        }
    }
}
