<?php

declare(strict_types=1);

namespace App\Events\Zone;

class ZoneRouteCompleted extends ZoneEvent
{
    public string $routeId;
    public array $completedStops;
    public array $skippedStops;
    public array $metrics;
    public string $completedAt;

    public function __construct(
        string $zoneId,
        string $routeId,
        array $completedStops,
        array $skippedStops,
        array $metrics,
        string $completedAt
    ) {
        parent::__construct($zoneId);
        $this->routeId = $routeId;
        $this->completedStops = $completedStops;
        $this->skippedStops = $skippedStops;
        $this->metrics = $metrics;
        $this->completedAt = $completedAt;
    }

    public function toPayload(): array
    {
        return array_merge(parent::toPayload(), [
            'route_id' => $this->routeId,
            'completed_stops' => $this->completedStops,
            'skipped_stops' => $this->skippedStops,
            'metrics' => $this->metrics,
            'completed_at' => $this->completedAt,
        ]);
    }
}
