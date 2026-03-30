<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Depot;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TradeMarketingSeeder extends Seeder
{
    public function run(): void
    {
        $userIds = User::pluck('id')->toArray();
        $depotIds = Depot::pluck('depot_id')->toArray();
        
        $managerId = $userIds[1] ?? 1;
        $salesId = $userIds[2] ?? 1;

        // Planograms
        $planograms = [
            [
                'name' => 'Planogram Électronique Q2 2024',
                'store_type' => 'hypermarket',
                'category' => 'smartphones',
                'layout_data' => json_encode([
                    'shelves' => [
                        ['level' => 1, 'products' => ['Samsung S24', 'iPhone 15'], 'facings' => 5],
                        ['level' => 2, 'products' => ['Samsung A54', 'iPhone 14'], 'facings' => 4],
                    ],
                ]),
                'valid_from' => now()->subDays(30),
                'valid_until' => now()->addDays(60),
                'status' => 'active',
                'created_by' => $managerId,
                'created_at' => now()->subDays(35),
                'updated_at' => now(),
            ],
            [
                'name' => 'Planogram Boissons Été 2024',
                'store_type' => 'supermarket',
                'category' => 'soft_drinks',
                'layout_data' => json_encode([
                    'shelves' => [
                        ['level' => 1, 'products' => ['Coca-Cola 1.5L', 'Pepsi 1.5L'], 'facings' => 10],
                        ['level' => 2, 'products' => ['Coca-Cola 33cl', 'Pepsi 33cl'], 'facings' => 8],
                    ],
                ]),
                'valid_from' => now()->subDays(15),
                'valid_until' => now()->addDays(75),
                'status' => 'active',
                'created_by' => $managerId,
                'created_at' => now()->subDays(20),
                'updated_at' => now(),
            ],
            [
                'name' => 'Planogram Hygiène Premium',
                'store_type' => 'pharmacy',
                'category' => 'beauty',
                'layout_data' => json_encode([
                    'shelves' => [
                        ['level' => 1, 'products' => ["L'Oréal Shampooing", "L'Oréal Après-shampooing"], 'facings' => 6],
                    ],
                ]),
                'valid_from' => now()->addDays(10),
                'valid_until' => now()->addDays(100),
                'status' => 'draft',
                'created_by' => $managerId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($planograms as $planogram) {
            $planogramId = DB::table('trademkt_planograms')->insertGetId($planogram);

            // Create shelf positions for planogram
            $positions = [
                [
                    'planogram_id' => $planogramId,
                    'product_id' => 1, // Samsung S24
                    'shelf_level' => 1,
                    'position_x' => 1,
                    'position_y' => 1,
                    'facings' => 5,
                    'capacity' => 20,
                    'notes' => 'Position premium - eye level',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'planogram_id' => $planogramId,
                    'product_id' => 2, // iPhone 15
                    'shelf_level' => 1,
                    'position_x' => 6,
                    'position_y' => 1,
                    'facings' => 5,
                    'capacity' => 20,
                    'notes' => 'Position premium - eye level',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ];

            foreach ($positions as $position) {
                DB::table('trademkt_shelf_positions')->insert($position);
            }
        }

        // Execution Tasks
        $executionTasks = [
            [
                'title' => 'Installation planogram Électronique',
                'description' => 'Mettre en place le nouveau planogram smartphones',
                'store_name' => 'Marjane Casablanca',
                'store_address' => 'Route de Rabat, Casablanca',
                'latitude' => 33.5731,
                'longitude' => -7.5898,
                'type' => 'planogram_setup',
                'scheduled_date' => now()->addDays(2),
                'assigned_to' => $salesId,
                'status' => 'pending',
                'priority' => 'high',
                'created_by' => $managerId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Audit présence produits',
                'description' => 'Vérifier la présence de tous les produits selon planogram',
                'store_name' => 'Carrefour Maarif',
                'store_address' => 'Boulevard Zerktouni, Casablanca',
                'latitude' => 33.5850,
                'longitude' => -7.6250,
                'type' => 'shelf_audit',
                'scheduled_date' => now()->subDays(1),
                'assigned_to' => $salesId,
                'status' => 'completed',
                'completed_at' => now()->subDays(1),
                'priority' => 'medium',
                'created_by' => $managerId,
                'created_at' => now()->subDays(5),
                'updated_at' => now()->subDays(1),
            ],
            [
                'title' => 'Promotion placement',
                'description' => 'Installer présentoir promotionnel boissons',
                'store_name' => 'Acima Oasis',
                'store_address' => 'Quartier Oasis, Casablanca',
                'latitude' => 33.5400,
                'longitude' => -7.6400,
                'type' => 'promotion_placement',
                'scheduled_date' => now(),
                'assigned_to' => $salesId,
                'status' => 'in_progress',
                'started_at' => now()->subHours(2),
                'priority' => 'high',
                'created_by' => $managerId,
                'created_at' => now()->subDays(2),
                'updated_at' => now(),
            ],
        ];

        foreach ($executionTasks as $task) {
            $taskId = DB::table('trademkt_execution_tasks')->insertGetId($task);

            // Add checklist items for each task
            $checklist = [
                [
                    'task_id' => $taskId,
                    'description' => 'Vérifier matériel promotionnel',
                    'required' => true,
                    'completed' => $task['status'] === 'completed',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'task_id' => $taskId,
                    'description' => 'Prendre photo avant',
                    'required' => true,
                    'completed' => in_array($task['status'], ['completed', 'in_progress']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'task_id' => $taskId,
                    'description' => 'Prendre photo après',
                    'required' => true,
                    'completed' => $task['status'] === 'completed',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ];

            foreach ($checklist as $item) {
                DB::table('trademkt_checklist_items')->insert($item);
            }
        }

        // Compliance Metrics
        $complianceMetrics = [
            [
                'store_name' => 'Marjane Casablanca',
                'store_address' => 'Route de Rabat, Casablanca',
                'date' => now()->format('Y-m-d'),
                'total_tasks' => 10,
                'completed_tasks' => 8,
                'compliant_tasks' => 7,
                'compliance_rate' => 87.5,
                'issues_found' => 1,
                'issues_resolved' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'store_name' => 'Carrefour Maarif',
                'store_address' => 'Boulevard Zerktouni, Casablanca',
                'date' => now()->format('Y-m-d'),
                'total_tasks' => 12,
                'completed_tasks' => 12,
                'compliant_tasks' => 11,
                'compliance_rate' => 91.7,
                'issues_found' => 1,
                'issues_resolved' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'store_name' => 'Acima Oasis',
                'store_address' => 'Quartier Oasis, Casablanca',
                'date' => now()->format('Y-m-d'),
                'total_tasks' => 8,
                'completed_tasks' => 5,
                'compliant_tasks' => 4,
                'compliance_rate' => 50.0,
                'issues_found' => 2,
                'issues_resolved' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($complianceMetrics as $metric) {
            DB::table('trademkt_compliance_metrics')->insert($metric);
        }
    }
}
