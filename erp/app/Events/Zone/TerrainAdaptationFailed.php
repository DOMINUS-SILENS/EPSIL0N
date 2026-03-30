<?php

declare(strict_types=1);

namespace App\Events\Zone;

class TerrainAdaptationFailed extends ZoneEvent
{
    public string $sagaId;
    public string $routeId;
    public string $reason;
    public array $criticalCustomers;
    public string $failedAt;

    public function __construct(
        string $zoneId,
        string $sagaId,
        string $routeId,
        string $reason,
        array $criticalCustomers,
        string $failedAt
    ) {
        parent::__construct($zoneId);
        $this->sagaId = $sagaId;
        $this->routeId = $routeId;
        $this->reason = $reason;
        $this->criticalCustomers = $criticalCustomers;
        $this->failedAt = $failedAt;
    }

    public function toPayload(): array
    {
        return array_merge(parent::toPayload(), [
            'saga_id' => $this->sagaId,
            'route_id' => $this->routeId,
            'reason' => $this->reason,
            'critical_customers' => $this->criticalCustomers,
            'failed_at' => $this->failedAt,
        ]);
    }
}
