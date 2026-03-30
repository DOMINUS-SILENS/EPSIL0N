<?php

declare(strict_types=1);

namespace App\Events\Region;

class StockReleased extends RegionEvent
{
    public string $tournéeId;
    public array $releasedStock;

    public function __construct(
        string $regionId,
        string $tournéeId,
        array $releasedStock
    ) {
        parent::__construct($regionId);
        $this->tournéeId = $tournéeId;
        $this->releasedStock = $releasedStock;
    }

    public function toPayload(): array
    {
        return array_merge(parent::toPayload(), [
            'tournée_id' => $this->tournéeId,
            'released_stock' => $this->releasedStock,
        ]);
    }
}
