<?php

namespace Tests\Unit;

use App\Services\SequenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SequenceServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_returns_sequential_numbers_for_same_aggregate()
    {
        $service = app(SequenceService::class);
        $service->ensureExists('order', 1);

        $this->assertEquals(1, $service->next('order', 1));
        $this->assertEquals(2, $service->next('order', 1));
        $this->assertEquals(3, $service->next('order', 1));
    }

    #[Test]
    public function it_handles_different_aggregates_independently()
    {
        $service = app(SequenceService::class);
        $service->ensureExists('order', 1);
        $service->ensureExists('order', 2);

        $this->assertEquals(1, $service->next('order', 1));
        $this->assertEquals(1, $service->next('order', 2));
        $this->assertEquals(2, $service->next('order', 1));
    }

    #[Test]
    public function it_auto_creates_sequence_if_row_does_not_exist()
    {
        $service = app(SequenceService::class);
        $this->assertEquals(1, $service->next('order', 999)); // row missing initially
    }

    #[Test]
    public function it_is_atomic_under_concurrency()
    {
        $this->markTestSkipped('Requires concurrent testing setup.');
    }
}
