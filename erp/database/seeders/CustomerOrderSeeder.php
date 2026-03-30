<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * CustomerOrderSeeder - Customers and sales orders
 */
class CustomerOrderSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // Customers
        $customers = [
            [
                'id' => (string) Str::uuid(),
                'entreprise_id' => 1,
                'name' => 'Acme Corporation',
                'credit_limit' => 50000.00,
                'created_at' => $now->copy()->subDays(60),
                'updated_at' => $now->copy()->subDays(60),
            ],
            [
                'id' => (string) Str::uuid(),
                'entreprise_id' => 1,
                'name' => 'TechStore SARL',
                'credit_limit' => 30000.00,
                'created_at' => $now->copy()->subDays(45),
                'updated_at' => $now->copy()->subDays(45),
            ],
            [
                'id' => (string) Str::uuid(),
                'entreprise_id' => 1,
                'name' => 'LogiShop France',
                'credit_limit' => 25000.00,
                'created_at' => $now->copy()->subDays(30),
                'updated_at' => $now->copy()->subDays(30),
            ],
            [
                'id' => (string) Str::uuid(),
                'entreprise_id' => 1,
                'name' => 'FreshMarket Lyon',
                'credit_limit' => 15000.00,
                'created_at' => $now->copy()->subDays(15),
                'updated_at' => $now->copy()->subDays(15),
            ],
        ];

        $customerIds = [];
        foreach ($customers as $customer) {
            DB::table('customers')->insertOrIgnore($customer);
            $customerIds[] = $customer['id'];
        }

        // Sales Orders
        $orders = [
            [
                'id' => (string) Str::uuid(),
                'reference' => 'SO-2026-0001',
                'customer_id' => $customerIds[0] ?? null,
                'customer_name' => 'Acme Corporation',
                'status' => 'confirmed',
                'subtotal_amount' => 3000.00,
                'total_amount' => 3600.00,
                'created_by' => 1,
                'created_at' => $now->copy()->subDays(10),
                'updated_at' => $now->copy()->subDays(10),
            ],
            [
                'id' => (string) Str::uuid(),
                'reference' => 'SO-2026-0002',
                'customer_id' => $customerIds[1] ?? null,
                'customer_name' => 'TechStore SARL',
                'status' => 'confirmed',
                'subtotal_amount' => 2500.00,
                'total_amount' => 3000.00,
                'created_by' => 1,
                'created_at' => $now->copy()->subDays(8),
                'updated_at' => $now->copy()->subDays(8),
            ],
            [
                'id' => (string) Str::uuid(),
                'reference' => 'SO-2026-0003',
                'customer_id' => $customerIds[2] ?? null,
                'customer_name' => 'LogiShop France',
                'status' => 'draft',
                'subtotal_amount' => 1500.00,
                'total_amount' => 1800.00,
                'created_by' => 1,
                'created_at' => $now->copy()->subDays(2),
                'updated_at' => $now->copy()->subDays(2),
            ],
            [
                'id' => (string) Str::uuid(),
                'reference' => 'SO-2026-0004',
                'customer_id' => $customerIds[3] ?? null,
                'customer_name' => 'FreshMarket Lyon',
                'status' => 'confirmed',
                'subtotal_amount' => 500.00,
                'total_amount' => 600.00,
                'created_by' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        $orderIds = [];
        foreach ($orders as $order) {
            DB::table('orders')->insertOrIgnore($order);
            $orderIds[] = $order['id'];
        }

        // Order Lines
        $orderLines = [
            // Order 1: Laptops + Mouse
            [
                'order_id' => $orderIds[0] ?? null,
                'product_id' => 1,
                'product_name' => 'Laptop EPSILON Pro 15',
                'quantity' => 2,
                'unit_price' => 1299.00,
                'total' => 2598.00,
                'created_at' => $now->copy()->subDays(10),
                'updated_at' => $now->copy()->subDays(10),
            ],
            [
                'order_id' => $orderIds[0] ?? null,
                'product_id' => 2,
                'product_name' => 'Souris Sans Fil EPSILON',
                'quantity' => 5,
                'unit_price' => 49.99,
                'total' => 249.95,
                'created_at' => $now->copy()->subDays(10),
                'updated_at' => $now->copy()->subDays(10),
            ],
            [
                'order_id' => $orderIds[0] ?? null,
                'product_id' => 3,
                'product_name' => 'Clavier Mécanique RGB',
                'quantity' => 2,
                'unit_price' => 149.99,
                'total' => 299.98,
                'created_at' => $now->copy()->subDays(10),
                'updated_at' => $now->copy()->subDays(10),
            ],
            // Order 2: Keyboards only
            [
                'order_id' => $orderIds[1] ?? null,
                'product_id' => 3,
                'product_name' => 'Clavier Mécanique RGB',
                'quantity' => 10,
                'unit_price' => 149.99,
                'total' => 1499.90,
                'created_at' => $now->copy()->subDays(8),
                'updated_at' => $now->copy()->subDays(8),
            ],
            [
                'order_id' => $orderIds[1] ?? null,
                'product_id' => 2,
                'product_name' => 'Souris Sans Fil EPSILON',
                'quantity' => 5,
                'unit_price' => 49.99,
                'total' => 249.95,
                'created_at' => $now->copy()->subDays(8),
                'updated_at' => $now->copy()->subDays(8),
            ],
            // Order 3 (draft): Fresh Products
            [
                'order_id' => $orderIds[2] ?? null,
                'product_id' => 4,
                'product_name' => 'Fromage Frais Camembert',
                'quantity' => 50,
                'unit_price' => 7.99,
                'total' => 399.50,
                'created_at' => $now->copy()->subDays(2),
                'updated_at' => $now->copy()->subDays(2),
            ],
            [
                'order_id' => $orderIds[2] ?? null,
                'product_id' => 5,
                'product_name' => 'Lait Entier 1L',
                'quantity' => 100,
                'unit_price' => 1.49,
                'total' => 149.00,
                'created_at' => $now->copy()->subDays(2),
                'updated_at' => $now->copy()->subDays(2),
            ],
            // Order 4: Food items
            [
                'order_id' => $orderIds[3] ?? null,
                'product_id' => 4,
                'product_name' => 'Fromage Frais Camembert',
                'quantity' => 20,
                'unit_price' => 7.99,
                'total' => 159.80,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($orderLines as $line) {
            DB::table('order_lines')->insertOrIgnore($line);
        }
    }
}
