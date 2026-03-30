<?php

namespace App\Observers;

use App\Models\ArticleMovement;
use App\Services\OutboxService;
use Illuminate\Support\Facades\Log;

class ArticleMovementObserver
{
    protected $outbox;

    protected static $processed = [];

    public function __construct(OutboxService $outbox)
    {
        $this->outbox = $outbox;
    }

    public function created(ArticleMovement $movement): void
    {
        // Prevent duplicate processing
        if (in_array($movement->article_mouvement_id, self::$processed)) {
            Log::warning('Duplicate observer call for movement ID: '.$movement->article_mouvement_id);

            return;
        }
        self::$processed[] = $movement->article_mouvement_id;

        // Publish domain event
        $this->outbox->publishDomain(
            'stock_movement',
            $movement->article_mouvement_id,
            'stock.movement.created',
            [
                'article_id' => $movement->article_id,
                'warehouse_id' => $movement->depot_id_destination ?? $movement->depot_id_source,
                'quantity' => $movement->article_mouvement_quantite,
                'type' => $movement->stock_operation_type,
                'movement_id' => $movement->article_mouvement_id,
                'created_at' => $movement->created_at,
            ]
        );
    }
}
