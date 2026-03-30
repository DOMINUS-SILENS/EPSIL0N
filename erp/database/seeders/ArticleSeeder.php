<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Entreprise;
use App\Models\ArticleFamille;
use App\Models\ArticleMarque;
use Illuminate\Database\Seeder;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        $entrepriseIds = Entreprise::pluck('entreprise_id')->toArray();
        $familleIds = ArticleFamille::pluck('article_famille_id')->toArray();
        $marqueIds = ArticleMarque::pluck('article_marque_id')->toArray();

        $firstEntrepriseId = $entrepriseIds[0] ?? 1;
        $elecFamilleId = $familleIds[0] ?? 1;
        $alimFamilleId = $familleIds[1] ?? 1;
        $boissonFamilleId = $familleIds[2] ?? 1;
        $hygieneFamilleId = $familleIds[3] ?? 1;

        $articles = [
            // Électronique
            [
                'entreprise_id' => $firstEntrepriseId,
                'designation' => 'Smartphone Samsung Galaxy S24',
                'article_abreviation' => 'SAMS24',
                'article_famille_id' => $elecFamilleId,
                'article_marque_id' => $marqueIds[0] ?? null, // Samsung
                'article_description' => 'Smartphone haut de gamme avec écran AMOLED 6.2"',
                'ean13' => '8806095012345',
                'quantite_stock' => 150,
                'quantite_min' => 20,
                'article_quantite_optimale' => 100,
                'active' => true,
                'is_stock_managed' => true,
                'archive' => false,
            ],
            [
                'entreprise_id' => $firstEntrepriseId,
                'designation' => 'iPhone 15 Pro',
                'article_abreviation' => 'IP15P',
                'article_famille_id' => $elecFamilleId,
                'article_marque_id' => $marqueIds[1] ?? null, // Apple
                'article_description' => 'Smartphone Apple avec puce A17 Pro',
                'ean13' => '1942534012345',
                'quantite_stock' => 80,
                'quantite_min' => 15,
                'article_quantite_optimale' => 60,
                'active' => true,
                'is_stock_managed' => true,
                'archive' => false,
            ],
            [
                'entreprise_id' => $firstEntrepriseId,
                'designation' => 'TV LG OLED 55"',
                'article_abreviation' => 'LGOLED55',
                'article_famille_id' => $elecFamilleId,
                'article_marque_id' => $marqueIds[9] ?? null, // LG
                'article_description' => 'Téléviseur OLED 4K Smart TV',
                'ean13' => '8806095123456',
                'quantite_stock' => 45,
                'quantite_min' => 10,
                'article_quantite_optimale' => 30,
                'active' => true,
                'is_stock_managed' => true,
                'archive' => false,
            ],
            [
                'entreprise_id' => $firstEntrepriseId,
                'designation' => 'Casque Sony WH-1000XM5',
                'article_abreviation' => 'SONYWH5',
                'article_famille_id' => $elecFamilleId,
                'article_marque_id' => $marqueIds[8] ?? null, // Sony
                'article_description' => 'Casque sans fil avec réduction de bruit active',
                'ean13' => '4548736123456',
                'quantite_stock' => 120,
                'quantite_min' => 25,
                'article_quantite_optimale' => 80,
                'active' => true,
                'is_stock_managed' => true,
                'archive' => false,
            ],
            // Alimentaire
            [
                'entreprise_id' => $firstEntrepriseId,
                'designation' => 'Lait Nestlé Nido 2.5kg',
                'article_abreviation' => 'NIDO2.5',
                'article_famille_id' => $alimFamilleId,
                'article_marque_id' => $marqueIds[4] ?? null, // Nestlé
                'article_description' => 'Lait en poudre pour toute la famille',
                'ean13' => '7613035123456',
                'quantite_stock' => 300,
                'quantite_min' => 50,
                'article_quantite_optimale' => 200,
                'active' => true,
                'is_stock_managed' => true,
                'archive' => false,
            ],
            [
                'entreprise_id' => $firstEntrepriseId,
                'designation' => 'Chocolat KitKat 4 Fingers',
                'article_abreviation' => 'KITKAT4F',
                'article_famille_id' => $alimFamilleId,
                'article_marque_id' => $marqueIds[4] ?? null, // Nestlé
                'article_description' => 'Barre de chocolat croustillante',
                'ean13' => '7613036234567',
                'quantite_stock' => 500,
                'quantite_min' => 100,
                'article_quantite_optimale' => 350,
                'active' => true,
                'is_stock_managed' => true,
                'archive' => false,
            ],
            // Boissons
            [
                'entreprise_id' => $firstEntrepriseId,
                'designation' => 'Coca-Cola 1.5L',
                'article_abreviation' => 'CC1.5L',
                'article_famille_id' => $boissonFamilleId,
                'article_marque_id' => $marqueIds[2] ?? null, // Coca-Cola
                'article_description' => 'Boisson gazeuse rafraîchissante',
                'ean13' => '5449000123456',
                'quantite_stock' => 600,
                'quantite_min' => 120,
                'article_quantite_optimale' => 400,
                'active' => true,
                'is_stock_managed' => true,
                'archive' => false,
            ],
            [
                'entreprise_id' => $firstEntrepriseId,
                'designation' => 'Pepsi Max 33cl',
                'article_abreviation' => 'PEPMAX33',
                'article_famille_id' => $boissonFamilleId,
                'article_marque_id' => $marqueIds[3] ?? null, // Pepsi
                'article_description' => 'Boisson gazeuse sans sucre',
                'ean13' => '4060800123456',
                'quantite_stock' => 450,
                'quantite_min' => 80,
                'article_quantite_optimale' => 300,
                'active' => true,
                'is_stock_managed' => true,
                'archive' => false,
            ],
            // Hygiène
            [
                'entreprise_id' => $firstEntrepriseId,
                'designation' => 'Shampooing L\'Oréal Elseve',
                'article_abreviation' => 'LORELSV',
                'article_famille_id' => $hygieneFamilleId,
                'article_marque_id' => $marqueIds[5] ?? null, // L'Oréal
                'article_description' => 'Shampooing nourrissant pour cheveux secs',
                'ean13' => '3600520123456',
                'quantite_stock' => 250,
                'quantite_min' => 40,
                'article_quantite_optimale' => 180,
                'active' => true,
                'is_stock_managed' => true,
                'archive' => false,
            ],
            [
                'entreprise_id' => $firstEntrepriseId,
                'designation' => 'Déodorant Axe Ice Chill',
                'article_abreviation' => 'AXEICE',
                'article_famille_id' => $hygieneFamilleId,
                'article_marque_id' => $marqueIds[12] ?? null, // Generic
                'article_description' => 'Déodorant spray 48h de protection',
                'ean13' => '8722700123456',
                'quantite_stock' => 350,
                'quantite_min' => 60,
                'article_quantite_optimale' => 250,
                'active' => true,
                'is_stock_managed' => true,
                'archive' => false,
            ],
        ];

        foreach ($articles as $article) {
            Article::create($article);
        }
    }
}
