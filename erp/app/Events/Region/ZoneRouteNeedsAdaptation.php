<?php

declare(strict_types=1);

namespace App\Events\Region;

class ZoneRouteNeedsAdaptation extends RegionEvent
{
    public string $adaptationId;
    public string $tournéeId;
    public string $zoneId;
    public string $reason;
    public array $requestedChanges;
    public string $requestedAt;

    public function __construct(
        string $regionId,
        string $adaptationId,
        string $tournéeId,
        string $zoneId,
        string $reason,
        array $requestedChanges,
        string $requestedAt
    ) {
        parent::__construct($regionId);
        $this->adaptationId = $adaptationId;
        $this->tournéeId = $tournéeId;
        $this->zoneId = $zoneId;
        $this->reason = $reason;
        $this->requestedChanges = $requestedChanges;
        $this->requestedAt = $requestedAt;
    }

    public function toPayload(): array
    {
        return array_merge(parent::toPayload(), [
            'adaptation_id' => $this->adaptationId,
            'tournée_id' => $this->tournéeId,
            'zone_id' => $this->zoneId,
            'reason' => $this->reason,
            'requested_changes' => $this->requestedChanges,
            'requested_at' => $this->requestedAt,
        ]);
    }
}
