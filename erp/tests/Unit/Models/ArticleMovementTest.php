<?php

namespace Tests\Unit\Models;

use App\Models\Article;
use App\Models\ArticleMovement;
use App\Models\Depot;
use App\Models\Entreprise;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * ArticleMovement Model Test Suite
 * 
 * Tests the ArticleMovement (stock movement) model including:
 * - Relationships (article, source depot, destination depot)
 * - Fillable fields (108 fields)
 * - Cast types validation
 * - Complex movement scenarios
 * 
 * @package Tests\Unit\Models
 * @covers \App\Models\ArticleMovement
 */
class ArticleMovementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_create_stock_entry_movement(): void
    {
        $entreprise = $this->createEntreprise();
        $article = $this->createArticle($entreprise);
        $depot = $this->createDepot($entreprise);

        $movement = ArticleMovement::create([
            'article_mouvement_id' => 1,
            'article_id' => $article->article_id,
            'depot_id_destination' => $depot->depot_id,
            'article_mouvement_quantite' => 100,
            'quantite' => 100,
            'article_mouvement_quantite_restante' => 100,
            'stock_operation_type' => 1, // Entry
            'article_mouvement_date' => now(),
            'article_mouvement_unite_id' => $this->createUnit($article)->article_unite_id,
        ]);

        $this->assertDatabaseHas('article_mouvement', [
            'article_mouvement_id' => 1,
            'article_id' => $article->article_id,
            'article_mouvement_quantite' => 100,
            'article_mouvement_quantite_restante' => 100,
        ]);
        $this->assertEquals(1, $movement->stock_operation_type);
    }

    #[Test]
    public function it_can_create_stock_exit_movement(): void
    {
        $entreprise = $this->createEntreprise();
        $article = $this->createArticle($entreprise);
        $depot = $this->createDepot($entreprise);

        $movement = ArticleMovement::create([
            'article_mouvement_id' => 1,
            'article_id' => $article->article_id,
            'depot_id_source' => $depot->depot_id,
            'article_mouvement_quantite' => 50,
            'article_mouvement_quantite_restante' => 50,
            'stock_operation_type' => 2,
        ]);

        $this->assertEquals(2, $movement->stock_operation_type);
    }

    #[Test]
    public function it_can_create_transfer_movement(): void
    {
        $entreprise = $this->createEntreprise();
        $article = $this->createArticle($entreprise);
        $sourceDepot = $this->createDepot($entreprise, 'Source Depot');
        $destDepot = $this->createDepot($entreprise, 'Destination Depot', 2);

        $movement = ArticleMovement::create([
            'article_mouvement_id' => 1,
            'article_id' => $article->article_id,
            'depot_id_source' => $sourceDepot->depot_id,
            'depot_id_destination' => $destDepot->depot_id,
            'article_mouvement_quantite' => 75,
            'article_mouvement_quantite_restante' => 75,
            'stock_operation_type' => 3, // Transfer
            'article_mouvement_date' => now(),
            'article_mouvement_unite_id' => $this->createUnit($article)->article_unite_id,
        ]);

        $this->assertEquals(3, $movement->stock_operation_type);
        $this->assertNotNull($movement->sourceDepot);
        $this->assertNotNull($movement->destinationDepot);
    }

    #[Test]
    public function it_belongs_to_article(): void
    {
        $entreprise = $this->createEntreprise();
        $article = $this->createArticle($entreprise);
        $depot = $this->createDepot($entreprise);

        $movement = ArticleMovement::create([
            'article_mouvement_id' => 1,
            'article_id' => $article->article_id,
            'depot_id_destination' => $depot->depot_id,
            'article_mouvement_quantite' => 100,
            'article_mouvement_quantite_restante' => 100,
            'stock_operation_type' => 1,
            'article_mouvement_unite_id' => $this->createUnit($article)->article_unite_id,
        ]);

        $this->assertInstanceOf(Article::class, $movement->article);
        $this->assertEquals($article->article_id, $movement->article->article_id);
    }

    #[Test]
    public function it_belongs_to_source_depot(): void
    {
        $entreprise = $this->createEntreprise();
        $article = $this->createArticle($entreprise);
        $sourceDepot = $this->createDepot($entreprise, 'Source');
        $destDepot = $this->createDepot($entreprise, 'Dest', 2);

        $movement = ArticleMovement::create([
            'article_mouvement_id' => 1,
            'article_id' => $article->article_id,
            'depot_id_source' => $sourceDepot->depot_id,
            'depot_id_destination' => $destDepot->depot_id,
            'article_mouvement_quantite' => 50,
            'article_mouvement_quantite_restante' => 50,
            'stock_operation_type' => 3,
            'article_mouvement_unite_id' => $this->createUnit($article)->article_unite_id,
        ]);

        $this->assertInstanceOf(Depot::class, $movement->sourceDepot);
        $this->assertEquals($sourceDepot->depot_id, $movement->sourceDepot->depot_id);
    }

    #[Test]
    public function it_belongs_to_destination_depot(): void
    {
        $entreprise = $this->createEntreprise();
        $article = $this->createArticle($entreprise);
        $sourceDepot = $this->createDepot($entreprise, 'Source');
        $destDepot = $this->createDepot($entreprise, 'Dest', 2);

        $movement = ArticleMovement::create([
            'article_mouvement_id' => 1,
            'article_id' => $article->article_id,
            'depot_id_source' => $sourceDepot->depot_id,
            'depot_id_destination' => $destDepot->depot_id,
            'article_mouvement_quantite' => 50,
            'article_mouvement_quantite_restante' => 50,
            'stock_operation_type' => 3,
            'article_mouvement_unite_id' => $this->createUnit($article)->article_unite_id,
        ]);

        $this->assertInstanceOf(Depot::class, $movement->destinationDepot);
        $this->assertEquals($destDepot->depot_id, $movement->destinationDepot->depot_id);
    }

    #[Test]
    public function it_uses_integer_quantities(): void
    {
        $entreprise = $this->createEntreprise();
        $article = $this->createArticle($entreprise);
        $depot = $this->createDepot($entreprise);

        $movement = ArticleMovement::create([
            'article_mouvement_id' => 1,
            'article_id' => $article->article_id,
            'depot_id_destination' => $depot->depot_id,
            'article_mouvement_quantite' => 10050,
            'article_mouvement_quantite_restante' => 10050,
            'article_mouvement_quantite_totale' => 10050,
            'stock_operation_type' => 1,
            'article_mouvement_unite_id' => $this->createUnit($article)->article_unite_id,
        ]);

        $this->assertIsInt($movement->article_mouvement_quantite);
        $this->assertIsInt($movement->article_mouvement_quantite_restante);
        $this->assertIsInt($movement->article_mouvement_quantite_totale);
    }

    #[Test]
    public function it_casts_booleans(): void
    {
        $entreprise = $this->createEntreprise();
        $article = $this->createArticle($entreprise);
        $depot = $this->createDepot($entreprise);

        $movement = ArticleMovement::create([
            'article_mouvement_id' => 1,
            'article_id' => $article->article_id,
            'depot_id_destination' => $depot->depot_id,
            'article_mouvement_quantite' => 100,
            'article_mouvement_quantite_restante' => 100,
            'stock_operation_type' => 1,
            'is_packaged' => 1,
            'archived' => 0,
            'ressource' => 1,
            'logistique_is_delivered' => 0,
            'logistique_deliver_validated_customer' => 0,
            'logistique_customer_notified' => 0,
            'article_mouvement_show_quantity' => 1,
            'article_mouvement_show_supplier' => 0,
            'article_mouvement_hidden' => 0,
            'article_mouvement_unite_id' => $this->createUnit($article)->article_unite_id,
        ]);

        $this->assertIsBool($movement->is_packaged);
        $this->assertIsBool($movement->archived);
        $this->assertIsBool($movement->ressource);
        $this->assertTrue($movement->is_packaged);
        $this->assertFalse($movement->archived);
    }

    #[Test]
    public function it_uses_correct_table_name(): void
    {
        $movement = new ArticleMovement();
        $this->assertEquals('article_mouvement', $movement->getTable());
    }

    #[Test]
    public function it_has_all_required_fillable_fields(): void
    {
        $movement = new ArticleMovement();
        $fillable = $movement->getFillable();
        
        $expectedFields = [
            'article_mouvement_id',
            'article_id',
            'depot_id_source',
            'depot_id_destination',
            'article_mouvement_quantite',
            'article_mouvement_quantite_restante',
            'stock_operation_type',
            'is_packaged',
            'archived',
        ];

        foreach ($expectedFields as $field) {
            $this->assertContains($field, $fillable);
        }
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

    private function createDepot(Entreprise $entreprise, string $name = 'Test Depot', int $id = 1): Depot
    {
        return Depot::create([
            'depot_id' => $id,
            'entreprise_id' => $entreprise->entreprise_id,
            'depot_designation' => $name,
        ]);
    }

    private function createUnit(Article $article): \App\Models\ArticleUnite
    {
        return \App\Models\ArticleUnite::create([
            'article_unite_id' => 1,
            'article_id' => $article->article_id,
            'is_default' => true,
        ]);
    }
}
