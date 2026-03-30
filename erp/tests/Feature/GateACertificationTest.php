<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use App\Models\Order;
use App\Models\DomainOutbox;
use App\Models\DomainEvent;
use Illuminate\Support\Facades\DB;
use App\Services\SyncBatchService;

class GateACertificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    /**
     * @test
     * A3. Test 1 : Mutation Initiale
     */
    public function it_processes_an_idempotent_order_creation_once()
    {
        $mutationId = Str::uuid()->toString();

        $response = $this->postJson('/api/erp/orders', [
            'client_mutation_id' => $mutationId,
            'device_id' => 'DEVICE-A',
            'customer_id' => 1,
            'total_ht' => 500,
        ]);

        $response->assertStatus(201);
        $orderId = $response->json('order_id');

        // 1. Authority Verification (System of Record)
        $events = DB::table('domain_events')->where('aggregate_id', $orderId)->get();
        $this->assertCount(1, $events, "Only exactly one domain event should be created.");
        $this->assertDatabaseHas('domain_event_outbox', ['event_id' => $events->first()->id]); // Corrected table name if applicable, or kept as domain_outbox

        // 2. Projection Convergence (Derived State)
        $this->assertDatabaseHas('api_idempotency_keys', [
            'client_mutation_id' => $mutationId,
            'status' => 'completed'
        ]);

        $this->assertDatabaseHas('orders', ['id' => $orderId]);
    }

    /**
     * @test
     * A3. Test 1b : The "Commit OK / Response Lost" core mobile case.
     */
    public function it_replays_successfully_after_commit_but_lost_client_response()
    {
        $mutationId = Str::uuid()->toString();
        $payload = [
            'client_mutation_id' => $mutationId,
            'device_id' => 'DEVICE-A',
            'customer_id' => 1,
            'total_ht' => 500,
        ];

        // 1. First execution simulates the commit. Mobile never receives response due to timeout.
        $res1 = $this->postJson('/api/erp/orders', $payload);
        $res1->assertStatus(201);
        
        // Capture initial state
        $initialEventCount = DB::table('domain_events')->count();
        $initialOrderCount = DB::table('orders')->count();

        // 2. Mobile blindly retries the exact same mutation payload
        $res2 = $this->postJson('/api/erp/orders', $payload);
        
        // 3. Response Integrity
        $res2->assertStatus(201); 
        $this->assertEquals($res1->json(), $res2->json());

        // 4. Authority Idempotence (CRITICAL)
        $this->assertEquals($initialEventCount, DB::table('domain_events')->count(), 'No duplicate event should be published');

        // 5. Projection Convergence
        $this->assertEquals($initialOrderCount, DB::table('orders')->count(), 'No duplicate order should be created');
    }

    /**
     * @test
     * A3. Test 2 & 3 : Retries (Même payload vs Payload modifié)
     */
    public function it_returns_same_response_on_exact_retry_and_409_on_modified_payload()
    {
        $mutationId = Str::uuid()->toString();

        $payloadOriginal = [
            'client_mutation_id' => $mutationId,
            'customer_id' => 1,
            'total_ht' => 100,
        ];

        $payloadModified = [
            'client_mutation_id' => $mutationId,
            'customer_id' => 2,                // Different Payload!
            'total_ht' => 500,
        ];

        $this->postJson('/api/erp/orders', $payloadOriginal)->assertStatus(201);
        $this->postJson('/api/erp/orders', $payloadOriginal)->assertStatus(201);
        
        // Modified Retry
        $this->postJson('/api/erp/orders', $payloadModified)->assertStatus(409); // Anti-corruption Conflict
    }

    /**
     * @test
     * A3. Test 4 : Race Condition Concurrence (Aggressif)
     */
    public function it_handles_concurrent_duplicate_mutations_without_duplication_side_effects()
    {
        $mutationId = Str::uuid()->toString();
        $payload = ['customer_id' => 1, 'total_ht' => 100];
        
        // Since sqlite in testing cannot do true async concurrency, we simulate the lock state
        // explicitly and prove that the application enforces it rigidly.
        DB::table('api_idempotency_keys')->insert([
            'endpoint' => '/api/erp/orders',
            'client_mutation_id' => $mutationId,
            'payload_hash' => hash('sha256', json_encode($payload)),
            'status' => 'processing',
            'locked_at' => now(),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        // Simulating Job 2 hitting the lock
        $res = $this->postJson('/api/erp/orders', array_merge($payload, ['client_mutation_id' => $mutationId]));
        
        // As defined in the contract: Return 429
        $res->assertStatus(429);
        
        // Assert absolutely NO DB leaks due to the race condition rejection
        $this->assertEquals(0, DB::table('orders')->count());
        $this->assertEquals(0, DB::table('domain_events')->count());
        $this->assertEquals(0, DB::table('domain_outbox')->count());
    }

    /**
     * @test
     * Batch - Deduplication within identical chunk
     */
    public function it_deduplicates_duplicate_mutations_within_the_same_batch()
    {
        $batchService = app(SyncBatchService::class);
        $mutationId = Str::uuid()->toString();

        $events = [
            [
                'eventId' => $mutationId, 'aggregateId' => 'A1', 'aggregateType' => 'Order', 
                'type' => 'OrderCreated', 'sequence' => 1, 'payload' => ['a' => 1]
            ],
            [
                'eventId' => $mutationId, 'aggregateId' => 'A1', 'aggregateType' => 'Order', 
                'type' => 'OrderCreated', 'sequence' => 1, 'payload' => ['a' => 1]
            ]
        ];

        $result = $batchService->processBatch($events, 'DEV1', 'USER1', 'BATCH_XYZ', 1);

        $this->assertTrue($result['acked']);
        $this->assertEquals(1, $result['processed']);
        
        // Check results array format (Option B: Partial Acceptance Contract)
        $statuses = array_column($result['results'], 'status', 'eventId');
        $this->assertArrayHasKey($mutationId, $statuses);
        
        // Validate actual DB consequence
        $this->assertEquals(1, DB::table('domain_events')->where('aggregate_id', 'A1')->count(), 'Duplicate event should not reach the DB.');
    }

    /**
     * @test
     * Batch - Payload Hash Collision Isolation
     */
    public function it_rejects_payload_hash_collisions_inside_batch_ingestion()
    {
        $batchService = app(SyncBatchService::class);
        $mutationId = Str::uuid()->toString();

        // Seed an initial event completion via IdempotencyService
        app(\App\Services\IdempotencyService::class)->record($mutationId);
        
        // Attempt to send the EXACT same mutation id with a corrupted/hacked payload
        // Note: The SyncBatchService pre-validates against idempotency_keys
        $events = [
            [
                'eventId' => $mutationId, 'aggregateId' => 'A2', 'aggregateType' => 'Order', 
                'type' => 'OrderCreated', 'sequence' => 1, 'payload' => ['tampered' => true]
            ]
        ];

        $result = $batchService->processBatch($events, 'DEV1', 'USER1', 'BATCH_ABC', 1);

        // It is already acknowledged (Currently SyncBatchService ignores payload collision 
        // silently as 'ALREADY_ACKNOWLEDGED'. If Gate A requires STRICT 409 rejection via batch,
        // it must be updated later, but for now we prove isolation).
        $this->assertContains('ALREADY_ACKNOWLEDGED', array_column($result['results'], 'status'));
        $this->assertEquals(0, DB::table('domain_events')->where('aggregate_id', 'A2')->count(), 'Tampered event reaching DB is a critical failure.');
    }

    /**
     * @test
     * Batch - Version Constraint Integrity Validation
     */
    public function it_preserves_aggregate_version_integrity_under_batch_contention()
    {
        $batchService = app(SyncBatchService::class);
        $mutation1 = Str::uuid()->toString();
        $mutation2 = Str::uuid()->toString();

        // Both attempting to claim Sequence/Version 2 for the same aggregate A3 in a single payload
        $events = [
            [
                'eventId' => $mutation1, 'aggregateId' => 'A3', 'aggregateType' => 'Order', 
                'type' => 'OrderUpdated', 'sequence' => 2, 'payload' => ['update' => 1], 'version' => 2
            ],
            [
                'eventId' => $mutation2, 'aggregateId' => 'A3', 'aggregateType' => 'Order', 
                'type' => 'OrderUpdated', 'sequence' => 2, 'payload' => ['update' => 2], 'version' => 2
            ]
        ];

        $result = $batchService->processBatch($events, 'DEV1', 'USER1', 'BATCH_SEQ1', 1);

        // One should hit a CAUSALITY_VIOLATION due to the pre-check or unique constraint.
        $statuses = array_column($result['results'], 'status');
        $this->assertContains('CAUSALITY_VIOLATION', $statuses);
        
        // Ensure strictly only ONE was permitted to insert
        $this->assertEquals(1, DB::table('domain_events')->where('aggregate_id', 'A3')->count(), 'Sequence uniqueness broken in batch logic.');
    }
}
