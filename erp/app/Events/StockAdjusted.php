<?php

namespace App\Events;

class StockAdjusted
{
    public string $uuid;
    public int $depotId;
    public int $articleId;
    public int $entrepriseId;
    public float $actualQuantity;
    public float $delta; // The mathematical difference evaluated at the time of adjustment.
    
    public function __construct(string $uuid, int $depotId, int $articleId, int $entrepriseId, float $actualQuantity, float $delta)
    {
        $this->uuid = $uuid;
        $this->depotId = $depotId;
        $this->articleId = $articleId;
        $this->entrepriseId = $entrepriseId;
        $this->actualQuantity = $actualQuantity;
        $this->delta = $delta;
    }
}
