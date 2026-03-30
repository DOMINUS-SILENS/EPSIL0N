<?php

namespace Database\Factories;

use App\Models\DeviceSyncState;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class DeviceSyncStateFactory extends Factory
{
    protected $model = DeviceSyncState::class;

    public function definition(): array
    {
        return [
            'entreprise_id' => 1,
            'device_id' => 'DEV-' . $this->faker->unique()->numerify('####'),
            'entity_type' => $this->faker->randomElement(['orders', 'customers', 'articles']),
            'last_sync_timestamp' => Carbon::now()->timestamp,
            'last_sync_sequence' => $this->faker->numberBetween(1, 10000),
            'created_at' => Carbon::now(),
        ];
    }
}
