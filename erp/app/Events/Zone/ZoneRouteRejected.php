<?php

declare(strict_types=1);

namespace App\Events\Zone;

class ZoneRouteRejected extends ZoneEvent
{
    public string $routeId;
    public string $tournéeId;
    public string $reason;
    public array $requestedChanges;
    public string $adaptationId;
    public string $deadline;

    public function __construct(
        string $zoneId,
        string $routeId,
        string $tournéeId,
        string $reason,
        array $requestedChanges,
        string $adaptationId,
        string $deadline
    ) {
        parent::__construct($zoneId);
        $this->routeId = $routeId;
        $this->tournéeId = $tournéeId;
        $this->reason = $reason;
        $this->requestedChanges = $requestedChanges;
        $this->adaptationId = $adaptationId;
        $this->deadline = $deadline;
    }

    public function toPayload(): array
    {
        return array_merge(parent::toPayload(), [
            'route_id' => $this->routeId,
            'tournée_id' => $this->tournéeId,
            'reason' => $this->reason,
            'requested_changes' => $this->requestedChanges,
            'adaptation_id' => $this->adaptationId,
            'deadline' => $this->deadline,
        ]);
    }
}
