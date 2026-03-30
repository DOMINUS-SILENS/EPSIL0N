<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@epsilon-erp.com',
            'password' => Hash::make('password'),
        ]);

        // Manager user
        User::create([
            'name' => 'Manager User',
            'email' => 'manager@epsilon-erp.com',
            'password' => Hash::make('password'),
        ]);

        // Sales rep
        User::create([
            'name' => 'Sales Representative',
            'email' => 'sales@epsilon-erp.com',
            'password' => Hash::make('password'),
        ]);

        // Warehouse manager
        User::create([
            'name' => 'Warehouse Manager',
            'email' => 'warehouse@epsilon-erp.com',
            'password' => Hash::make('password'),
        ]);

        // Delivery driver
        User::create([
            'name' => 'Delivery Driver',
            'email' => 'driver@epsilon-erp.com',
            'password' => Hash::make('password'),
        ]);

        // Generate additional random users
        User::factory()->count(10)->create();
    }
}
