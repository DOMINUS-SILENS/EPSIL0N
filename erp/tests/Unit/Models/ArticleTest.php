<?php

namespace Tests\Unit;

use App\Models\Article;
use App\Models\ArticleFamille;
use App\Models\ArticleMarque;
use App\Models\Entreprise;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Article Model Test Suite
 * 
 * Tests the core Article (product) model including:
 * - Relationships (entreprise, famille, marque)
 * - Fillable fields validation
 * - Cast types (booleans)
 * - CRUD operations
 * 
 * @package Tests\Unit
 * @covers \App\Models\Article
 */
class ArticleTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_create_an_article(): void
    {
        $entreprise = $this->createEntreprise();
        
        $article = Article::create([
            'article_id' => 1,
            'entreprise_id' => $entreprise->entreprise_id,
            'article_designation' => 'Test Product',
            'article_abreviation' => 'TP',
            'active' => true,
            'is_stock_managed' => true,
            'archive' => false,
        ]);

        $this->assertDatabaseHas('article', [
            'article_id' => 1,
            'article_designation' => 'Test Product',
        ]);
        $this->assertTrue($article->active);
    }

    #[Test]
    public function it_belongs_to_entreprise(): void
    {
        $entreprise = $this->createEntreprise();
        $article = Article::create([
            'article_id' => 1,
            'entreprise_id' => $entreprise->entreprise_id,
            'article_designation' => 'Test Product',
        ]);

        $this->assertInstanceOf(Entreprise::class, $article->entreprise);
        $this->assertEquals($entreprise->entreprise_id, $article->entreprise->entreprise_id);
    }

    #[Test]
    public function it_belongs_to_famille(): void
    {
        $entreprise = $this->createEntreprise();
        $famille = ArticleFamille::create([
            'article_famille_id' => 1,
            'entreprise_id' => $entreprise->entreprise_id,
            'article_famille_designation' => 'Test Family',
            'active' => true,
        ]);

        $article = Article::create([
            'article_id' => 1,
            'entreprise_id' => $entreprise->entreprise_id,
            'article_famille_id' => $famille->article_famille_id,
            'article_designation' => 'Test Product',
        ]);

        $this->assertInstanceOf(ArticleFamille::class, $article->family);
        $this->assertEquals($famille->article_famille_id, $article->family->article_famille_id);
    }

    #[Test]
    public function it_belongs_to_marque(): void
    {
        $entreprise = $this->createEntreprise();
        $marque = ArticleMarque::create([
            'article_marque_id' => 1,
            'entreprise_id' => $entreprise->entreprise_id,
            'article_marque_designation' => 'Test Brand',
        ]);

        $article = Article::create([
            'article_id' => 1,
            'entreprise_id' => $entreprise->entreprise_id,
            'article_marque_id' => $marque->article_marque_id,
            'article_designation' => 'Test Product',
        ]);

        $this->assertInstanceOf(ArticleMarque::class, $article->brand);
        $this->assertEquals($marque->article_marque_id, $article->brand->article_marque_id);
    }

    #[Test]
    public function it_casts_boolean_fields(): void
    {
        $entreprise = $this->createEntreprise();
        
        $article = Article::create([
            'article_id' => 1,
            'entreprise_id' => $entreprise->entreprise_id,
            'article_designation' => 'Test',
            'active' => 1,
            'is_stock_managed' => 1,
            'archive' => 0,
            'article_manage_stock' => 1,
            'article_online_show' => 0,
        ]);

        $this->assertIsBool($article->active);
        $this->assertIsBool($article->is_stock_managed);
        $this->assertIsBool($article->archive);
        $this->assertIsBool($article->article_manage_stock);
        $this->assertIsBool($article->article_online_show);
        
        $this->assertTrue($article->active);
        $this->assertFalse($article->archive);
    }

    #[Test]
    public function it_has_correct_fillable_fields(): void
    {
        $article = new Article();
        $fillable = $article->getFillable();
        
        // Test all expected fillable fields exist
        $expected = [
            'article_id',
            'entreprise_id',
            'article_designation',
            'article_abreviation',
            'article_famille_id',
            'article_marque_id',
            'active',
            'is_stock_managed',
        ];
        
        foreach ($expected as $field) {
            $this->assertContains($field, $fillable, "Field {$field} should be fillable");
        }
    }

    #[Test]
    public function it_uses_correct_table_name(): void
    {
        $article = new Article();
        $this->assertEquals('article', $article->getTable());
    }

    #[Test]
    public function it_uses_correct_primary_key(): void
    {
        $article = new Article();
        $this->assertEquals('article_id', $article->getKeyName());
    }

    private function createEntreprise(): Entreprise
    {
        return Entreprise::create([
            'entreprise_id' => 1,
            'nom' => 'Test Company',
            'raison_sociale' => 'Test Company SA',
        ]);
    }
}
