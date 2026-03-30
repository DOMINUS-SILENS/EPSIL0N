<?php

namespace Tests\Unit\Models;

use App\Models\ProjectionVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * ProjectionVersion Model Test Suite
 * 
 * Tests the ProjectionVersion model including:
 * - Version tracking for projectors
 * - Projector name as primary key
 * - Version number management
 * 
 * @package Tests\Unit\Models
 * @covers \App\Models\ProjectionVersion
 */
class ProjectionVersionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_create_projection_version(): void
    {
        $version = ProjectionVersion::create([
            'projector_name' => 'CustomerBalanceProjector',
            'version' => 1,
        ]);

        $this->assertDatabaseHas('projection_versions', [
            'projector_name' => 'CustomerBalanceProjector',
            'version' => 1,
        ]);
    }

    #[Test]
    public function it_tracks_version_increments(): void
    {
        $version = ProjectionVersion::create([
            'projector_name' => 'AccountProjector',
            'version' => 1,
        ]);

        // Increment version
        $version->update(['version' => 2]);
        $this->assertEquals(2, $version->fresh()->version);

        $version->update(['version' => 3]);
        $this->assertEquals(3, $version->fresh()->version);
    }

    #[Test]
    public function it_has_multiple_projectors(): void
    {
        // Create versions for different projectors
        ProjectionVersion::create([
            'projector_name' => 'CustomerProjector',
            'version' => 1,
        ]);

        ProjectionVersion::create([
            'projector_name' => 'OrderProjector',
            'version' => 5,
        ]);

        ProjectionVersion::create([
            'projector_name' => 'SalesProjector',
            'version' => 3,
        ]);

        $this->assertCount(3, ProjectionVersion::all());
        $this->assertEquals(5, ProjectionVersion::where('projector_name', 'OrderProjector')->first()->version);
    }

    #[Test]
    public function it_uses_correct_table_name(): void
    {
        $version = new ProjectionVersion();
        $this->assertEquals('projection_versions', $version->getTable());
    }

    #[Test]
    public function it_uses_projector_name_as_primary_key(): void
    {
        $version = new ProjectionVersion();
        $this->assertEquals('projector_name', $version->getKeyName());
    }
}
