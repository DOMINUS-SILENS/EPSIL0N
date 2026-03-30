<?php

namespace Tests\Unit;

use App\Services\MetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * MetricsService Test Suite
 * 
 * Tests the MetricsService which handles:
 * - Metric counters (increment, get)
 * - Attention budget tracking
 * - Cache-based metrics storage
 * 
 * Used for system monitoring and rate limiting.
 * 
 * @package Tests\Unit
 * @covers \App\Services\MetricsService
 */
class MetricsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected MetricsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MetricsService::class);
        Cache::flush();
    }

    #[Test]
    public function it_can_increment_metric(): void
    {
        $this->service->increment('test_metric');
        
        $this->assertEquals(1, $this->service->get('test_metric'));
    }

    #[Test]
    public function it_can_increment_metric_by_custom_amount(): void
    {
        $this->service->increment('test_metric', 5);
        
        $this->assertEquals(5, $this->service->get('test_metric'));
    }

    #[Test]
    public function it_returns_zero_for_unknown_metric(): void
    {
        $value = $this->service->get('non_existent_metric');
        
        $this->assertEquals(0, $value);
    }

    #[Test]
    public function it_tracks_attention_budget(): void
    {
        // First call should succeed
        $result1 = $this->service->recordAttention();
        $this->assertTrue($result1);
        
        // Budget should be incremented
        $this->assertEquals(1, Cache::get('attention_budget'));
    }

    #[Test]
    public function it_limits_attention_budget(): void
    {
        // Fill up the budget
        for ($i = 0; $i < 1000; $i++) {
            $this->service->recordAttention();
        }
        
        // 1001st call should fail
        $result = $this->service->recordAttention();
        $this->assertFalse($result);
    }

    #[Test]
    public function it_accumulates_multiple_metrics_independently(): void
    {
        $this->service->increment('orders', 10);
        $this->service->increment('visits', 100);
        $this->service->increment('errors', 2);
        
        $this->assertEquals(10, $this->service->get('orders'));
        $this->assertEquals(100, $this->service->get('visits'));
        $this->assertEquals(2, $this->service->get('errors'));
    }

    #[Test]
    public function it_handles_concurrent_increments(): void
    {
        // Simulate concurrent increments
        $this->service->increment('concurrent_metric');
        $this->service->increment('concurrent_metric');
        $this->service->increment('concurrent_metric');
        
        $this->assertEquals(3, $this->service->get('concurrent_metric'));
    }
}
