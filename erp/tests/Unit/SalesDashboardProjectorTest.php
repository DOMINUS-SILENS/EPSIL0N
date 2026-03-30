<?php

namespace Tests\Unit;

use App\Events\MovementValidated;
use App\Events\StopVisited;
use App\Models\DomainOutbox;
use App\Services\Projectors\SalesDashboardProjector;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Tests pour le SalesDashboardProjector
 * Vérifie l'idempotence, l'agrégation correcte et la gestion des doublons.
 */
class SalesDashboardProjectorTest extends TestCase
{
    protected SalesDashboardProjector $projector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->projector = app(SalesDashboardProjector::class);

        // Clean tables before each test
        DB::table('dashboard_sales')->delete();
        DB::table('dashboard_top_articles')->delete();
    }

    /** @test */
    public function it_creates_dashboard_sales_record_on_movement_validated(): void
    {
        $payload = $this->createMovementValidatedEvent([
            'routeId' => 1,
            'date' => '2026-03-22',
            'totalHt' => 1000.00,
            'totalTtc' => 1200.00,
        ]);
        
        $event = $this->createDomainOutbox('MovementValidated', $payload, 1);

        $this->projector->handleMovementValidated($payload, $event);

        $record = DB::table('dashboard_sales')
            ->where('company_id', 1)
            ->where('date', '2026-03-22')
            ->where('route_id', 1)
            ->first();

        $this->assertNotNull($record);
        $this->assertEquals(1000.00, $record->total_ht);
        $this->assertEquals(1200.00, $record->total_ttc);
        $this->assertEquals(1, $record->nb_orders);
    }

    /** @test */
    public function it_aggregates_multiple_movements_for_same_route_and_date(): void
    {
        $basePayload = [
            'companyId' => 1,
            'routeId' => 1,
            'date' => '2026-03-22',
            'totalHt' => 500.00,
            'totalTtc' => 600.00,
            'lines' => [],
        ];

        // Process 3 movements
        for ($i = 1; $i <= 3; $i++) {
            $event = $this->createDomainOutbox('MovementValidated', array_merge($basePayload, [
                'totalHt' => 500.00 * $i,
                'totalTtc' => 600.00 * $i,
            ]), $i);

            $this->projector->handleMovementValidated((array) $event->payload, $event);
        }

        $record = DB::table('dashboard_sales')
            ->where('company_id', 1)
            ->where('date', '2026-03-22')
            ->where('route_id', 1)
            ->first();

        // Total: 500 + 1000 + 1500 = 3000
        $this->assertEquals(3000.00, $record->total_ht);
        $this->assertEquals(3600.00, $record->total_ttc);
        $this->assertEquals(3, $record->nb_orders);
    }

    /** @test */
    public function it_is_idempotent_and_does_not_double_count(): void
    {
        $payload = [
            'companyId' => 1,
            'routeId' => 1,
            'date' => '2026-03-22',
            'totalHt' => 1000.00,
            'totalTtc' => 1200.00,
            'lines' => [],
        ];

        $event = $this->createDomainOutbox('MovementValidated', $payload, 1);

        // Process same event twice
        $this->projector->handleMovementValidated($payload, $event);
        $this->projector->handleMovementValidated($payload, $event);

        $record = DB::table('dashboard_sales')
            ->where('company_id', 1)
            ->where('date', '2026-03-22')
            ->where('route_id', 1)
            ->first();

        // Should only count once due to last_event_id check
        $this->assertEquals(1000.00, $record->total_ht);
        $this->assertEquals(1, $record->nb_orders);
    }

    /** @test */
    public function it_creates_top_articles_records(): void
    {
        $payload = [
            'companyId' => 1,
            'routeId' => 1,
            'date' => '2026-03-22',
            'totalHt' => 1000.00,
            'totalTtc' => 1200.00,
            'lines' => [
                ['article_id' => 1, 'quantity' => 10, 'price_ht' => 500.00],
                ['article_id' => 2, 'quantity' => 5, 'price_ht' => 500.00],
            ],
        ];

        $event = $this->createDomainOutbox('MovementValidated', $payload, 1);
        $this->projector->handleMovementValidated($payload, $event);

        $article1 = DB::table('dashboard_top_articles')
            ->where('company_id', 1)
            ->where('date', '2026-03-22')
            ->where('article_id', 1)
            ->first();

        $article2 = DB::table('dashboard_top_articles')
            ->where('company_id', 1)
            ->where('date', '2026-03-22')
            ->where('article_id', 2)
            ->first();

        $this->assertNotNull($article1);
        $this->assertEquals(10, $article1->quantity_sold);
        $this->assertEquals(500.00, $article1->amount_ht);

        $this->assertNotNull($article2);
        $this->assertEquals(5, $article2->quantity_sold);
        $this->assertEquals(500.00, $article2->amount_ht);
    }

    /** @test */
    public function it_increments_client_visits_on_stop_visited(): void
    {
        // First create a sales record via movement
        $movementPayload = [
            'companyId' => 1,
            'routeId' => 1,
            'date' => '2026-03-22',
            'totalHt' => 1000.00,
            'totalTtc' => 1200.00,
            'lines' => [],
        ];

        $movementEvent = $this->createDomainOutbox('MovementValidated', $movementPayload, 1);
        $this->projector->handleMovementValidated($movementPayload, $movementEvent);

        // Then add visits
        for ($i = 1; $i <= 3; $i++) {
            $stopPayload = [
                'companyId' => 1,
                'routeId' => 1,
                'visitedAt' => '2026-03-22 10:00:00',
            ];
            $stopEvent = $this->createDomainOutbox('StopVisited', $stopPayload, $i + 10);
            $this->projector->handleStopVisited($stopPayload, $stopEvent);
        }

        $record = DB::table('dashboard_sales')
            ->where('company_id', 1)
            ->where('date', '2026-03-22')
            ->where('route_id', 1)
            ->first();

        $this->assertEquals(3, $record->nb_clients_visited);
    }

    /** @test */
    public function it_handles_different_routes_and_dates_independently(): void
    {
        $routes = [
            ['routeId' => 1, 'date' => '2026-03-22', 'total' => 1000],
            ['routeId' => 1, 'date' => '2026-03-23', 'total' => 2000],
            ['routeId' => 2, 'date' => '2026-03-22', 'total' => 3000],
        ];

        foreach ($routes as $i => $data) {
            $payload = [
                'companyId' => 1,
                'routeId' => $data['routeId'],
                'date' => $data['date'],
                'totalHt' => $data['total'],
                'totalTtc' => $data['total'] * 1.2,
                'lines' => [],
            ];

            $event = $this->createDomainOutbox('MovementValidated', $payload, $i + 1);
            $this->projector->handleMovementValidated($payload, $event);
        }

        $records = DB::table('dashboard_sales')->get();
        $this->assertCount(3, $records);

        $totals = $records->pluck('total_ht')->toArray();
        sort($totals);
        $this->assertEquals([1000, 2000, 3000], $totals);
    }

    private function createMovementValidatedEvent(array $overrides = []): array
    {
        return array_merge([
            'companyId' => 1,
            'routeId' => 1,
            'date' => '2026-03-22',
            'totalHt' => 1000.00,
            'totalTtc' => 1200.00,
            'lines' => [],
        ], $overrides);
    }

    private function createDomainOutbox(string $eventType, array $payload, int $eventId): DomainOutbox
    {
        $outbox = new DomainOutbox([
            'id' => $eventId,
            'aggregate_type' => 'Movement',
            'aggregate_id' => 1,
            'sequence' => $eventId,
            'event_type' => $eventType,
            'payload' => $payload,
            'status' => 'pending',
        ]);
        $outbox->syncOriginal();
        return $outbox;
    }
}