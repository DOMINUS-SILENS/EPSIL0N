<?php

declare(strict_types=1);

namespace App\Events\Zone;

class ZoneRouteAccepted extends ZoneEvent
{
    public string $routeId;
    public string $tournéeId;
    public string $acceptedAt;
    public array $stockRequirements;
    public array $creditRequirements;

    public function __construct(
        string $zoneId,
        string $routeId,
        string $tournéeId,
        string $acceptedAt,
        array $stockRequirements,
        array $creditRequirements
    ) {
        parent::__construct($zoneId);
        $this->routeId = $routeId;
        $this->tournéeId = $tournéeId;
        $this->acceptedAt = $acceptedAt;
        $this->stockRequirements = $stockRequirements;
        $this->creditRequirements = $creditRequirements;
    }

    public function toPayload(): array
    {
        return array_merge(parent::toPayload(), [
            'route_id' => $this->routeId,
            'tournée_id' => $this->tournéeId,
            'accepted_at' => $this->acceptedAt,
            'stock_requirements' => $this->stockRequirements,
            'credit_requirements' => $this->creditRequirements,
        ]);
    }
}
