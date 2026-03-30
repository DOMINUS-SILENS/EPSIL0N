<?php

declare(strict_types=1);

namespace App\Events\Zone;

class ZoneRouteStarted extends ZoneEvent
{
    public string $routeId;
    public array $vehicles;
    public string $startedAt;

    public function __construct(
        string $zoneId,
        string $routeId,
        array $vehicles,
        string $startedAt
    ) {
        parent::__construct($zoneId);
        $this->routeId = $routeId;
        $this->vehicles = $vehicles;
        $this->startedAt = $startedAt;
    }

    public function toPayload(): array
    {
        return array_merge(parent::toPayload(), [
            'route_id' => $this->routeId,
            'vehicles' => $this->vehicles,
            'started_at' => $this->startedAt,
        ]);
    }
}
