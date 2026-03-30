<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SalesSeeder extends Seeder
{
    public function run()
    {
        $customers = [
            ['id' => 1, 'name' => 'Alpha Retail Group'],
            ['id' => 2, 'name' => 'Beta Wholesale'],
            ['id' => 3, 'name' => 'Gamma Distribution'],
        ];

        foreach ($customers as $customer) {
            // Generate 2 orders per customer
            for ($i = 1; $i <= 2; $i++) {
                $orderRef = 'SO-' . Carbon::now()->format('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
                $orderId = (string) Str::uuid();
                
                DB::table('orders')->insert([
                    'id' => $orderId,
                    'reference' => $orderRef,
                    'customer_id' => $customer['id'],
                    'customer_name' => $customer['name'],
                    'status' => 'confirmed',
                    'subtotal_amount' => 0,
                    'total_amount' => 0,
                    'created_by' => 1,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);

                // Generate 2-3 lines per order
                $subtotal_amount = 0;
                $lineCount = rand(2,3);
                for ($j = 1; $j <= $lineCount; $j++) {
                    $unitPrice = rand(100, 500);
                    $qty = rand(1, 10);
                    $lineTotal = $unitPrice * $qty;
                    $subtotal_amount += $lineTotal;

                    DB::table('order_lines')->insert([
                        'order_id' => $orderId,
                        'product_id' => rand(1000, 2000),
                        'product_name' => 'Product ' . chr(64 + $j),
                        'quantity' => $qty,
                        'unit_price' => $unitPrice,
                        'total' => $lineTotal,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]);
                }

                // Update order total
                $total_amount = $subtotal_amount * 1.2; // 20% tax
                DB::table('orders')->where('id', $orderId)->update([
                    'subtotal_amount' => $subtotal_amount,
                    'total_amount' => $total_amount
                ]);
            }
        }
    }
}
