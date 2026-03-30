<?php

namespace Tests\Unit\Models;

use App\Models\Contract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Contract Model Test Suite
 * 
 * Tests the Contract model including:
 * - Contract creation with predicates
 * - Active/inactive states
 * - Predicate class storage
 * 
 * @package Tests\Unit\Models
 * @covers \App\Models\Contract
 */
class ContractTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_create_active_contract(): void
    {
        $contract = Contract::create([
            'name' => 'CreditCheck',
            'predicate_class' => 'App\Contracts\Predicates\CreditCheck',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('contracts', [
            'name' => 'CreditCheck',
            'predicate_class' => 'App\Contracts\Predicates\CreditCheck',
        ]);
        $this->assertTrue($contract->is_active);
    }

    #[Test]
    public function it_can_deactivate_contract(): void
    {
        $contract = Contract::create([
            'name' => 'OldRule',
            'predicate_class' => 'App\Contracts\Predicates\OldRule',
            'is_active' => false,
        ]);

        $this->assertFalse($contract->is_active);
    }

    #[Test]
    public function it_uses_correct_table_name(): void
    {
        $contract = new Contract();
        $this->assertEquals('contracts', $contract->getTable());
    }

    #[Test]
    public function it_has_fillable_fields(): void
    {
        $contract = new Contract();
        $fillable = $contract->getFillable();

        $expected = ['name', 'predicate_class', 'is_active'];
        
        foreach ($expected as $field) {
            $this->assertContains($field, $fillable);
        }
    }
}
