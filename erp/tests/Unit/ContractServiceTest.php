<?php

namespace Tests\Unit;

use App\Contracts\Predicate;
use App\Models\Contract;
use App\Services\ContractService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContractServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ContractService $contractService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->contractService = app(ContractService::class);
    }

    #[Test]
    public function it_verifies_all_active_contracts()
    {
        // Create a contract with a mock predicate that returns true
        $predicateMock = Mockery::mock(Predicate::class);
        $predicateMock->shouldReceive('evaluate')->once()->with(['customer_id' => 1])->andReturn(true);

        $this->app->instance('App\Contracts\Predicates\TestPredicate', $predicateMock);

        Contract::create([
            'name' => 'TestContract',
            'predicate_class' => 'App\Contracts\Predicates\TestPredicate',
            'is_active' => true,
        ]);

        $this->contractService->verify(['customer_id' => 1]);
        $this->assertTrue(true); // no exception
    }

    #[Test]
    public function it_throws_exception_when_contract_fails()
    {
        $predicateMock = Mockery::mock(Predicate::class);
        $predicateMock->shouldReceive('evaluate')->once()->with(['customer_id' => 1])->andReturn(false);

        $this->app->instance('App\Contracts\Predicates\TestPredicate', $predicateMock);

        Contract::create([
            'name' => 'TestContract',
            'predicate_class' => 'App\Contracts\Predicates\TestPredicate',
            'is_active' => true,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Contract failed: App\Contracts\Predicates\TestPredicate');

        $this->contractService->verify(['customer_id' => 1]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
