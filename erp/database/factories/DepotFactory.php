<?php

namespace Database\Factories;

use App\Models\Depot;
use Illuminate\Database\Eloquent\Factories\Factory;

class DepotFactory extends Factory
{
    protected $model = Depot::class;

    public function definition(): array
    {
        return [
            'entreprise_id' => 1,
            'designation' => $this->faker->randomElement(['Entrepôt Central Alger', 'Dépôt Oran', 'Stock Constantine', 'Magasin Blida', 'Hub Annaba']),
            'is_active' => true,
        ];
    }
}
