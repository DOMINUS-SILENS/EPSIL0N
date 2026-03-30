<?php

declare(strict_types=1);

namespace App\Events\Region;

class ZoneQuotaRemoved extends RegionEvent
{
    public string $zoneId;
    public string $reason;

    public function __construct(
        string $regionId,
        string $zoneId,
        string $reason
    ) {
        parent::__construct($regionId);
        $this->zoneId = $zoneId;
        $this->reason = $reason;
    }

    public function toPayload(): array
    {
        return array_merge(parent::toPayload(), [
            'zone_id' => $this->zoneId,
            'reason' => $this->reason,
        ]);
    }
}
