<?php

declare(strict_types=1);

namespace App\Events\Region;

class ZoneStockLocked extends RegionEvent
{
    public string $tournéeId;
    public string $zoneId;
    public array $stockRequirements;

    public function __construct(
        string $regionId,
        string $tournéeId,
        string $zoneId,
        array $stockRequirements
    ) {
        parent::__construct($regionId);
        $this->tournéeId = $tournéeId;
        $this->zoneId = $zoneId;
        $this->stockRequirements = $stockRequirements;
    }

    public function toPayload(): array
    {
        return array_merge(parent::toPayload(), [
            'tournée_id' => $this->tournéeId,
            'zone_id' => $this->zoneId,
            'stock_requirements' => $this->stockRequirements,
        ]);
    }
}
