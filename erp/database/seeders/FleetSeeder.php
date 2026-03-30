<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FleetSeeder extends Seeder
{
    public function run(): void
    {
        $userIds = User::pluck('id')->toArray();
        $driverId = $userIds[4] ?? null; // Delivery driver

        $vehicles = [
            [
                'registration' => '12345-A-6',
                'type' => 'camion_frigorifique',
                'brand' => 'Mercedes',
                'model' => 'Actros 1842',
                'year' => 2022,
                'capacity_kg' => 12000,
                'volume_m3' => 45,
                'fuel_type' => 'diesel',
                'status' => 'active',
                'driver_id' => $driverId,
                'current_latitude' => 33.5731,
                'current_longitude' => -7.5898,
                'last_location_update' => now(),
                'mileage_km' => 45000,
                'next_maintenance_km' => 50000,
                'next_maintenance_date' => now()->addMonths(2),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'registration' => '23456-B-7',
                'type' => 'camion_bache',
                'brand' => 'Volvo',
                'model' => 'FH 500',
                'year' => 2021,
                'capacity_kg' => 18000,
                'volume_m3' => 60,
                'fuel_type' => 'diesel',
                'status' => 'active',
                'driver_id' => null,
                'current_latitude' => 33.6065,
                'current_longitude' => -7.5625,
                'last_location_update' => now()->subHours(1),
                'mileage_km' => 78000,
                'next_maintenance_km' => 80000,
                'next_maintenance_date' => now()->addMonths(1),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'registration' => '34567-C-8',
                'type' => 'fourgon',
                'brand' => 'Renault',
                'model' => 'Master',
                'year' => 2023,
                'capacity_kg' => 3500,
                'volume_m3' => 13,
                'fuel_type' => 'diesel',
                'status' => 'maintenance',
                'driver_id' => null,
                'current_latitude' => null,
                'current_longitude' => null,
                'last_location_update' => null,
                'mileage_km' => 12000,
                'next_maintenance_km' => 15000,
                'next_maintenance_date' => now()->addDays(7),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'registration' => '45678-D-9',
                'type' => 'camionnette_frigorifique',
                'brand' => 'Ford',
                'model' => 'Transit',
                'year' => 2022,
                'capacity_kg' => 1500,
                'volume_m3' => 8,
                'fuel_type' => 'diesel',
                'status' => 'active',
                'driver_id' => null,
                'current_latitude' => 34.0209,
                'current_longitude' => -6.8416,
                'last_location_update' => now()->subMinutes(15),
                'mileage_km' => 25000,
                'next_maintenance_km' => 30000,
                'next_maintenance_date' => now()->addMonths(3),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($vehicles as $vehicle) {
            $vehicleId = DB::table('fleet_vehicles')->insertGetId($vehicle);
            
            // Add maintenance logs for vehicles in maintenance
            if ($vehicle['status'] === 'maintenance') {
                DB::table('fleet_maintenance_logs')->insert([
                    'vehicle_id' => $vehicleId,
                    'type' => 'revision',
                    'description' => 'Révision annuelle et changement filtres',
                    'mileage_km' => $vehicle['mileage_km'],
                    'cost' => 15000.00,
                    'performed_at' => now(),
                    'performed_by' => 'Garage Central',
                    'next_due_km' => $vehicle['mileage_km'] + 3000,
                    'status' => 'in_progress',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Add vehicle location history
        DB::table('fleet_vehicle_locations')->insert([
            [
                'vehicle_id' => 1,
                'latitude' => 33.5731,
                'longitude' => -7.5898,
                'speed_kmh' => 45,
                'heading' => 180,
                'recorded_at' => now()->subMinutes(5),
                'created_at' => now(),
            ],
            [
                'vehicle_id' => 1,
                'latitude' => 33.5800,
                'longitude' => -7.6000,
                'speed_kmh' => 50,
                'heading' => 175,
                'recorded_at' => now()->subMinutes(10),
                'created_at' => now(),
            ],
            [
                'vehicle_id' => 2,
                'latitude' => 33.6065,
                'longitude' => -7.5625,
                'speed_kmh' => 0,
                'heading' => 90,
                'recorded_at' => now()->subMinutes(30),
                'created_at' => now(),
            ],
        ]);
    }
}
