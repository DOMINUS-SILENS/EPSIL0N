<?php

namespace Tests\Unit\Models;

use App\Models\ArticleFamille;
use App\Models\Entreprise;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * ArticleFamille Model Test Suite
 * 
 * Tests the ArticleFamille (Product Family) model including:
 * - Hierarchical structure with nested set
 * - Fillable fields
 * - Boolean casts
 * - Online display settings
 * 
 * @package Tests\Unit\Models
 * @covers \App\Models\ArticleFamille
 */
class ArticleFamilleTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_create_article_famille(): void
    {
        $entreprise = $this->createEntreprise();

        $famille = ArticleFamille::create([
            'article_famille_id' => 1,
            'entreprise_id' => $entreprise->entreprise_id,
            'article_famille_designation' => 'Electronics',
            'active' => true,
            'famille_codification' => 'ELEC',
        ]);

        $this->assertDatabaseHas('article_famille', [
            'article_famille_id' => 1,
            'article_famille_designation' => 'Electronics',
        ]);
    }

    #[Test]
    public function it_supports_hierarchical_structure(): void
    {
        $entreprise = $this->createEntreprise();

        $parentFamille = ArticleFamille::create([
            'article_famille_id' => 1,
            'entreprise_id' => $entreprise->entreprise_id,
            'article_famille_designation' => 'Electronics',
            'article_famille_parent_left' => 1,
            'article_famille_parent_right' => 4,
            'active' => true,
        ]);

        $childFamille = ArticleFamille::create([
            'article_famille_id' => 2,
            'entreprise_id' => $entreprise->entreprise_id,
            'article_famille_designation' => 'Smartphones',
            'article_famille_parent_id' => $parentFamille->article_famille_id,
            'article_famille_parent_left' => 2,
            'article_famille_parent_right' => 3,
            'active' => true,
        ]);

        $this->assertEquals($parentFamille->article_famille_id, $childFamille->article_famille_parent_id);
    }

    #[Test]
    public function it_manages_online_display(): void
    {
        $entreprise = $this->createEntreprise();

        $famille = ArticleFamille::create([
            'article_famille_id' => 1,
            'entreprise_id' => $entreprise->entreprise_id,
            'article_famille_designation' => 'Featured Products',
            'article_famille_online_show' => true,
            'article_famille_online_description' => 'Our best products',
            'active' => true,
        ]);

        $this->assertTrue($famille->article_famille_online_show);
        $this->assertEquals('Our best products', $famille->article_famille_online_description);
    }

    #[Test]
    public function it_uses_correct_table_name(): void
    {
        $famille = new ArticleFamille();
        $this->assertEquals('article_famille', $famille->getTable());
    }

    private function createEntreprise(): Entreprise
    {
        return Entreprise::create([
            'entreprise_id' => 1,
            'nom' => 'Test Company',
        ]);
    }
}
