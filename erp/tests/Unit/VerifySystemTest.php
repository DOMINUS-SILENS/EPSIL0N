<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VerifySystemTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_runs_verification_without_errors()
    {
        Artisan::call('verify:system');
        $output = Artisan::output();
        $this->assertStringContainsString('Verification completed', $output);
    }
}
