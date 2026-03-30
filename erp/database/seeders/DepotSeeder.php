<?php

namespace Database\Seeders;

use App\Models\Depot;
use App\Models\Entreprise;
use Illuminate\Database\Seeder;

class DepotSeeder extends Seeder
{
    public function run(): void
    {
        $entrepriseIds = Entreprise::pluck('entreprise_id')->toArray();
        $firstEntrepriseId = $entrepriseIds[0] ?? 1;

        $depots = [
            [
                'entreprise_id' => $firstEntrepriseId,
                'designation' => 'Entrepôt Principal Casablanca',
                'depot_emplacement_text' => 'Zone Industrielle Sidi Maarouf',
                'depot_type' => 'entrepot',
                'adresse' => 'Zone Industrielle Sidi Maarouf, Casablanca',
                'latitude' => 33.5731,
                'longitude' => -7.5898,
                'depot_surface' => 5000,
                'depot_volume' => 25000,
                'frigo' => false,
                'is_used' => true,
            ],
            [
                'entreprise_id' => $firstEntrepriseId,
                'designation' => 'Entrepôt Frigorifique',
                'depot_emplacement_text' => 'Zone Frigorifique Aïn Sebaâ',
                'depot_type' => 'frigo',
                'adresse' => 'Zone Frigorifique Aïn Sebaâ, Casablanca',
                'latitude' => 33.6065,
                'longitude' => -7.5625,
                'depot_surface' => 2000,
                'depot_volume' => 8000,
                'frigo' => true,
                'temperature_min' => -25,
                'temperature_max' => 4,
                'is_used' => true,
            ],
            [
                'entreprise_id' => $firstEntrepriseId,
                'designation' => 'Dépôt Rabat',
                'depot_emplacement_text' => 'Zone Industrielle Sidi Bernoussi',
                'depot_type' => 'depot',
                'adresse' => 'Zone Industrielle Sidi Bernoussi, Rabat',
                'latitude' => 34.0209,
                'longitude' => -6.8416,
                'depot_surface' => 1500,
                'depot_volume' => 6000,
                'frigo' => false,
                'is_used' => true,
            ],
            [
                'entreprise_id' => $entrepriseIds[1] ?? $firstEntrepriseId,
                'designation' => 'Entrepôt Marrakech',
                'depot_emplacement_text' => 'Zone Industrielle Sidi Ghanem',
                'depot_type' => 'entrepot',
                'adresse' => 'Zone Industrielle Sidi Ghanem, Marrakech',
                'latitude' => 31.6295,
                'longitude' => -7.9811,
                'depot_surface' => 3000,
                'depot_volume' => 12000,
                'frigo' => false,
                'is_used' => true,
            ],
            [
                'entreprise_id' => $entrepriseIds[2] ?? $firstEntrepriseId,
                'designation' => 'Dépôt Mobile Agadir',
                'depot_emplacement_text' => 'Camion Frigorifique',
                'depot_type' => 'mobile',
                'adresse' => 'Zone Industrielle, Agadir',
                'latitude' => 30.4278,
                'longitude' => -9.5981,
                'depot_surface' => 50,
                'depot_volume' => 80,
                'frigo' => true,
                'temperature_min' => -20,
                'temperature_max' => 4,
                'vehicule' => true,
                'is_used' => true,
            ],
        ];

        foreach ($depots as $depot) {
            Depot::create($depot);
        }
    }
}
