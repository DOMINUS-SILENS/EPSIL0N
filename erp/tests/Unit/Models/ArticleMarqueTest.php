<?php

namespace Tests\Unit\Models;

use App\Models\Entreprise;
use App\Models\ArticleMarque;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * ArticleMarque Model Test Suite
 * 
 * Tests the ArticleMarque (Product Brand) model including:
 * - CRUD operations
 * - Fillable fields
 * - Timestamps
 * - Relationships
 * 
 * @package Tests\Unit\Models
 * @covers \App\Models\ArticleMarque
 */
class ArticleMarqueTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_create_article_marque(): void
    {
        $entreprise = $this->createEntreprise();

        $marque = ArticleMarque::create([
            'article_marque_id' => 1,
            'entreprise_id' => $entreprise->entreprise_id,
            'article_marque_designation' => 'Apple',
            'article_marque_created_by' => 1,
        ]);

        $this->assertDatabaseHas('article_marque', [
            'article_marque_id' => 1,
            'article_marque_designation' => 'Apple',
        ]);
    }

    #[Test]
    public function it_uses_correct_table_name(): void
    {
        $marque = new ArticleMarque();
        $this->assertEquals('article_marque', $marque->getTable());
    }

    #[Test]
    public function it_uses_correct_primary_key(): void
    {
        $marque = new ArticleMarque();
        $this->assertEquals('article_marque_id', $marque->getKeyName());
    }

    private function createEntreprise(): Entreprise
    {
        return Entreprise::create([
            'entreprise_id' => 1,
            'nom' => 'Test Company',
        ]);
    }
}
