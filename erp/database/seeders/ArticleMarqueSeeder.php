<?php

namespace Database\Seeders;

use App\Models\ArticleMarque;
use Illuminate\Database\Seeder;

class ArticleMarqueSeeder extends Seeder
{
    public function run(): void
    {
        $marques = [
            ['article_marque_libelle' => 'Samsung', 'article_marque_code' => 'SAM'],
            ['article_marque_libelle' => 'Apple', 'article_marque_code' => 'APL'],
            ['article_marque_libelle' => 'Coca-Cola', 'article_marque_code' => 'CC'],
            ['article_marque_libelle' => 'Pepsi', 'article_marque_code' => 'PEP'],
            ['article_marque_libelle' => 'Nestlé', 'article_marque_code' => 'NES'],
            ['article_marque_libelle' => 'L\'Oréal', 'article_marque_code' => 'LOR'],
            ['article_marque_libelle' => 'Nike', 'article_marque_code' => 'NIK'],
            ['article_marque_libelle' => 'Adidas', 'article_marque_code' => 'ADI'],
            ['article_marque_libelle' => 'Sony', 'article_marque_code' => 'SNY'],
            ['article_marque_libelle' => 'LG', 'article_marque_code' => 'LG'],
            ['article_marque_libelle' => 'Canon', 'article_marque_code' => 'CAN'],
            ['article_marque_libelle' => 'HP', 'article_marque_code' => 'HP'],
            ['article_marque_libelle' => 'Generic', 'article_marque_code' => 'GEN'],
        ];

        foreach ($marques as $marque) {
            ArticleMarque::create($marque);
        }
    }
}
