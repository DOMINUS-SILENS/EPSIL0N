<?php

declare(strict_types=1);

namespace App\Events\Region;

class ZoneQuotaAdjusted extends RegionEvent
{
    public string $zoneId;
    public int $newDailyCapacity;
    public string $reason;
    public string $effectiveFrom;

    public function __construct(
        string $regionId,
        string $zoneId,
        int $newDailyCapacity,
        string $reason,
        string $effectiveFrom
    ) {
        parent::__construct($regionId);
        $this->zoneId = $zoneId;
        $this->newDailyCapacity = $newDailyCapacity;
        $this->reason = $reason;
        $this->effectiveFrom = $effectiveFrom;
    }

    public function toPayload(): array
    {
        return array_merge(parent::toPayload(), [
            'zone_id' => $this->zoneId,
            'new_daily_capacity' => $this->newDailyCapacity,
            'reason' => $this->reason,
            'effective_from' => $this->effectiveFrom,
        ]);
    }
}
