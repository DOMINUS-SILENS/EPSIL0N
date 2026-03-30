<?php

declare(strict_types=1);

namespace App\Events\Zone;

class TerrainDisruptionDetected extends ZoneEvent
{
    public string $disruptionId;
    public string $type; // 'road_blocked', 'weather_alert', 'vehicle_unavailable', 'customer_closed'
    public array $location; // ['lat' => x, 'lng' => y, 'radius' => meters]
    public array $affectedRoutes;
    public array $constraints; // Additional constraint info
    public string $detectedAt;

    public function __construct(
        string $zoneId,
        string $disruptionId,
        string $type,
        array $location,
        array $affectedRoutes,
        array $constraints = [],
        string $detectedAt = ''
    ) {
        parent::__construct($zoneId);
        $this->disruptionId = $disruptionId;
        $this->type = $type;
        $this->location = $location;
        $this->affectedRoutes = $affectedRoutes;
        $this->constraints = $constraints;
        $this->detectedAt = $detectedAt ?: now()->toDateTimeString();
    }

    public function toPayload(): array
    {
        return array_merge(parent::toPayload(), [
            'disruption_id' => $this->disruptionId,
            'type' => $this->type,
            'location' => $this->location,
            'affected_routes' => $this->affectedRoutes,
            'constraints' => $this->constraints,
            'detected_at' => $this->detectedAt,
        ]);
    }
}
