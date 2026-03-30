<?php

namespace Database\Seeders;

use App\Models\Entreprise;
use Illuminate\Database\Seeder;

class EntrepriseSeeder extends Seeder
{
    public function run(): void
    {
        $entreprises = [
            [
                'nom' => 'EPSILON Distribution',
                'raison_sociale' => 'EPSILON Distribution SARL',
                'adresse' => '123 Boulevard Mohamed VI, Casablanca',
                'telephone' => '+212 522 123 456',
                'email' => 'contact@epsilon-distribution.ma',
            ],
            [
                'nom' => 'Maroc Retail Group',
                'raison_sociale' => 'Maroc Retail Group SA',
                'adresse' => '45 Avenue Hassan II, Rabat',
                'telephone' => '+212 537 789 012',
                'email' => 'info@marocretail.ma',
            ],
            [
                'nom' => 'Atlas Commerce',
                'raison_sociale' => 'Atlas Commerce SARL',
                'adresse' => '78 Rue Moulay Ismail, Marrakech',
                'telephone' => '+212 524 345 678',
                'email' => 'contact@atlascommerce.ma',
            ],
            [
                'nom' => 'Sahara Logistics',
                'raison_sociale' => 'Sahara Logistics SARL',
                'adresse' => '12 Zone Industrielle, Agadir',
                'telephone' => '+212 528 901 234',
                'email' => 'logistics@sahara.ma',
            ],
        ];

        foreach ($entreprises as $entreprise) {
            Entreprise::create($entreprise);
        }
    }
}
