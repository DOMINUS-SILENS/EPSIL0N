<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Entreprise;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PreSalesSeeder extends Seeder
{
    public function run(): void
    {
        $entrepriseIds = Entreprise::pluck('entreprise_id')->toArray();
        $userIds = User::pluck('id')->toArray();
        
        $firstEntrepriseId = $entrepriseIds[0] ?? 1;
        $managerId = $userIds[1] ?? 1; // Manager user
        $salesId = $userIds[2] ?? 1; // Sales rep

        // Campaigns
        $campaigns = [
            [
                'name' => 'Lancement Nouveau Produit - Smartphone S24',
                'description' => 'Campagne de lancement pour le nouveau Samsung Galaxy S24',
                'start_date' => now()->subDays(30),
                'end_date' => now()->addDays(30),
                'status' => 'active',
                'target_revenue' => 500000,
                'actual_revenue' => 125000,
                'target_leads' => 200,
                'actual_leads' => 85,
                'created_by' => $managerId,
                'created_at' => now()->subDays(35),
                'updated_at' => now(),
            ],
            [
                'name' => 'Promotion Été 2024',
                'description' => 'Offres spéciales été sur les produits électroniques',
                'start_date' => now()->addDays(15),
                'end_date' => now()->addDays(75),
                'status' => 'planned',
                'target_revenue' => 300000,
                'actual_revenue' => 0,
                'target_leads' => 150,
                'actual_leads' => 0,
                'created_by' => $managerId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Campagne B2B - Q1 2024',
                'description' => 'Campagne ciblée sur les entreprises',
                'start_date' => now()->subDays(60),
                'end_date' => now()->subDays(30),
                'status' => 'completed',
                'target_revenue' => 750000,
                'actual_revenue' => 820000,
                'target_leads' => 50,
                'actual_leads' => 62,
                'created_by' => $managerId,
                'created_at' => now()->subDays(65),
                'updated_at' => now()->subDays(30),
            ],
        ];

        foreach ($campaigns as $campaign) {
            $campaignId = DB::table('presales_campaigns')->insertGetId($campaign);
            
            // Add campaign tasks
            $tasks = [
                [
                    'campaign_id' => $campaignId,
                    'title' => 'Préparer matériel marketing',
                    'description' => 'Brochures, bannières, supports digitaux',
                    'assigned_to' => $salesId,
                    'due_date' => now()->subDays(25),
                    'status' => 'completed',
                    'created_at' => now()->subDays(30),
                    'updated_at' => now(),
                ],
                [
                    'campaign_id' => $campaignId,
                    'title' => 'Contacter prospects prioritaires',
                    'description' => 'Appeler les 20 premiers prospects',
                    'assigned_to' => $salesId,
                    'due_date' => now()->addDays(5),
                    'status' => 'in_progress',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ];
            
            foreach ($tasks as $task) {
                DB::table('presales_campaign_tasks')->insert($task);
            }
        }

        // Product Demos
        $demos = [
            [
                'customer_name' => 'Tech Solutions Maroc',
                'customer_email' => 'contact@techsolutions.ma',
                'product_id' => 1, // Samsung S24
                'scheduled_at' => now()->addDays(2),
                'status' => 'scheduled',
                'location' => 'Casablanca',
                'notes' => 'Présentation aux décideurs IT',
                'created_by' => $salesId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'customer_name' => 'Startup Hub Rabat',
                'customer_email' => 'hello@startuphub.ma',
                'product_id' => 2, // iPhone 15
                'scheduled_at' => now()->subDays(5),
                'completed_at' => now()->subDays(5),
                'status' => 'completed',
                'location' => 'Rabat',
                'notes' => 'Démo réussie - 10 unités commandées',
                'feedback' => 'Excellent accueil, intérêt fort pour commande groupée',
                'created_by' => $salesId,
                'created_at' => now()->subDays(10),
                'updated_at' => now()->subDays(5),
            ],
        ];

        foreach ($demos as $demo) {
            DB::table('presales_demos')->insert($demo);
        }

        // Sample Orders
        $samples = [
            [
                'customer_name' => 'Distribution Nord',
                'customer_email' => 'achats@distributionnord.ma',
                'product_id' => 1,
                'quantity' => 5,
                'status' => 'approved',
                'notes' => 'Échantillons pour test qualité',
                'requested_by' => $salesId,
                'approved_by' => $managerId,
                'approved_at' => now()->subDays(2),
                'created_at' => now()->subDays(5),
                'updated_at' => now(),
            ],
            [
                'customer_name' => 'Magasin ElectroPlus',
                'customer_email' => 'contact@electroplus.ma',
                'product_id' => 4,
                'quantity' => 2,
                'status' => 'shipped',
                'notes' => 'Échantillons pour display magasin',
                'requested_by' => $salesId,
                'approved_by' => $managerId,
                'approved_at' => now()->subDays(5),
                'shipped_at' => now()->subDays(3),
                'created_at' => now()->subDays(7),
                'updated_at' => now(),
            ],
            [
                'customer_name' => 'Nouveau Prospect',
                'customer_email' => 'test@exemple.ma',
                'product_id' => 2,
                'quantity' => 1,
                'status' => 'pending',
                'notes' => 'En attente de validation',
                'requested_by' => $salesId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($samples as $sample) {
            DB::table('presales_sample_orders')->insert($sample);
        }
    }
}
