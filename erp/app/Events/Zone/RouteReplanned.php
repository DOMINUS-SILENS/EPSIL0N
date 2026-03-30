<?php

declare(strict_types=1);

namespace App\Events\Zone;

class RouteReplanned extends ZoneEvent
{
    public string $routeId;
    public string $disruptionId;
    public string $type;
    public array $alternativePath;
    public array $affectedCustomers;
    public string $replannedAt;

    public function __construct(
        string $zoneId,
        string $routeId,
        string $disruptionId,
        string $type,
        array $alternativePath,
        array $affectedCustomers,
        string $replannedAt
    ) {
        parent::__construct($zoneId);
        $this->routeId = $routeId;
        $this->disruptionId = $disruptionId;
        $this->type = $type;
        $this->alternativePath = $alternativePath;
        $this->affectedCustomers = $affectedCustomers;
        $this->replannedAt = $replannedAt;
    }

    public function toPayload(): array
    {
        return array_merge(parent::toPayload(), [
            'route_id' => $this->routeId,
            'disruption_id' => $this->disruptionId,
            'type' => $this->type,
            'alternative_path' => $this->alternativePath,
            'affected_customers' => $this->affectedCustomers,
            'replanned_at' => $this->replannedAt,
        ]);
    }
}
