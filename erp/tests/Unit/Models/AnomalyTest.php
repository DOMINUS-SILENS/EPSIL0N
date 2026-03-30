<?php

namespace Tests\Unit\Models;

use App\Models\Anomaly;
use App\Models\Entreprise;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Anomaly Model Test Suite
 * 
 * Tests the Anomaly model including:
 * - Anomaly detection recording
 * - Severity levels
 * - Resolution tracking
 * 
 * @package Tests\Unit\Models
 * @covers \App\Models\Anomaly
 */
class AnomalyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_record_anomaly(): void
    {
        $anomaly = Anomaly::create([
            'type' => 'stock_discrepancy',
            'context' => ['article_id' => 1, 'depot_id' => 1],
            'detected_at' => now(),
            'resolved' => false,
        ]);
    }

    #[Test]
    public function it_can_resolve_anomaly(): void
    {
        $anomaly = Anomaly::create([
            'type' => 'duplicate_entry',
            'context' => [],
            'detected_at' => now(),
            'is_resolved' => false,
        ]);

        $anomaly->update([
            'resolved' => true,
        ]);

        $this->assertTrue((bool)$anomaly->fresh()->resolved);
    }

    #[Test]
    public function it_stores_context_as_json(): void
    {
        $context = ['entity' => 'article', 'id' => 123, 'expected' => 100, 'actual' => 95];
        
        $anomaly = Anomaly::create([
            'type' => 'quantity_mismatch',
            'context' => $context,
            'detected_at' => now(),
        ]);

        $this->assertIsArray($anomaly->context);
        $this->assertEquals(123, $anomaly->context['id']);
    }

    #[Test]
    public function it_uses_correct_table_name(): void
    {
        $anomaly = new Anomaly();
        $this->assertEquals('anomalies', $anomaly->getTable());
    }
}
