<?php

namespace Tests\Unit;

use App\Models\Anomaly;
use App\Services\AdversarialMonitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdversarialMonitorTest extends TestCase
{
    use RefreshDatabase;

    protected AdversarialMonitor $monitor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->monitor = app(AdversarialMonitor::class);
        Cache::flush();
    }

    #[Test]
    public function it_detects_duplicate_command()
    {
        $this->monitor->recordCommand('order_create', ['customer' => 1], 'key-123');
        $this->assertEquals(0, Anomaly::count());

        $this->monitor->recordCommand('order_create', ['customer' => 1], 'key-123');
        $this->assertEquals(1, Anomaly::count());

        $anomaly = Anomaly::first();
        $this->assertEquals('duplicate_command', $anomaly->type);
        $this->assertEquals('order_create', $anomaly->context['command_type']);
        $this->assertEquals('key-123', $anomaly->context['idempotency_key']);
    }

    #[Test]
    public function test_it_detects_excessive_retries()
    {
        for ($i = 1; $i <= 10; $i++) {
            $this->monitor->checkRetryRate('order_create', 'user-1');
        }
        $this->assertEquals(0, Anomaly::count());

        $this->monitor->checkRetryRate('order_create', 'user-1');
        $this->assertEquals(1, Anomaly::count());

        $anomaly = Anomaly::first();
        $this->assertEquals('excessive_retry', $anomaly->type);
        $this->assertEquals('order_create', $anomaly->context['command_type']);
        $this->assertEquals('user-1', $anomaly->context['user_id']);
        $this->assertEquals(11, $anomaly->context['attempts']);
    }

    #[Test]
    public function it_respects_retry_rate_window()
    {
        // Do 11 attempts within 1 minute (all increments within window)
        for ($i = 1; $i <= 11; $i++) {
            $this->monitor->checkRetryRate('order_create', 'user-1');
        }
        $this->assertEquals(1, Anomaly::count());

        // After 61 seconds, the cache expires; next attempt resets counter
        sleep(61);
        $this->monitor->checkRetryRate('order_create', 'user-1');
        $this->assertEquals(1, Anomaly::count()); // still 1, no new anomaly
    }
}
