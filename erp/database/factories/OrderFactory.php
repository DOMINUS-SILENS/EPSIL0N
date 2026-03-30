<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Customer;
use App\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'entreprise_id' => 1,
            'customer_id' => Customer::inRandomOrder()->first()?->id ?? 1,
            'subtotal_amount' => $this->faker->randomFloat(2, 50, 500),
            'total_amount' => $this->faker->randomFloat(2, 60, 600),
            'lines' => [
                [
                    'product_id' => Article::inRandomOrder()->first()?->article_id ?? 1,
                    'quantity' => rand(1, 5),
                    'unit_price' => rand(10, 50)
                ]
            ]
        ];
    }
}
