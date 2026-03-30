<?php

namespace App\Events;

class StockConsumed
{
    public string $uuid;
    public int $depotId;
    public int $articleId;
    public int $entrepriseId;
    public float $quantity;
    public string $reason; // Sale, Waste

    public function __construct(string $uuid, int $depotId, int $articleId, int $entrepriseId, float $quantity, string $reason)
    {
        $this->uuid = $uuid;
        $this->depotId = $depotId;
        $this->articleId = $articleId;
        $this->entrepriseId = $entrepriseId;
        $this->quantity = $quantity;
        $this->reason = $reason;
    }
}
