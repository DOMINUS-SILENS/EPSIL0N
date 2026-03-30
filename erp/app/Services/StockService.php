<?php

namespace App\Services;

use App\Models\Article;
use App\Models\ArticleUnite;
use App\Models\ArticleMovement;
use App\Models\Depot;
use App\Models\DomainOutbox;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;
use RuntimeException;

class StockService
{
    public function __construct(
        protected OutboxService $outboxService
    ) {}

    /**
     * Create a stock entry movement.
     *
     * @return DomainOutbox
     *
     * @throws InvalidArgumentException
     */
    public function createEntry(
        int $articleId,
        int $depotId,
        float $quantity,
        int $uniteId,
        array $additionalData = []
        )
    {
        $this->validateArticle($articleId);
        $this->validateDepot($depotId);
        $this->validateQuantity($quantity);

        return $this->outboxService->publishDomain(
            'stock',
            $articleId,
            'stock.entered',
        [
            'article_id' => $articleId,
            'depot_id_destination' => $depotId,
            'quantity' => $quantity,
            'unite_id' => $uniteId,
            'additional_data' => $additionalData,
        ]
        );
    }

    /**
     * Create a stock exit movement.
     *
     * @return DomainOutbox
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function createExit(
        int $articleId,
        int $depotId,
        float $quantity,
        int $uniteId,
        array $additionalData = []
        )
    {
        $this->validateArticle($articleId);
        $this->validateDepot($depotId);
        $this->validateQuantity($quantity);

        $availableStock = $this->getAvailableStock($articleId, $depotId);
        if ($availableStock < $quantity) {
            throw new RuntimeException(
                "Insufficient stock for article {$articleId} in depot {$depotId}. " .
                "Available: {$availableStock}, Requested: {$quantity}"
                );
        }

        return $this->outboxService->publishDomain(
            'stock',
            $articleId,
            'stock.exited',
        [
            'article_id' => $articleId,
            'depot_id_source' => $depotId,
            'quantity' => $quantity,
            'unite_id' => $uniteId,
            'additional_data' => $additionalData,
        ]
        );
    }

    /**
     * Create a stock transfer between depots.
     *
     * @return DomainOutbox
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function createTransfer(
        int $articleId,
        int $sourceDepotId,
        int $destinationDepotId,
        float $quantity,
        int $uniteId,
        array $additionalData = []
        )
    {
        if ($sourceDepotId === $destinationDepotId) {
            throw new InvalidArgumentException('Source and destination depots must be different');
        }

        $this->validateArticle($articleId);
        $this->validateDepot($sourceDepotId);
        $this->validateDepot($destinationDepotId);
        $this->validateQuantity($quantity);

        $availableStock = $this->getAvailableStock($articleId, $sourceDepotId);
        if ($availableStock < $quantity) {
            throw new RuntimeException(
                "Insufficient stock for article {$articleId} in source depot {$sourceDepotId}. " .
                "Available: {$availableStock}, Requested: {$quantity}"
                );
        }

        return $this->outboxService->publishDomain(
            'stock',
            $articleId,
            'stock.transferred',
        [
            'article_id' => $articleId,
            'depot_id_source' => $sourceDepotId,
            'depot_id_destination' => $destinationDepotId,
            'quantity' => $quantity,
            'unite_id' => $uniteId,
            'additional_data' => $additionalData,
        ]
        );
    }

    /**
     * Get available stock quantity for an article in a depot.
     */
    public function getAvailableStock(int $articleId, int $depotId): float
    {
        return ArticleMovement::where('article_id', $articleId)
            ->where(function ($query) use ($depotId) {
            $query->where('depot_id_destination', $depotId)
                ->orWhere('depot_id_source', $depotId);
        })
            ->sum('article_mouvement_quantite_restante') ?? 0;
    }

    /**
     * Get stock movements for an article.
     *
     * @return Collection
     */
    public function getMovements(int $articleId, ?int $depotId = null, int $limit = 100)
    {
        $query = ArticleMovement::where('article_id', $articleId)
            ->with(['article', 'sourceDepot', 'destinationDepot'])
            ->orderBy('article_mouvement_date', 'desc')
            ->limit($limit);

        if ($depotId !== null) {
            $query->where(function ($q) use ($depotId) {
                $q->where('depot_id_source', $depotId)
                    ->orWhere('depot_id_destination', $depotId);
            });
        }

        return $query->get();
    }

    /**
     * Validate article exists.
     *
     * @throws InvalidArgumentException
     */
    protected function validateArticle(int $articleId): void
    {
        if (!Article::where('article_id', $articleId)->exists()) {
            throw new InvalidArgumentException("Article with ID {$articleId} does not exist");
        }
    }

    /**
     * Validate depot exists.
     *
     * @throws InvalidArgumentException
     */
    protected function validateDepot(int $depotId): void
    {
        if (!Depot::where('depot_id', $depotId)->exists()) {
            throw new InvalidArgumentException("Depot with ID {$depotId} does not exist");
        }
    }

    /**
     * Validate quantity is positive.
     *
     * @throws InvalidArgumentException
     */
    protected function validateQuantity(float $quantity): void
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Quantity must be greater than zero');
        }
    }

    /**
     * Get default unit for an article.
     */
    public function getDefaultUnit(int $articleId): ?ArticleUnite
    {
        return ArticleUnite::where('article_id', $articleId)
            ->where('is_default', 1)
            ->first();
    }
}
