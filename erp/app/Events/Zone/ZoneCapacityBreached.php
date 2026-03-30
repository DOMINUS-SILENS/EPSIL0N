<?php

declare(strict_types=1);

namespace App\Events\Zone;

class ZoneCapacityBreached extends ZoneEvent
{
    public float $currentLoad;
    public int $dailyCapacity;
    public float $utilizationPercentage;
    public string $breachedAt;

    public function __construct(
        string $zoneId,
        float $currentLoad,
        int $dailyCapacity,
        float $utilizationPercentage,
        string $breachedAt
    ) {
        parent::__construct($zoneId);
        $this->currentLoad = $currentLoad;
        $this->dailyCapacity = $dailyCapacity;
        $this->utilizationPercentage = $utilizationPercentage;
        $this->breachedAt = $breachedAt;
    }

    public function toPayload(): array
    {
        return array_merge(parent::toPayload(), [
            'current_load' => $this->currentLoad,
            'daily_capacity' => $this->dailyCapacity,
            'utilization_percentage' => $this->utilizationPercentage,
            'breached_at' => $this->breachedAt,
        ]);
    }
}
