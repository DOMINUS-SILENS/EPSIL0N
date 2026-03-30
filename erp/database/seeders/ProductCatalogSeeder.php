<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * ProductCatalogSeeder - Product families, brands, and articles
 */
class ProductCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // Product Families (Hierarchical)
        $families = [
            [
                'article_famille_id' => 1,
                'entreprise_id' => 1,
                'article_famille_designation' => 'Électronique',
                'article_famille_parent_id' => null,
                'active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'article_famille_id' => 2,
                'entreprise_id' => 1,
                'article_famille_designation' => 'Informatique',
                'article_famille_parent_id' => 1,
                'active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'article_famille_id' => 3,
                'entreprise_id' => 1,
                'article_famille_designation' => 'Accessoires',
                'article_famille_parent_id' => 1,
                'active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'article_famille_id' => 4,
                'entreprise_id' => 1,
                'article_famille_designation' => 'Alimentaire',
                'article_famille_parent_id' => null,
                'active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'article_famille_id' => 5,
                'entreprise_id' => 1,
                'article_famille_designation' => 'Produits Frais',
                'article_famille_parent_id' => 4,
                'active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($families as $family) {
            DB::table('article_famille')->insertOrIgnore($family);
        }

        // Brands
        $brands = [
            [
                'article_marque_id' => 1,
                'entreprise_id' => 1,
                'article_marque_designation' => 'EPSILON Pro',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'article_marque_id' => 2,
                'entreprise_id' => 1,
                'article_marque_designation' => 'TechFlow',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'article_marque_id' => 3,
                'entreprise_id' => 1,
                'article_marque_designation' => 'FreshFood Co',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($brands as $brand) {
            DB::table('article_marque')->insertOrIgnore($brand);
        }

        // Articles (Products)
        $articles = [
            // Electronics
            [
                'article_id' => 1,
                'entreprise_id' => 1,
                'designation' => 'Laptop EPSILON Pro 15',
                'article_abreviation' => 'LPT-EPSI-15',
                'article_famille_id' => 2,
                'article_marque_id' => 1,
                'article_matricule' => 'MAT-LAPTOP-001',
                'ean13' => '1111111111111',
                'barcode' => 'LAPTOP-001',
                'quantite_stock' => 50,
                'quantite_min' => 10,
                'article_quantite_optimale' => 30,
                'active' => 1,
                'is_stock_managed' => 1,
                'article_manage_stock' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'article_id' => 2,
                'entreprise_id' => 1,
                'designation' => 'Souris Sans Fil EPSILON',
                'article_abreviation' => 'MOU-EPSI-WL',
                'article_famille_id' => 3,
                'article_marque_id' => 1,
                'article_matricule' => 'MAT-MOUSE-001',
                'ean13' => '2222222222222',
                'barcode' => 'MOUSE-001',
                'quantite_stock' => 200,
                'quantite_min' => 50,
                'article_quantite_optimale' => 100,
                'active' => 1,
                'is_stock_managed' => 1,
                'article_manage_stock' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'article_id' => 3,
                'entreprise_id' => 1,
                'designation' => 'Clavier Mécanique RGB',
                'article_abreviation' => 'CLV-MECH-RGB',
                'article_famille_id' => 3,
                'article_marque_id' => 2,
                'article_matricule' => 'MAT-KEYBOARD-001',
                'ean13' => '3333333333333',
                'barcode' => 'KEYBOARD-001',
                'quantite_stock' => 120,
                'quantite_min' => 30,
                'article_quantite_optimale' => 60,
                'active' => 1,
                'is_stock_managed' => 1,
                'article_manage_stock' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Food products
            [
                'article_id' => 4,
                'entreprise_id' => 1,
                'designation' => 'Fromage Frais Camembert',
                'article_abreviation' => 'FROMAGE-CAM',
                'article_famille_id' => 5,
                'article_marque_id' => 3,
                'article_matricule' => 'MAT-CHEESE-001',
                'ean13' => '4444444444444',
                'barcode' => 'CHEESE-001',
                'quantite_stock' => 80,
                'quantite_min' => 20,
                'article_quantite_optimale' => 40,
                'active' => 1,
                'is_stock_managed' => 1,
                'article_manage_stock' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'article_id' => 5,
                'entreprise_id' => 1,
                'designation' => 'Lait Entier 1L',
                'article_abreviation' => 'LAIT-1L',
                'article_famille_id' => 5,
                'article_marque_id' => 3,
                'article_matricule' => 'MAT-MILK-001',
                'ean13' => '5555555555555',
                'barcode' => 'MILK-001',
                'quantite_stock' => 150,
                'quantite_min' => 50,
                'article_quantite_optimale' => 100,
                'active' => 1,
                'is_stock_managed' => 1,
                'article_manage_stock' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($articles as $article) {
            DB::table('article')->insertOrIgnore($article);
        }

        // Article Units (SKUs)
        $units = [
            [
                'article_id' => 1,
                'barcode' => 'UNIT-LAPTOP-001',
                'article_poids' => 1800,
                'article_volume' => 5000,
                'article_prix_revient' => 600,
                'article_prix_achat_moyen' => 800,
                'article_prix_vente' => 1299,
                'article_unite_quantite' => 1,
                'active' => 1,
                'is_default' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'article_id' => 2,
                'barcode' => 'UNIT-MOUSE-001',
                'article_poids' => 100,
                'article_volume' => 500,
                'article_prix_revient' => 15,
                'article_prix_achat_moyen' => 20,
                'article_prix_vente' => 49.99,
                'article_unite_quantite' => 1,
                'active' => 1,
                'is_default' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'article_id' => 3,
                'barcode' => 'UNIT-KEYBOARD-001',
                'article_poids' => 950,
                'article_volume' => 2500,
                'article_prix_revient' => 50,
                'article_prix_achat_moyen' => 70,
                'article_prix_vente' => 149.99,
                'article_unite_quantite' => 1,
                'active' => 1,
                'is_default' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'article_id' => 4,
                'barcode' => 'UNIT-CHEESE-001',
                'article_poids' => 200,
                'article_volume' => 400,
                'article_prix_revient' => 3,
                'article_prix_achat_moyen' => 4,
                'article_prix_vente' => 7.99,
                'article_unite_quantite' => 1,
                'active' => 1,
                'is_default' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'article_id' => 5,
                'barcode' => 'UNIT-MILK-001',
                'article_poids' => 1000,
                'article_volume' => 1000,
                'article_prix_revient' => 0.50,
                'article_prix_achat_moyen' => 0.70,
                'article_prix_vente' => 1.49,
                'article_unite_quantite' => 1,
                'active' => 1,
                'is_default' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($units as $unit) {
            DB::table('article_unite')->insertOrIgnore($unit);
        }
    }
}
