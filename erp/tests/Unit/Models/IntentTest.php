<?php

namespace Tests\Unit\Models;

use App\Models\Intent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Intent Model Test Suite
 * 
 * Tests the Intent model including:
 * - Intent registration
 * - Active/inactive states
 * - Verifier class association
 * 
 * @package Tests\Unit\Models
 * @covers \App\Models\Intent
 */
class IntentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_create_intent(): void
    {
        $intent = Intent::create([
            'command_type' => 'create_order',
            'verifier_class' => 'App\Intents\OrderIntentVerifier',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('intents', [
            'command_type' => 'create_order',
            'verifier_class' => 'App\Intents\OrderIntentVerifier',
        ]);
    }

    #[Test]
    public function it_can_deactivate_intent(): void
    {
        $intent = Intent::create([
            'command_type' => 'delete_order',
            'verifier_class' => 'App\Intents\DeleteIntentVerifier',
            'is_active' => false,
        ]);

        $this->assertFalse($intent->is_active);
    }

    #[Test]
    public function it_uses_correct_table_name(): void
    {
        $intent = new Intent();
        $this->assertEquals('intents', $intent->getTable());
    }

    #[Test]
    public function it_has_fillable_fields(): void
    {
        $intent = new Intent();
        $fillable = $intent->getFillable();

        $this->assertContains('command_type', $fillable);
        $this->assertContains('verifier_class', $fillable);
        $this->assertContains('is_active', $fillable);
    }
}
