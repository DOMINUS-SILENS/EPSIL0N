<?php

namespace Database\Seeders;

use App\Models\ArticleFamille;
use Illuminate\Database\Seeder;

class ArticleFamilleSeeder extends Seeder
{
    public function run(): void
    {
        $familles = [
            ['article_famille_libelle' => 'Électronique', 'article_famille_code' => 'ELEC'],
            ['article_famille_libelle' => 'Alimentaire', 'article_famille_code' => 'ALIM'],
            ['article_famille_libelle' => 'Boissons', 'article_famille_code' => 'BOIS'],
            ['article_famille_libelle' => 'Hygiène & Beauté', 'article_famille_code' => 'HYG'],
            ['article_famille_libelle' => 'Textile', 'article_famille_code' => 'TEXT'],
            ['article_famille_libelle' => 'Maison & Jardin', 'article_famille_code' => 'MAIS'],
            ['article_famille_libelle' => 'Sport & Loisirs', 'article_famille_code' => 'SPORT'],
            ['article_famille_libelle' => 'Papeterie', 'article_famille_code' => 'PAP'],
        ];

        foreach ($familles as $famille) {
            ArticleFamille::create($famille);
        }
    }
}
