<?php

namespace App\Events;

class StockTransferred
{
    public string $uuid;
    public int $sourceDepotId;
    public int $targetDepotId;
    public int $articleId;
    public int $entrepriseId;
    public float $quantity;

    public function __construct(string $uuid, int $sourceDepotId, int $targetDepotId, int $articleId, int $entrepriseId, float $quantity)
    {
        $this->uuid = $uuid;
        $this->sourceDepotId = $sourceDepotId;
        $this->targetDepotId = $targetDepotId;
        $this->articleId = $articleId;
        $this->entrepriseId = $entrepriseId;
        $this->quantity = $quantity;
    }
}
