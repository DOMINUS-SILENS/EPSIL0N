<?php

declare(strict_types=1);

namespace App\Events\Region;

class ZoneRouteAccepted extends RegionEvent
{
    public string $tournéeId;
    public string $zoneId;
    public string $acceptedRouteId;
    public array $stockRequirements;
    public array $creditRequirements;
    public string $acceptedAt;

    public function __construct(
        string $regionId,
        string $tournéeId,
        string $zoneId,
        string $acceptedRouteId,
        array $stockRequirements,
        array $creditRequirements,
        string $acceptedAt
    ) {
        parent::__construct($regionId);
        $this->tournéeId = $tournéeId;
        $this->zoneId = $zoneId;
        $this->acceptedRouteId = $acceptedRouteId;
        $this->stockRequirements = $stockRequirements;
        $this->creditRequirements = $creditRequirements;
        $this->acceptedAt = $acceptedAt;
    }

    public function toPayload(): array
    {
        return array_merge(parent::toPayload(), [
            'tournée_id' => $this->tournéeId,
            'zone_id' => $this->zoneId,
            'accepted_route_id' => $this->acceptedRouteId,
            'stock_requirements' => $this->stockRequirements,
            'credit_requirements' => $this->creditRequirements,
            'accepted_at' => $this->acceptedAt,
        ]);
    }
}
