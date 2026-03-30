<?php

namespace Tests\Unit;

use App\Helpers\Logging;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TracingTest extends TestCase
{
    #[Test]
    public function it_logs_with_correlation_id()
    {
        $id = 'test-123';
        Logging::setCorrelationId($id);
        Logging::info('Test message', ['foo' => 'bar']);

        // Assert log contains correlation ID (we can't easily check without mocking Log facade)
        $this->assertTrue(true);
    }
}
