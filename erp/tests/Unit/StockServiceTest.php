<?php

namespace Tests\Unit;

use App\Models\Article;
use App\Models\ArticleUnite;
use App\Models\ArticleMovement;
use App\Models\Depot;
use App\Models\DomainOutbox;
use App\Models\Entreprise;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedDatabase();
    }

    protected function seedDatabase(): void
    {
        // Create entreprise
        Entreprise::create([
            'entreprise_id' => 1,
            'nom' => 'Test Entreprise',
        ]);

        // Create article
        Article::create([
            'article_id' => 1,
            'entreprise_id' => 1,
            'article_designation' => 'Test Article',
        ]);

        // Create default unit
        ArticleUnite::create([
            'article_unite_id' => 1,
            'article_id' => 1,
            'is_default' => 1,
            'article_unite_quantite' => 1,
        ]);

        // Create depot
        Depot::create([
            'depot_id' => 1,
            'entreprise_id' => 1,
            'depot_designation' => 'Main Depot',
        ]);
    }

    protected function seedSequence(string $aggregateType, int $aggregateId): void
    {
        DB::table('aggregate_sequences')->insertOrIgnore([
            'aggregate_type' => $aggregateType,
            'aggregate_id' => $aggregateId,
            'seq' => 0,
        ]);
    }

    #[Test]
    public function stock_movement_creates_outbox_event()
    {
        // Ensure sequence row exists
        $this->seedSequence('stock_movement', 1);

        // Create movement
        $movement = ArticleMovement::create([
            'article_id' => 1,
            'stock_operation_type' => 1,
            'depot_id_destination' => 1,
            'article_mouvement_quantite' => 10,
            'article_mouvement_quantite_restante' => 10,
            'article_mouvement_unite_id' => 1,
            'entreprise_id' => 1, // Explicit authority check
        ]);

        // 1. Authority Verification (The Event itself is the Truth)
        $events = \App\Models\DomainEvent::where('aggregate_type', 'stock_movement')
            ->where('aggregate_id', $movement->article_mouvement_id)
            ->get();
            
        $this->assertCount(1, $events, "Authority Check: One canonical event must exist.");
        
        $event = $events->first();
        $this->assertEquals('stock.movement.created', $event->event_type);
        
        $payload = is_string($event->payload) ? json_decode($event->payload, true) : $event->payload;
        $this->assertEquals(1, $payload['article_id']);
        $this->assertEquals(10, $payload['quantity']);

        // 2. Outbox Reliability (Authority propagation)
        $this->assertDatabaseHas('domain_outbox', [
            'event_id' => $event->id,
            'status' => 'pending'
        ]);

        // 3. Projection Convergence (Materialized state)
        // In a true event-sourced test, we would wait for the projector or trigger it.
        // For this unit test, we verify the movement row exists as the immediate "write-side" projection.
        $this->assertDatabaseHas('article_mouvement', [
            'article_mouvement_id' => $movement->article_mouvement_id,
            'article_mouvement_quantite' => 10
        ]);
    }
}
