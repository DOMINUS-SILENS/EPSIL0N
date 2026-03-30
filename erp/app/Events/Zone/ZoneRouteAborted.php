<?php

declare(strict_types=1);

namespace App\Events\Zone;

class ZoneRouteAborted extends ZoneEvent
{
    public string $routeId;
    public string $reason;
    public string $abortedAt;

    public function __construct(
        string $zoneId,
        string $routeId,
        string $reason,
        string $abortedAt
    ) {
        parent::__construct($zoneId);
        $this->routeId = $routeId;
        $this->reason = $reason;
        $this->abortedAt = $abortedAt;
    }

    public function toPayload(): array
    {
        return array_merge(parent::toPayload(), [
            'route_id' => $this->routeId,
            'reason' => $this->reason,
            'aborted_at' => $this->abortedAt,
        ]);
    }
}
