<?php

namespace Tests\Unit\Models;

use App\Models\Article;
use App\Models\Depot;
use App\Models\Entreprise;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Depot Model Test Suite
 * 
 * Tests the Depot (warehouse/location) model including:
 * - Hierarchical structure (parent/child relationships)
 * - Fillable fields (48 fields including logistics)
 * - Spatial data (latitude, longitude)
 * - Temperature controls for cold storage
 * - Relationships with articles through movements
 * 
 * @package Tests\Unit\Models
 * @covers \App\Models\Depot
 */
class DepotTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_create_a_depot(): void
    {
        $entreprise = $this->createEntreprise();

        $depot = Depot::create([
            'depot_id' => 1,
            'entreprise_id' => $entreprise->entreprise_id,
            'depot_designation' => 'Main Warehouse',
            'depot_path' => '/main-warehouse',
            'adresse' => '123 Main Street',
            'wilaya_id' => 16, // Algiers
            'commune_id' => 101,
        ]);

        $this->assertDatabaseHas('depot', [
            'depot_id' => 1,
            'depot_designation' => 'Main Warehouse',
        ]);
    }

    #[Test]
    public function it_can_create_hierarchical_depots(): void
    {
        $entreprise = $this->createEntreprise();

        $parentDepot = Depot::create([
            'depot_id' => 1,
            'entreprise_id' => $entreprise->entreprise_id,
            'depot_designation' => 'Central Warehouse',
            'depot_parent_left' => 1,
            'depot_parent_right' => 4,
        ]);

        $childDepot = Depot::create([
            'depot_id' => 2,
            'entreprise_id' => $entreprise->entreprise_id,
            'depot_designation' => 'Zone A',
            'depot_parent_id' => $parentDepot->depot_id,
            'depot_parent_left' => 2,
            'depot_parent_right' => 3,
        ]);

        $this->assertEquals($parentDepot->depot_id, $childDepot->depot_parent_id);
    }

    #[Test]
    public function it_stores_spatial_coordinates(): void
    {
        $entreprise = $this->createEntreprise();

        $depot = Depot::create([
            'depot_id' => 1,
            'entreprise_id' => $entreprise->entreprise_id,
            'depot_designation' => 'Geolocated Depot',
            'latitude' => 36.7538,
            'longitude' => 3.0588,
        ]);

        $this->assertEquals(36.7538, $depot->latitude);
        $this->assertEquals(3.0588, $depot->longitude);
    }

    #[Test]
    public function it_supports_cold_storage_configuration(): void
    {
        $entreprise = $this->createEntreprise();

        $depot = Depot::create([
            'depot_id' => 1,
            'entreprise_id' => $entreprise->entreprise_id,
            'depot_designation' => 'Cold Storage',
            'frigo' => 1,
            'temperature_min' => -20.0,
            'temperature_max' => -15.0,
        ]);

        $this->assertEquals(1, $depot->frigo);
        $this->assertEquals(-20.0, $depot->temperature_min);
        $this->assertEquals(-15.0, $depot->temperature_max);
    }

    #[Test]
    public function it_calculates_storage_dimensions(): void
    {
        $entreprise = $this->createEntreprise();

        $depot = Depot::create([
            'depot_id' => 1,
            'entreprise_id' => $entreprise->entreprise_id,
            'depot_designation' => 'Measured Depot',
            'longueur' => 100.5,
            'largeur' => 50.25,
            'hauteur' => 12.0,
            'depot_surface' => 5025.0,
            'depot_volume' => 60300.0,
        ]);

        $this->assertEquals(100.5, $depot->longueur);
        $this->assertEquals(50.25, $depot->largeur);
        $this->assertEquals(12.0, $depot->hauteur);
    }

    #[Test]
    public function it_tracks_security_features(): void
    {
        $entreprise = $this->createEntreprise();

        $depot = Depot::create([
            'depot_id' => 1,
            'entreprise_id' => $entreprise->entreprise_id,
            'depot_designation' => 'Secure Depot',
            'depot_gardé' => 1,
            'depot_blindé' => 1,
            'depot_barreaudage' => 1,
        ]);

        $this->assertEquals(1, $depot->depot_gardé);
        $this->assertEquals(1, $depot->depot_blindé);
    }

    #[Test]
    public function it_belongs_to_entreprise(): void
    {
        $entreprise = $this->createEntreprise();

        $depot = Depot::create([
            'depot_id' => 1,
            'entreprise_id' => $entreprise->entreprise_id,
            'depot_designation' => 'Company Depot',
        ]);

        $this->assertInstanceOf(Entreprise::class, $depot->entreprise);
        $this->assertEquals($entreprise->entreprise_id, $depot->entreprise->entreprise_id);
    }

    #[Test]
    public function it_uses_correct_table_name(): void
    {
        $depot = new Depot();
        $this->assertEquals('depot', $depot->getTable());
    }

    #[Test]
    public function it_uses_correct_primary_key(): void
    {
        $depot = new Depot();
        $this->assertEquals('depot_id', $depot->getKeyName());
    }

    #[Test]
    public function it_has_all_fillable_fields(): void
    {
        $depot = new Depot();
        $fillable = $depot->getFillable();

        $expected = [
            'depot_id',
            'entreprise_id',
            'depot_designation',
            'depot_parent_id',
            'depot_surface',
            'depot_volume',
            'latitude',
            'longitude',
            'frigo',
            'temperature_min',
            'temperature_max',
        ];

        foreach ($expected as $field) {
            $this->assertContains($field, $fillable, "Field {$field} should be fillable");
        }
    }

    #[Test]
    public function it_can_be_marked_as_department(): void
    {
        $entreprise = $this->createEntreprise();

        $depot = Depot::create([
            'depot_id' => 1,
            'entreprise_id' => $entreprise->entreprise_id,
            'depot_designation' => 'Department',
            'depot_is_departement' => 1,
            'departement_id' => 101,
        ]);

        $this->assertEquals(1, $depot->depot_is_departement);
        $this->assertEquals(101, $depot->departement_id);
    }

    private function createEntreprise(): Entreprise
    {
        return Entreprise::create([
            'entreprise_id' => 1,
            'nom' => 'Test Company',
        ]);
    }
}
