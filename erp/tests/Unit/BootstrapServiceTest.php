<?php

namespace Tests\Unit;

use App\Services\BootstrapService;
use App\Services\HealthMonitor;
use App\Services\HealthReport;
use App\Services\SystemModeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BootstrapServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BootstrapService $bootstrap;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootstrap = app(BootstrapService::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_bootstraps_system()
    {
        // Mock HealthMonitor
        $healthReport = new HealthReport('NORMAL');
        $healthMonitor = Mockery::mock(HealthMonitor::class);
        $healthMonitor->shouldReceive('evaluate')->once()->andReturn($healthReport);
        $this->app->instance(HealthMonitor::class, $healthMonitor);
        // Mock SystemModeService
        $modeService = Mockery::mock(SystemModeService::class);
        $modeService->shouldReceive('setMode')->once()->with('NORMAL');
        $this->app->instance(SystemModeService::class, $modeService);

        // Bootstrap
        $this->bootstrap->boot();

        $this->assertTrue(true);
    }
}
