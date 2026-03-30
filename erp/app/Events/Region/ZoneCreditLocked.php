<?php

declare(strict_types=1);

namespace App\Events\Region;

class ZoneCreditLocked extends RegionEvent
{
    public string $tournéeId;
    public string $zoneId;
    public array $creditRequirements;

    public function __construct(
        string $regionId,
        string $tournéeId,
        string $zoneId,
        array $creditRequirements
    ) {
        parent::__construct($regionId);
        $this->tournéeId = $tournéeId;
        $this->zoneId = $zoneId;
        $this->creditRequirements = $creditRequirements;
    }

    public function toPayload(): array
    {
        return array_merge(parent::toPayload(), [
            'tournée_id' => $this->tournéeId,
            'zone_id' => $this->zoneId,
            'credit_requirements' => $this->creditRequirements,
        ]);
    }
}
