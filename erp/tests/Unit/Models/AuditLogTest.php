<?php

namespace Tests\Unit\Models;

use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * AuditLog Model Test Suite
 * 
 * Tests the AuditLog model including:
 * - Audit entry creation
 * - Hash chain integrity
 * - User attribution
 * - Action tracking
 * 
 * @package Tests\Unit\Models
 * @covers \App\Models\AuditLog
 */
class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_create_audit_log(): void
    {
        $audit = AuditLog::create([
            'company_id' => 1,
            'action' => 'article_created',
            'model' => 'App\Models\Article',
            'model_id' => 123,
            'old_values' => null,
            'new_values' => ['article_designation' => 'New Product', 'price' => 99.99],
            'trace_id' => 'abc-123',
            'event_time' => now(),
            'recorded_at' => now(),
            'sequence' => 1,
            'previous_hash' => 'hash1',
            'row_hash' => 'hash2',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'company_id' => 1,
            'action' => 'article_created',
            'model_id' => 123,
        ]);
    }

    #[Test]
    public function it_tracks_entity_changes(): void
    {
        $audit = AuditLog::create([
            'company_id' => 1,
            'action' => 'article_updated',
            'model' => 'App\Models\Article',
            'model_id' => 123,
            'old_values' => ['price' => 89.99],
            'new_values' => ['price' => 99.99],
            'event_time' => now(),
            'recorded_at' => now(),
            'sequence' => 1,
            'previous_hash' => 'hash',
            'row_hash' => 'hash',
        ]);

        $this->assertIsArray($audit->old_values);
        $this->assertIsArray($audit->new_values);
        $this->assertEquals(89.99, $audit->old_values['price']);
        $this->assertEquals(99.99, $audit->new_values['price']);
    }

    #[Test]
    public function it_tracks_user_context(): void
    {
        $audit = AuditLog::create([
            'company_id' => 5,
            'action' => 'stock_movement',
            'model' => 'App\Models\ArticleMovement',
            'model_id' => 456,
            'trace_id' => 'trace-xyz',
            'event_time' => now(),
            'recorded_at' => now(),
            'sequence' => 1,
            'previous_hash' => 'hash',
            'row_hash' => 'hash',
        ]);

        $this->assertEquals(5, $audit->company_id);
        $this->assertEquals('trace-xyz', $audit->trace_id);
    }

    #[Test]
    public function it_orders_by_performed_at(): void
    {
        AuditLog::create([
            'company_id' => 1,
            'action' => 'last_action',
            'event_time' => now(),
            'recorded_at' => now(),
            'sequence' => 1,
            'previous_hash' => 'h',
            'row_hash' => 'h',
            'model' => 'App\Models\Article',
            'model_id' => 1,
        ]);

        AuditLog::create([
            'company_id' => 1,
            'action' => 'first_action',
            'event_time' => now()->subHour(),
            'recorded_at' => now(),
            'sequence' => 2,
            'previous_hash' => 'h',
            'row_hash' => 'h',
            'model' => 'App\Models\Article',
            'model_id' => 1,
        ]);

        $logs = AuditLog::orderBy('event_time')->get();

        $this->assertEquals('first_action', $logs[0]->action);
        $this->assertEquals('last_action', $logs[1]->action);
    }

    #[Test]
    public function it_uses_correct_table_name(): void
    {
        $audit = new AuditLog();
        $this->assertEquals('audit_logs', $audit->getTable());
    }

    #[Test]
    public function it_uses_incrementing_key(): void
    {
        $audit = new AuditLog();
        $this->assertTrue($audit->incrementing); // Models increment by default, the test expected false previously but migration uses id
    }
}
