<?php

declare(strict_types=1);

namespace App\Events\Zone;

class ZoneRouteAdapted extends ZoneEvent
{
    public string $routeId;
    public string $tournéeId;
    public array $changes;
    public string $reason;
    public string $adaptedBy;
    public string $adaptedAt;

    public function __construct(
        string $zoneId,
        string $routeId,
        string $tournéeId,
        array $changes,
        string $reason,
        string $adaptedBy,
        string $adaptedAt
    ) {
        parent::__construct($zoneId);
        $this->routeId = $routeId;
        $this->tournéeId = $tournéeId;
        $this->changes = $changes;
        $this->reason = $reason;
        $this->adaptedBy = $adaptedBy;
        $this->adaptedAt = $adaptedAt;
    }

    public function toPayload(): array
    {
        return array_merge(parent::toPayload(), [
            'route_id' => $this->routeId,
            'tournée_id' => $this->tournéeId,
            'changes' => $this->changes,
            'reason' => $this->reason,
            'adapted_by' => $this->adaptedBy,
            'adapted_at' => $this->adaptedAt,
        ]);
    }
}
