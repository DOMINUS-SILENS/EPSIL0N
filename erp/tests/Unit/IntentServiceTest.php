<?php

namespace Tests\Unit;

use App\Contracts\IntentVerifier;
use App\Models\Intent;
use App\Services\IntentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IntentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected IntentService $intentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->intentService = app(IntentService::class);
    }

    #[Test]
    public function it_verifies_intent_for_existing_verifier()
    {
        $verifierMock = Mockery::mock(IntentVerifier::class);
        $verifierMock->shouldReceive('verify')->once()->with(['items' => [1, 2]])->andReturn(true);

        $this->app->instance('App\Contracts\Verifiers\TestVerifier', $verifierMock);

        Intent::create([
            'command_type' => 'create_order',
            'verifier_class' => 'App\Contracts\Verifiers\TestVerifier',
            'is_active' => true,
        ]);

        $this->intentService->verify('create_order', ['items' => [1, 2]]);
        $this->assertTrue(true);
    }

    #[Test]
    public function it_throws_exception_when_intent_invalid()
    {
        $verifierMock = Mockery::mock(IntentVerifier::class);
        $verifierMock->shouldReceive('verify')->once()->with(['items' => []])->andReturn(false);

        $this->app->instance('App\Contracts\Verifiers\TestVerifier', $verifierMock);

        Intent::create([
            'command_type' => 'create_order',
            'verifier_class' => 'App\Contracts\Verifiers\TestVerifier',
            'is_active' => true,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Intent verification failed for command: create_order');

        $this->intentService->verify('create_order', ['items' => []]);
    }

    #[Test]
    public function it_skips_verification_if_no_intent_defined()
    {
        // No intent record for 'create_order'
        $this->intentService->verify('create_order', ['items' => []]);
        $this->assertTrue(true);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
