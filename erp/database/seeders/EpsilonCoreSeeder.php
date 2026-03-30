<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * EpsilonCoreSeeder - Core users and system data
 * Non-destructive: uses insertOrIgnore()
 */
class EpsilonCoreSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // Admin User
        DB::table('users')->insertOrIgnore([
            'name' => 'Admin EPSILON',
            'email' => 'admin@epsilon-erp.local',
            'email_verified_at' => $now,
            'password' => bcrypt('Admin@Epsilon2026'),
            'remember_token' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Sales Manager
        DB::table('users')->insertOrIgnore([
            'name' => 'Yves Sales',
            'email' => 'yves.sales@epsilon-erp.local',
            'email_verified_at' => $now,
            'password' => bcrypt('Sales@Epsilon2026'),
            'remember_token' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Warehouse Manager
        DB::table('users')->insertOrIgnore([
            'name' => 'Marie Stock',
            'email' => 'marie.stock@epsilon-erp.local',
            'email_verified_at' => $now,
            'password' => bcrypt('Stock@Epsilon2026'),
            'remember_token' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Delivery Manager
        DB::table('users')->insertOrIgnore([
            'name' => 'Pierre Delivery',
            'email' => 'pierre.delivery@epsilon-erp.local',
            'email_verified_at' => $now,
            'password' => bcrypt('Delivery@Epsilon2026'),
            'remember_token' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
