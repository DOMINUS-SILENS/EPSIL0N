<?php

namespace Tests\Unit;

use App\Services\SystemModeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SystemModeServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SystemModeService $modeService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        // Only insert if the table is empty (migration may have seeded it)
        if (DB::table('system_modes')->count() === 0) {
            DB::table('system_modes')->insert([
                ['mode' => 'NORMAL', 'is_active' => true],
                ['mode' => 'DEGRADED', 'is_active' => false],
                ['mode' => 'SAFE_HALT', 'is_active' => false],
            ]);
        }
        $this->modeService = app(SystemModeService::class);
    }

    #[Test]
    public function it_returns_current_mode()
    {
        $this->assertEquals('NORMAL', $this->modeService->getCurrentMode());
    }

    #[Test]
    public function it_can_change_mode()
    {
        $this->modeService->setMode('DEGRADED');
        $this->assertEquals('DEGRADED', $this->modeService->getCurrentMode());
    }

    #[Test]
    public function it_checks_mode_correctly()
    {
        $this->assertTrue($this->modeService->isMode('NORMAL'));
        $this->assertFalse($this->modeService->isMode('DEGRADED'));
    }

    #[Test]
    public function it_allows_processing_based_on_mode()
    {
        // NORMAL: everything allowed
        $this->assertTrue($this->modeService->canProcess('p0_financial'));
        $this->assertTrue($this->modeService->canProcess('p1_stock'));
        $this->assertTrue($this->modeService->canProcess('p2_projections'));

        // DEGRADED: only P0 allowed
        $this->modeService->setMode('DEGRADED');
        $this->assertTrue($this->modeService->canProcess('p0_financial'));
        $this->assertFalse($this->modeService->canProcess('p1_stock'));
        $this->assertFalse($this->modeService->canProcess('p2_projections'));

        // SAFE_HALT: nothing allowed
        $this->modeService->setMode('SAFE_HALT');
        $this->assertFalse($this->modeService->canProcess('p0_financial'));
    }
}
