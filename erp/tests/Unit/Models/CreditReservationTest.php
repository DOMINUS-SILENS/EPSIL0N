<?php

namespace Tests\Unit\Models;

use App\Models\CreditReservation;
use App\Models\Entreprise;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * CreditReservation Model Test Suite
 * 
 * Tests the CreditReservation model including:
 * - Reservation creation
 * - Status transitions (pending, confirmed, expired, cancelled)
 * - Expiry tracking
 * - Sequence numbering
 * 
 * @package Tests\Unit\Models
 * @covers \App\Models\CreditReservation
 */
class CreditReservationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_create_credit_reservation(): void
    {
        $reservation = CreditReservation::create([
            'company_id' => 1,
            'customer_id' => 1,
            'order_id' => 123,
            'amount' => 500.00,
            'status' => 'pending',
            'expires_at' => now()->addHours(24),
            'sequence' => 1,
        ]);

        $this->assertDatabaseHas('credit_reservations', [
            'company_id' => 1,
            'customer_id' => 1,
            'order_id' => 123,
            'amount' => 500.00,
        ]);
    }

    #[Test]
    public function it_manages_status_transitions(): void
    {
        $reservation = CreditReservation::create([
            'company_id' => 1,
            'customer_id' => 1,
            'order_id' => 123,
            'amount' => 500.00,
            'status' => 'pending',
            'expires_at' => now()->addHours(24),
            'sequence' => 1,
        ]);

        $this->assertEquals('pending', $reservation->status);

        // Confirm reservation
        $reservation->update(['status' => 'confirmed']);
        $this->assertEquals('confirmed', $reservation->fresh()->status);

        // Expire reservation
        $reservation->update(['status' => 'expired']);
        $this->assertEquals('expired', $reservation->fresh()->status);
    }

    #[Test]
    public function it_tracks_expiry(): void
    {
        $expiry = now()->addHours(24);
        
        $reservation = CreditReservation::create([
            'company_id' => 1,
            'customer_id' => 1,
            'order_id' => 123,
            'amount' => 1000.00,
            'status' => 'pending',
            'expires_at' => $expiry,
            'sequence' => 1,
        ]);

        $this->assertEquals($expiry->format('Y-m-d H:i:s'), $reservation->expires_at->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function it_assigns_sequence_number(): void
    {
        $reservation = CreditReservation::create([
            'company_id' => 1,
            'customer_id' => 1,
            'order_id' => 123,
            'amount' => 500.00,
            'status' => 'pending',
            'expires_at' => now()->addHours(24),
            'sequence' => 42,
        ]);

        $this->assertEquals(42, $reservation->sequence);
    }

    #[Test]
    public function it_scopes_active_reservations(): void
    {
        // Active pending reservation
        CreditReservation::create([
            'company_id' => 1,
            'customer_id' => 1,
            'order_id' => 123,
            'amount' => 500.00,
            'status' => 'pending',
            'expires_at' => now()->addHours(24),
            'sequence' => 1,
        ]);

        // Expired reservation
        CreditReservation::create([
            'company_id' => 1,
            'customer_id' => 1,
            'order_id' => 124,
            'amount' => 300.00,
            'status' => 'expired',
            'expires_at' => now()->subHours(24),
            'sequence' => 2,
        ]);

        // Confirmed reservation
        CreditReservation::create([
            'company_id' => 1,
            'customer_id' => 1,
            'order_id' => 125,
            'amount' => 400.00,
            'status' => 'confirmed',
            'expires_at' => now()->addHours(24),
            'sequence' => 3,
        ]);

        $active = CreditReservation::whereIn('status', ['pending', 'confirmed'])->get();
        
        $this->assertCount(2, $active);
    }

    #[Test]
    public function it_uses_correct_table_name(): void
    {
        $reservation = new CreditReservation();
        $this->assertEquals('credit_reservations', $reservation->getTable());
    }

    #[Test]
    public function it_has_fillable_fields(): void
    {
        $reservation = new CreditReservation();
        $fillable = $reservation->getFillable();

        $expected = [
            'company_id',
            'customer_id',
            'order_id',
            'amount',
            'status',
            'expires_at',
            'sequence',
        ];

        foreach ($expected as $field) {
            $this->assertContains($field, $fillable);
        }
    }
}
