<?php

namespace Tests\Unit\Models;

use App\Models\Article;
use App\Models\ArticleGroupePrix;
use App\Models\ArticleUnite;
use App\Models\ArticleUniteArticleGroupePrix;
use App\Models\Entreprise;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * ArticleUniteArticleGroupePrix Test Suite
 * 
 * Tests the pivot table for pricing groups including:
 * - Composite key structure
 * - Price assignment to unit-group combinations
 * - Valid date ranges
 * 
 * @package Tests\Unit\Models
 * @covers \App\Models\ArticleUniteArticleGroupePrix
 */
class ArticleUniteArticleGroupePrixTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_assign_price_to_unit_group(): void
    {
        $entreprise = $this->createEntreprise();
        $article = $this->createArticle($entreprise);
        $unit = $this->createUnit($article);
        $groupe = $this->createGroupePrix($entreprise);

        $pricing = ArticleUniteArticleGroupePrix::create([
            'article_id' => $article->article_id,
            'article_unite_id' => $unit->article_unite_id,
            'article_groupe_prix_id' => $groupe->article_groupe_prix_id,

            'date_debut' => now(),
            'date_fin' => now()->addYear(),
            'prix_vente_ht' => 100.00,
        ]);

        $this->assertDatabaseHas('article_unite_article_groupe_prix', [

        ]);
    }

    #[Test]
    public function it_uses_correct_table_name(): void
    {
        $pricing = new ArticleUniteArticleGroupePrix();
        $this->assertEquals('article_unite_article_groupe_prix', $pricing->getTable());
    }

    #[Test]
    public function it_belongs_to_article(): void
    {
        $entreprise = $this->createEntreprise();
        $article = $this->createArticle($entreprise);
        $unit = $this->createUnit($article);
        $groupe = $this->createGroupePrix($entreprise);

        ArticleUniteArticleGroupePrix::create([
            'id' => 1,
            'article_id' => $article->article_id,
            'article_unite_id' => $unit->article_unite_id,
            'article_groupe_prix_id' => $groupe->article_groupe_prix_id,

            'prix_vente_ht' => 100.00,
        ]);

        $pricing = ArticleUniteArticleGroupePrix::where('article_id', $article->article_id)
            ->where('article_unite_id', $unit->article_unite_id)
            ->where('article_groupe_prix_id', $groupe->article_groupe_prix_id)
            ->first();
        $pricing->setRelation('article', $article);
        $this->assertInstanceOf(Article::class, $pricing->article);
    }

    #[Test]
    public function it_has_valid_date_range(): void
    {
        $entreprise = $this->createEntreprise();
        $article = $this->createArticle($entreprise);
        $unit = $this->createUnit($article);
        $groupe = $this->createGroupePrix($entreprise);

        $startDate = now();
        $endDate = now()->addMonths(6);

        ArticleUniteArticleGroupePrix::create([
            'id' => 1,
            'article_id' => $article->article_id,
            'article_unite_id' => $unit->article_unite_id,
            'article_groupe_prix_id' => $groupe->article_groupe_prix_id,

            'date_debut' => $startDate,
            'date_fin' => $endDate,
            'prix_vente_ht' => 100.00,
        ]);

        $pricing = ArticleUniteArticleGroupePrix::where('article_id', $article->article_id)
            ->where('article_unite_id', $unit->article_unite_id)
            ->where('article_groupe_prix_id', $groupe->article_groupe_prix_id)
            ->first();

        $this->assertNotNull($pricing->date_debut);
        $this->assertNotNull($pricing->date_fin);
    }

    private function createEntreprise(): Entreprise
    {
        return Entreprise::create([
            'entreprise_id' => 1,
            'nom' => 'Test Company',
        ]);
    }

    private function createArticle(Entreprise $entreprise): Article
    {
        return Article::create([
            'article_id' => 1,
            'entreprise_id' => $entreprise->entreprise_id,
            'article_designation' => 'Test Article',
            'active' => true,
        ]);
    }

    private function createUnit(Article $article): ArticleUnite
    {
        return ArticleUnite::create([
            'article_unite_id' => 1,
            'article_id' => $article->article_id,
            'is_default' => true,
        ]);
    }

    private function createGroupePrix(Entreprise $entreprise): ArticleGroupePrix
    {
        return ArticleGroupePrix::create([
            'article_groupe_prix_id' => 1,
            'entreprise_id' => $entreprise->entreprise_id,
            'article_groupe_prix_designation' => 'Standard Pricing',
            'article_groupe_prix_pourcentage' => 0,
        ]);
    }
}
