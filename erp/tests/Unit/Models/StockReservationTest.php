<?php

namespace Tests\Unit\Models;

use App\Models\StockReservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * StockReservation Model Test Suite
 * 
 * Tests the StockReservation model including:
 * - Reservation creation for stock items
 * - Status management
 * - Expiry tracking
 * - Product and warehouse associations
 * 
 * @package Tests\Unit\Models
 * @covers \App\Models\StockReservation
 */
class StockReservationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_create_stock_reservation(): void
    {
        $reservation = StockReservation::create([
            'product_id' => 1,
            'warehouse_id' => 1,
            'order_id' => 123,
            'qty' => 50.5,
            'status' => 'pending',
            'expires_at' => now()->addHours(24),
            'sequence' => 1,
        ]);

        $this->assertDatabaseHas('stock_reservations', [
            'product_id' => 1,
            'warehouse_id' => 1,
            'order_id' => 123,
            'qty' => 50.5,
        ]);
    }

    #[Test]
    public function it_manages_status_transitions(): void
    {
        $reservation = StockReservation::create([
            'product_id' => 1,
            'warehouse_id' => 1,
            'order_id' => 123,
            'qty' => 10,
            'status' => 'pending',
            'expires_at' => now()->addHours(24),
            'sequence' => 1,
        ]);

        $this->assertEquals('pending', $reservation->status);

        // Confirm
        $reservation->update(['status' => 'confirmed']);
        $this->assertEquals('confirmed', $reservation->fresh()->status);

        // Cancel
        $reservation->update(['status' => 'cancelled']);
        $this->assertEquals('cancelled', $reservation->fresh()->status);
    }

    #[Test]
    public function it_tracks_expiry(): void
    {
        $expiry = now()->addHours(48);
        
        $reservation = StockReservation::create([
            'product_id' => 2,
            'warehouse_id' => 1,
            'order_id' => 456,
            'qty' => 100,
            'status' => 'pending',
            'expires_at' => $expiry,
            'sequence' => 2,
        ]);

        $this->assertEquals($expiry->format('Y-m-d H:i:s'), $reservation->expires_at->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function it_handles_float_quantities(): void
    {
        $reservation = StockReservation::create([
            'product_id' => 1,
            'warehouse_id' => 1,
            'order_id' => 789,
            'qty' => 25.75,
            'status' => 'pending',
            'expires_at' => now()->addHours(24),
            'sequence' => 1,
        ]);

        $this->assertEquals(25.75, $reservation->qty);
    }

    #[Test]
    public function it_uses_correct_table_name(): void
    {
        $reservation = new StockReservation();
        $this->assertEquals('stock_reservations', $reservation->getTable());
    }

    #[Test]
    public function it_has_fillable_fields(): void
    {
        $reservation = new StockReservation();
        $fillable = $reservation->getFillable();

        $expected = [
            'product_id',
            'warehouse_id',
            'order_id',
            'qty',
            'status',
            'expires_at',
            'sequence',
        ];

        foreach ($expected as $field) {
            $this->assertContains($field, $fillable);
        }
    }
}
