<?php

namespace Tests\Unit;

use App\Models\CreditReservation;
use App\Models\StockReservation;
use App\Services\ReservationService;
use App\Services\SequenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReservationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ReservationService $reservation;

    protected SequenceService $sequence;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sequence = app(SequenceService::class);
        $this->reservation = app(ReservationService::class);

        // Seed minimal data
        DB::table('customers')->insert([
            'id' => 1,
            'company_id' => 1,
            'name' => 'Test Customer',
            'credit_limit' => 10000,
        ]);

        DB::table('stock_moves')->insert([
            ['product_id' => 1, 'warehouse_id' => 1, 'type' => 'in', 'qty' => 100],
        ]);

        // Seed article and default unit for stock reservation
        DB::table('entreprise')->insert(['entreprise_id' => 1, 'nom' => 'Test']);
        DB::table('article')->insert(['article_id' => 1, 'entreprise_id' => 1, 'article_designation' => 'Test']);
        DB::table('depot')->insert(['depot_id' => 1, 'entreprise_id' => 1, 'depot_designation' => 'Test Depot']);
        DB::table('article_unite')->insert([
            'article_unite_id' => 1,
            'article_id' => 1,
            'is_default' => 1,
            'article_unite_quantite' => 1,
        ]);
        DB::table('article_unite_depot')->insert([
            'article_id' => 1,
            'unite_id' => 1,
            'depot_id' => 1,
            'quantite' => 100,
        ]);
    }

    #[Test]
    public function it_creates_credit_reservation_when_enough_credit()
    {
        $this->sequence->ensureExists('credit_reservation', 1);
        $res = $this->reservation->reserveCredit(1, 1, 1, 500, 24);

        $this->assertInstanceOf(CreditReservation::class, $res);
        $this->assertEquals(500, $res->amount);
        $this->assertEquals('pending', $res->status);
        $this->assertEquals(1, $res->sequence);
    }

    #[Test]
    public function it_rejects_credit_reservation_exceeding_limit()
    {
        $this->expectException(\RuntimeException::class);
        $this->reservation->reserveCredit(1, 1, 1, 15000, 24);
    }

    #[Test]
    public function it_creates_stock_reservation_when_enough_stock()
    {
        $this->sequence->ensureExists('stock_reservation', 1);
        $res = $this->reservation->reserveStock(1, 1, 1, 30, 24);

        $this->assertInstanceOf(StockReservation::class, $res);
        $this->assertEquals(30, $res->qty);
        $this->assertEquals('pending', $res->status);
        $this->assertEquals(1, $res->sequence);
    }

    #[Test]
    public function it_rejects_stock_reservation_insufficient_stock()
    {
        $this->expectException(\RuntimeException::class);
        $this->reservation->reserveStock(1, 1, 1, 200, 24);
    }

    #[Test]
    public function it_expires_old_reservations()
    {
        $this->sequence->ensureExists('credit_reservation', 1);
        $res = CreditReservation::create([
            'company_id' => 1,
            'customer_id' => 1,
            'order_id' => 1,
            'amount' => 100,
            'expires_at' => now()->subHour(),
            'sequence' => $this->sequence->next('credit_reservation', 1),
        ]);

        $this->reservation->expireReservations();

        $this->assertEquals('expired', $res->fresh()->status);
    }
}
