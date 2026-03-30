<?php

namespace Database\Factories;

use App\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;

class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        return [
            'entreprise_id' => 1,
            'designation' => $this->faker->words(3, true),
            'barcode' => 'ART-' . $this->faker->unique()->numerify('#####'),
            'ean13' => $this->faker->unique()->numerify('#############'),
            'quantite_stock' => $this->faker->randomFloat(3, 0, 1000),
            'quantite_min' => $this->faker->numberBetween(5, 50),
            'active' => true,
            'is_stock_managed' => true,
        ];
    }
}
