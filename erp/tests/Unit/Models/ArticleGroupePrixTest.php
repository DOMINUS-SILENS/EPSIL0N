<?php

namespace Tests\Unit\Models;

use App\Models\Entreprise;
use App\Models\ArticleGroupePrix;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * ArticleGroupePrix Model Test Suite
 * 
 * Tests the ArticleGroupePrix (Pricing Group) model including:
 * - CRUD operations
 * - Fillable fields
 * - Relationship with entreprise
 * 
 * @package Tests\Unit\Models
 * @covers \App\Models\ArticleGroupePrix
 */
class ArticleGroupePrixTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_create_pricing_group(): void
    {
        $entreprise = $this->createEntreprise();

        $groupe = ArticleGroupePrix::create([
            'article_groupe_prix_id' => 1,
            'entreprise_id' => $entreprise->entreprise_id,
            'article_groupe_prix_designation' => 'Retail Customers',
            'article_groupe_prix_created_by' => 1,
            'article_groupe_prix_pourcentage' => 0,
        ]);

        $this->assertDatabaseHas('article_groupe_prix', [
            'article_groupe_prix_id' => 1,
            'article_groupe_prix_designation' => 'Retail Customers',
        ]);
    }

    #[Test]
    public function it_belongs_to_entreprise(): void
    {
        $entreprise = $this->createEntreprise();

        $groupe = ArticleGroupePrix::create([
            'article_groupe_prix_id' => 1,
            'entreprise_id' => $entreprise->entreprise_id,
            'article_groupe_prix_designation' => 'Wholesale',
            'article_groupe_prix_pourcentage' => 0,
        ]);

        $this->assertInstanceOf(Entreprise::class, $groupe->entreprise);
        $this->assertEquals($entreprise->entreprise_id, $groupe->entreprise->entreprise_id);
    }

    #[Test]
    public function it_uses_correct_table_name(): void
    {
        $groupe = new ArticleGroupePrix();
        $this->assertEquals('article_groupe_prix', $groupe->getTable());
    }

    #[Test]
    public function it_uses_correct_primary_key(): void
    {
        $groupe = new ArticleGroupePrix();
        $this->assertEquals('article_groupe_prix_id', $groupe->getKeyName());
    }

    private function createEntreprise(): Entreprise
    {
        return Entreprise::create([
            'entreprise_id' => 1,
            'nom' => 'Test Company',
        ]);
    }
}
