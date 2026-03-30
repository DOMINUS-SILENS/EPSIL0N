<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class LeadApiTest extends TestCase
{
    public function test_can_create_lead_command()
    {
        $user = User::factory()->make(['id' => 1]);
        $this->actingAs($user);

        $payload = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '123456789'
        ];

        $response = $this->postJson('/api/crm/leads', $payload);

        $response->assertStatus(202);
        $response->assertJsonStructure(['id', 'status']);
    }

    public function test_validates_lead_payload()
    {
        $user = User::factory()->make(['id' => 1]);
        $this->actingAs($user);

        $payload = [
            'first_name' => 'John',
            // missing last_name and email
        ];

        $response = $this->postJson('/api/crm/leads', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['last_name', 'email']);
    }
}
