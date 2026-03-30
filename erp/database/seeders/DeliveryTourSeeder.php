<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Depot;
use App\Models\Entreprise;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DeliveryTourSeeder extends Seeder
{
    public function run(): void
    {
        $entrepriseIds = Entreprise::pluck('entreprise_id')->toArray();
        $userIds = User::pluck('id')->toArray();
        $depotIds = Depot::pluck('depot_id')->toArray();
        
        $firstEntrepriseId = $entrepriseIds[0] ?? 1;
        $driverId = $userIds[4] ?? 1; // Delivery driver
        $warehouseId = $depotIds[0] ?? 1; // Main warehouse

        // Create tours
        $tours = [
            [
                'reference' => 'TOUR-' . now()->format('Ymd') . '-001',
                'date' => now()->format('Y-m-d'),
                'status' => 'planned',
                'driver_id' => $driverId,
                'vehicle_id' => null,
                'depot_id' => $warehouseId,
                'start_time' => null,
                'end_time' => null,
                'total_stops' => 3,
                'completed_stops' => 0,
                'total_distance_km' => 45.5,
                'estimated_duration_min' => 180,
                'notes' => 'Livraison zone Casablanca centre',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'reference' => 'TOUR-' . now()->format('Ymd') . '-002',
                'date' => now()->format('Y-m-d'),
                'status' => 'in_progress',
                'driver_id' => $driverId,
                'vehicle_id' => null,
                'depot_id' => $warehouseId,
                'start_time' => now()->subHours(2)->format('H:i:s'),
                'end_time' => null,
                'total_stops' => 4,
                'completed_stops' => 2,
                'total_distance_km' => 62.0,
                'estimated_duration_min' => 240,
                'notes' => 'Livraison zone Industrielle',
                'created_at' => now()->subHours(3),
                'updated_at' => now(),
            ],
            [
                'reference' => 'TOUR-' . now()->subDays(1)->format('Ymd') . '-001',
                'date' => now()->subDays(1)->format('Y-m-d'),
                'status' => 'completed',
                'driver_id' => $driverId,
                'vehicle_id' => null,
                'depot_id' => $warehouseId,
                'start_time' => '08:00:00',
                'end_time' => '14:30:00',
                'total_stops' => 5,
                'completed_stops' => 5,
                'total_distance_km' => 78.3,
                'estimated_duration_min' => 300,
                'actual_duration_min' => 390,
                'notes' => 'Tournée terminée avec succès',
                'created_at' => now()->subDays(1),
                'updated_at' => now()->subDays(1),
            ],
        ];

        foreach ($tours as $tour) {
            $tourId = DB::table('delivery_tours')->insertGetId($tour);
            
            // Create stops for each tour
            if ($tour['reference'] === 'TOUR-' . now()->format('Ymd') . '-001') {
                $this->createStops($tourId, [
                    ['customer_name' => 'Client A - Casablanca', 'address' => '123 Boulevard Zerktouni, Casablanca', 'sequence' => 1, 'status' => 'pending'],
                    ['customer_name' => 'Client B - Maârif', 'address' => '45 Rue Ibn Sina, Casablanca', 'sequence' => 2, 'status' => 'pending'],
                    ['customer_name' => 'Client C - Gauthier', 'address' => '78 Rue Jean Jaurès, Casablanca', 'sequence' => 3, 'status' => 'pending'],
                ]);
            } elseif ($tour['reference'] === 'TOUR-' . now()->format('Ymd') . '-002') {
                $this->createStops($tourId, [
                    ['customer_name' => 'Client D - Sidi Maarouf', 'address' => 'Zone Industrielle Sidi Maarouf', 'sequence' => 1, 'status' => 'completed', 'actual_arrival' => now()->subHours(1)->format('H:i:s')],
                    ['customer_name' => 'Client E - Oasis', 'address' => '12 Rue de l\'Oasis, Casablanca', 'sequence' => 2, 'status' => 'completed', 'actual_arrival' => now()->subMinutes(30)->format('H:i:s')],
                    ['customer_name' => 'Client F - Aïn Sebaâ', 'address' => '45 Boulevard d\'Anfa, Casablanca', 'sequence' => 3, 'status' => 'in_progress'],
                    ['customer_name' => 'Client G - Hay Hassani', 'address' => '78 Avenue des FAR, Casablanca', 'sequence' => 4, 'status' => 'pending'],
                ]);
            }
        }
    }

    private function createStops(int $tourId, array $stops): void
    {
        foreach ($stops as $stop) {
            DB::table('delivery_stops')->insert([
                'tour_id' => $tourId,
                'customer_name' => $stop['customer_name'],
                'address' => $stop['address'],
                'latitude' => 33.5731 + (rand(-100, 100) / 1000),
                'longitude' => -7.5898 + (rand(-100, 100) / 1000),
                'sequence' => $stop['sequence'],
                'status' => $stop['status'],
                'estimated_arrival' => now()->addMinutes($stop['sequence'] * 45)->format('H:i:s'),
                'actual_arrival' => $stop['actual_arrival'] ?? null,
                'delivery_notes' => null,
                'signature' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
