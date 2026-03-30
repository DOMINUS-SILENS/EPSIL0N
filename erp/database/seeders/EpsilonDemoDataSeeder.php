<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * EpsilonDemoDataSeeder
 * Complete demo data for EPSILON ERP with realistic relationships
 */
class EpsilonDemoDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $now = Carbon::now();

            $this->seedUsers($now);
            $this->seedEnterprises($now);
            $this->seedDepots($now);
            $this->seedArticles($now);
            $this->seedCustomersAndOrders($now);
            $this->seedInventory($now);

            $this->command->info('✓ Demo data seeded successfully');
        });
    }

    private function seedUsers($now)
    {
        DB::table('users')->insertOrIgnore([
            ['name' => 'Admin EPSILON', 'email' => 'admin@epsilon-erp.local', 'email_verified_at' => $now, 'password' => bcrypt('Admin@Epsilon2026'), 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Yves Sales', 'email' => 'yves.sales@epsilon-erp.local', 'email_verified_at' => $now, 'password' => bcrypt('Sales@Epsilon2026'), 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Marie Stock', 'email' => 'marie.stock@epsilon-erp.local', 'email_verified_at' => $now, 'password' => bcrypt('Stock@Epsilon2026'), 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Pierre Delivery', 'email' => 'pierre.delivery@epsilon-erp.local', 'email_verified_at' => $now, 'password' => bcrypt('Delivery@Epsilon2026'), 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->command->line('  ✓ Users seeded');
    }

    private function seedEnterprises($now)
    {
        if (!DB::getSchemaBuilder()->hasTable('entreprise')) {
            return;
        }

        DB::table('entreprise')->insertOrIgnore([
            [
                'id' => (string) Str::uuid(),
                'siret' => 'SIRET-EPSILON-2026',
                'name' => 'EPSILON Distribution',
                'address' => '123 Rue de la Démo',
                'postal_code' => '75001',
                'city' => 'Paris',
                'country' => 'France',
                'phone' => '+33 1 23 45 67 89',
                'email' => 'contact@epsilon-erp.local',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $this->command->line('  ✓ Enterprises seeded');
    }

    private function seedDepots($now)
    {
        if (!DB::getSchemaBuilder()->hasTable('depot')) {
            return;
        }

        DB::table('depot')->insertOrIgnore([
            ['id' => (string) Str::uuid(), 'name' => 'Dépôt Paris Central', 'address' => '123 Rue de la Démo', 'city' => 'Paris', 'code' => 'PC', 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::uuid(), 'name' => 'Dépôt Lyon', 'address' => '456 Rue de Lyon', 'city' => 'Lyon', 'code' => 'LY', 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::uuid(), 'name' => 'Dépôt Marseille', 'address' => '789 Rue de Marseille', 'city' => 'Marseille', 'code' => 'MA', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->command->line('  ✓ Depots seeded');
    }

    private function seedArticles($now)
    {
        if (!DB::getSchemaBuilder()->hasTable('article')) {
            return;
        }

        $articles = [
            ['sku' => 'ART-001', 'name' => 'Produit Standard A', 'barcode' => 'EAN-001', 'poids' => 1.5, 'prix_unitaire' => 25.50],
            ['sku' => 'ART-002', 'name' => 'Produit Standard B', 'barcode' => 'EAN-002', 'poids' => 2.0, 'prix_unitaire' => 35.75],
            ['sku' => 'ART-003', 'name' => 'Produit Premium C', 'barcode' => 'EAN-003', 'poids' => 0.8, 'prix_unitaire' => 89.90],
            ['sku' => 'ART-004', 'name' => 'Produit Économique D', 'barcode' => 'EAN-004', 'poids' => 3.2, 'prix_unitaire' => 12.50],
        ];

        foreach ($articles as $article) {
            DB::table('article')->insertOrIgnore(array_merge($article, [
                'id' => (string) Str::uuid(),
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }

        $this->command->line('  ✓ Articles seeded');
    }

    private function seedCustomersAndOrders($now)
    {
        if (!DB::getSchemaBuilder()->hasTable('client')) {
            return;
        }

        $clients = [
            ['name' => 'Client Distribution XYZ', 'email' => 'contact@xyz-distrib.fr', 'phone' => '+33 2 11 11 11 11', 'city' => 'Nantes'],
            ['name' => 'Magasin ABC', 'email' => 'shop@abc-magasin.fr', 'phone' => '+33 3 22 22 22 22', 'city' => 'Bordeaux'],
            ['name' => 'Entreprise EFG', 'email' => 'purchase@efg-corp.fr', 'phone' => '+33 4 33 33 33 33', 'city' => 'Toulouse'],
        ];

        foreach ($clients as $client) {
            DB::table('client')->insertOrIgnore(array_merge($client, [
                'id' => (string) Str::uuid(),
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }

        $this->command->line('  ✓ Clients seeded');
    }

    private function seedInventory($now)
    {
        if (!DB::getSchemaBuilder()->hasTable('article_mouvement')) {
            return;
        }

        DB::table('article_mouvement')->insertOrIgnore([
            ['id' => (string) Str::uuid(), 'type' => 'entree', 'quantity' => 100, 'description' => 'Stock initial', 'created_at' => $now, 'updated_at' => $now],
            ['id' => (string) Str::uuid(), 'type' => 'entree', 'quantity' => 50, 'description' => 'Approvisionnement', 'created_at' => $now->subDay(), 'updated_at' => $now->subDay()],
            ['id' => (string) Str::uuid(), 'type' => 'sortie', 'quantity' => 15, 'description' => 'Vente commande #001', 'created_at' => $now->subHours(4), 'updated_at' => $now->subHours(4)],
        ]);

        $this->command->line('  ✓ Inventory movements seeded');
    }
}
