<?php

namespace Tests\Unit\Models;

use App\Models\Article;
use App\Models\ArticleUnite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * ArticleUnite Model Test Suite
 * 
 * Tests the Article-ArticleUnit (PCU - Product Control Unit) model including:
 * - Relationships (article, prices)
 * - Default unit management
 * - Dimension and weight tracking
 * - Boolean casts for pricing flags
 * 
 * @package Tests\Unit\Models
 * @covers \App\Models\ArticleUnite
 */
class ArticleUniteTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_create_article_unit(): void
    {
        $entreprise = $this->createEntreprise();
        $article = $this->createArticle($entreprise);

        $unit = ArticleUnite::create([
            'article_unite_id' => 1,
            'article_id' => $article->article_id,
            'article_bar_code' => '123456789',
            'article_prix_vente' => 99.99,
            'article_unite_quantite' => 1,
            'is_default' => 1,
            'active' => 1,
        ]);

        $this->assertDatabaseHas('article_unite', [
            'article_unite_id' => 1,
            'article_bar_code' => '123456789',
        ]);
    }

    #[Test]
    public function it_tracks_dimensions_and_weight(): void
    {
        $entreprise = $this->createEntreprise();
        $article = $this->createArticle($entreprise);

        $unit = ArticleUnite::create([
            'article_unite_id' => 1,
            'article_id' => $article->article_id,
            'article_poids' => 2.5,
            'article_volume' => 0.05,
            'article_longueur' => 30.0,
            'article_largeur' => 20.0,
            'article_hauteur' => 15.0,
        ]);

        $this->assertEquals(2.5, $unit->article_poids);
        $this->assertEquals(0.05, $unit->article_volume);
        $this->assertEquals(30.0, $unit->article_longueur);
    }

    #[Test]
    public function it_manages_pricing_information(): void
    {
        $entreprise = $this->createEntreprise();
        $article = $this->createArticle($entreprise);

        $unit = ArticleUnite::create([
            'article_unite_id' => 1,
            'article_id' => $article->article_id,
            'article_prix_revient' => 50.00,
            'article_prix_achat_moyen' => 55.00,
            'article_prix_vente' => 99.99,
            'article_prix_min_autorise' => 89.99,
            'article_prix_online_prix' => 95.00,
            'article_prix_online_show' => 1,
            'is_article_prix_change_autorised' => 0,
        ]);

        $this->assertEquals(99.99, $unit->article_prix_vente);
        $this->assertTrue($unit->article_prix_online_show);
        $this->assertFalse($unit->is_article_prix_change_autorised);
    }

    #[Test]
    public function it_casts_boolean_fields(): void
    {
        $entreprise = $this->createEntreprise();
        $article = $this->createArticle($entreprise);

        $unit = ArticleUnite::create([
            'article_unite_id' => 1,
            'article_id' => $article->article_id,
            'active' => 1,
            'is_default' => 1,
            'is_article_prix_change_autorised' => 1,
            'article_prix_online_show' => 0,
        ]);

        $this->assertIsBool($unit->active);
        $this->assertIsBool($unit->is_default);
        $this->assertTrue($unit->active);
        $this->assertTrue($unit->is_default);
    }

    #[Test]
    public function it_belongs_to_article(): void
    {
        $entreprise = $this->createEntreprise();
        $article = $this->createArticle($entreprise);

        $unit = ArticleUnite::create([
            'article_unite_id' => 1,
            'article_id' => $article->article_id,
        ]);

        $this->assertInstanceOf(Article::class, $unit->article);
        $this->assertEquals($article->article_id, $unit->article->article_id);
    }

    #[Test]
    public function it_uses_correct_table_name(): void
    {
        $unit = new ArticleUnite();
        $this->assertEquals('article_unite', $unit->getTable());
    }

    #[Test]
    public function it_uses_correct_primary_key(): void
    {
        $unit = new ArticleUnite();
        $this->assertEquals('article_unite_id', $unit->getKeyName());
    }

    private function createEntreprise(): \App\Models\Entreprise
    {
        return \App\Models\Entreprise::create([
            'entreprise_id' => 1,
            'nom' => 'Test Company',
        ]);
    }

    private function createArticle(\App\Models\Entreprise $entreprise): Article
    {
        return Article::create([
            'article_id' => 1,
            'entreprise_id' => $entreprise->entreprise_id,
            'article_designation' => 'Test Article',
            'active' => true,
        ]);
    }
}
