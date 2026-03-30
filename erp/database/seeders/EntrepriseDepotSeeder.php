<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * EntrepriseDepotSeeder - Companies and warehouse hierarchy
 */
class EntrepriseDepotSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // Create Enterprises
        $enterprises = [
            [
                'entreprise_id' => 1,
                'nom' => 'EPSILON SARL',
                'raison_sociale' => 'EPSILON Systems & Logistics',
                'adresse' => '123 Rue de la Tech, 75001 Paris',
                'telephone' => '+33 1 23 45 67 89',
                'email' => 'contact@epsilon-erp.local',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($enterprises as $enterprise) {
            DB::table('entreprise')->insertOrIgnore($enterprise);
        }

        // Create Depots (Warehouses) - Hierarchical structure
        $depots = [
            // Main distribution center (root)
            [
                'depot_id' => 1,
                'entreprise_id' => 1,
                'designation' => 'Centre de Distribution Principal - Paris',
                'depot_parent_id' => null,
                'emplacement_text' => 'Paris (France)',
                'code_barre' => 'DEPOT-PARIS-001',
                'ean13' => '1234567890001',
                'surface' => 5000,
                'volume' => 25000,
                'poid_max' => 50000,
                'frigo' => false,
                'gardé' => true,
                'blindé' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            // Sub-depot: Product Storage
            [
                'depot_id' => 2,
                'entreprise_id' => 1,
                'designation' => 'Zone Stockage Produits - Niveau 1',
                'depot_parent_id' => 1,
                'emplacement_text' => 'Paris (France) - Niveau 1',
                'code_barre' => 'DEPOT-PARIS-002',
                'ean13' => '1234567890002',
                'surface' => 2000,
                'volume' => 12000,
                'poid_max' => 30000,
                'frigo' => false,
                'gardé' => true,
                'blindé' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            // Sub-depot: Refrigerated Storage
            [
                'depot_id' => 3,
                'entreprise_id' => 1,
                'designation' => 'Zone Réfrigérée - Produits Frais',
                'depot_parent_id' => 1,
                'emplacement_text' => 'Paris (France) - Réfrigération',
                'code_barre' => 'DEPOT-PARIS-003',
                'ean13' => '1234567890003',
                'surface' => 500,
                'volume' => 2000,
                'poid_max' => 5000,
                'frigo' => true,
                'temperature_min' => 0,
                'temperature_max' => 4,
                'gardé' => true,
                'blindé' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            // Sub-depot: Shipping Area
            [
                'depot_id' => 4,
                'entreprise_id' => 1,
                'designation' => 'Zone Expédition',
                'depot_parent_id' => 1,
                'emplacement_text' => 'Paris (France) - Expédition',
                'code_barre' => 'DEPOT-PARIS-004',
                'ean13' => '1234567890004',
                'surface' => 800,
                'volume' => 4000,
                'poid_max' => 15000,
                'frigo' => false,
                'gardé' => true,
                'blindé' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            // Regional depot: Lyon
            [
                'depot_id' => 5,
                'entreprise_id' => 1,
                'designation' => 'Dépôt Régional - Lyon',
                'depot_parent_id' => null,
                'emplacement_text' => 'Lyon (France)',
                'code_barre' => 'DEPOT-LYON-001',
                'ean13' => '1234567890005',
                'surface' => 3000,
                'volume' => 15000,
                'poid_max' => 25000,
                'frigo' => false,
                'gardé' => true,
                'blindé' => false,
                'created_at' => $now->copy()->subDays(30),
                'updated_at' => $now->copy()->subDays(30),
            ],
        ];

        foreach ($depots as $depot) {
            DB::table('depot')->insertOrIgnore($depot);
        }
    }
}
