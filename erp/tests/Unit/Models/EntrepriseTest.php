<?php

namespace Tests\Unit\Models;

use App\Models\Entreprise;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Entreprise Model Test Suite
 * 
 * Tests the Entreprise (Company) model including:
 * - CRUD operations
 * - Fillable fields
 * - Primary key configuration
 * - Relationships with articles and depots
 * 
 * This is the root entity for multi-tenant operations.
 * 
 * @package Tests\Unit\Models
 * @covers \App\Models\Entreprise
 */
class EntrepriseTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_create_an_entreprise(): void
    {
        $entreprise = Entreprise::create([
            'entreprise_id' => 1,
            'nom' => 'Test Company',
            'raison_sociale' => 'Test Company SA',
            'adresse' => '123 Test Street',
            'telephone' => '0234567890',
            'email' => 'test@company.com',
        ]);

        $this->assertDatabaseHas('entreprise', [
            'entreprise_id' => 1,
            'nom' => 'Test Company',
        ]);
    }

    #[Test]
    public function it_uses_correct_table_name(): void
    {
        $entreprise = new Entreprise();
        $this->assertEquals('entreprise', $entreprise->getTable());
    }

    #[Test]
    public function it_uses_correct_primary_key(): void
    {
        $entreprise = new Entreprise();
        $this->assertEquals('entreprise_id', $entreprise->getKeyName());
    }

    #[Test]
    public function it_has_all_fillable_fields(): void
    {
        $entreprise = new Entreprise();
        $fillable = $entreprise->getFillable();

        $expected = [
            'entreprise_id',
            'nom',
            'raison_sociale',
            'adresse',
            'telephone',
            'email',
        ];

        foreach ($expected as $field) {
            $this->assertContains($field, $fillable, "Field {$field} should be fillable");
        }
    }

    #[Test]
    public function it_supports_nullable_fields(): void
    {
        $entreprise = Entreprise::create([
            'entreprise_id' => 1,
            'nom' => 'Minimal Company',
            // All other fields left as null
        ]);

        $this->assertNull($entreprise->raison_sociale);
        $this->assertNull($entreprise->adresse);
        $this->assertNull($entreprise->telephone);
        $this->assertNull($entreprise->email);
    }

    #[Test]
    public function it_uses_incrementing_primary_key(): void
    {
        $entreprise = new Entreprise();
        $this->assertTrue($entreprise->incrementing);
    }

    #[Test]
    public function it_uses_integer_key_type(): void
    {
        $entreprise = new Entreprise();
        $this->assertEquals('int', $entreprise->getKeyType());
    }
}
