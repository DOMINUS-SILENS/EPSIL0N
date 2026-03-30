<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class OrderApiTest extends TestCase
{
    public function test_can_create_order_command()
    {
        $user = User::factory()->make(['id' => 1]);
        $this->actingAs($user);

        $payload = [
            'customer_id' => 123,
            'items' => [
                ['product_id' => 1, 'qty' => 2, 'unit_price' => 100],
            ],
            'notes' => 'Test order'
        ];

        $response = $this->postJson('/api/erp/orders', $payload);

        $response->assertStatus(202);
        $response->assertJsonStructure(['id', 'status']);
    }

    public function test_validates_order_payload()
    {
        $user = User::factory()->make(['id' => 1]);
        $this->actingAs($user);

        $payload = [
            'customer_id' => 123,
            // missing items
        ];

        $response = $this->postJson('/api/erp/orders', $payload);

        $response->assertStatus(422); // Validation error
        $response->assertJsonValidationErrors('items');
    }
}
