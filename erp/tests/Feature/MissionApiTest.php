<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class MissionApiTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        // Avoid strict auth logic failure if User table needs columns
        $this->user = User::factory()->create();
    }

    public function test_mission_fsm_flow()
    {
        // 1. Create Mission (Plan)
        $response = $this->actingAs($this->user)->postJson('/api/logistics/missions', [
            'mission_id' => 100,
            'company_id' => 1,
            'points' => [
                ['mission_point_id' => 10, 'contact_id' => 200],
                ['mission_point_id' => 11, 'contact_id' => 201]
            ]
        ]);

        $response->assertStatus(202);
        $missionId = $response->json('id');
        $this->assertNotEmpty($missionId);

        // 2. Load Logistics/Mission Stock
        $response = $this->actingAs($this->user)->postJson("/api/logistics/missions/{$missionId}/load", [
            'company_id' => 1
        ]);
        $response->assertStatus(202);

        // 3. Visit first stop
        $response = $this->actingAs($this->user)->postJson("/api/logistics/missions/{$missionId}/stops/visit", [
            'company_id' => 1,
            'point_id' => 10,
            'route_id' => 500,
            'visited_at' => now()->toIso8601String(),
            'delivery_data' => [
                'quantite_livree' => 50,
                'montant_encaisse' => 100000
            ]
        ]);
        $response->assertStatus(202);

        // 4. Complete Mission Route
        $response = $this->actingAs($this->user)->postJson("/api/logistics/missions/{$missionId}/complete", [
            'company_id' => 1
        ]);
        $response->assertStatus(202);

        // Verify Event Store caught all FSM transitions securely
        $this->assertDatabaseHas('domain_events', [
            'aggregate_id' => $missionId,
            'event_type' => 'MissionCreated'
        ]);
        $this->assertDatabaseHas('domain_events', [
            'aggregate_id' => $missionId,
            'event_type' => 'MissionLoaded'
        ]);
        $this->assertDatabaseHas('domain_events', [
            'aggregate_id' => $missionId,
            'event_type' => 'StopVisited'
        ]);
        $this->assertDatabaseHas('domain_events', [
            'aggregate_id' => $missionId,
            'event_type' => 'MissionCompleted'
        ]);
    }
}
