<?php

declare(strict_types=1);

namespace App\Events\Zone;

class ZoneCapacityUpdated extends ZoneEvent
{
    public int $newCapacity;
    public string $reason;
    public ?float $newUtilizationThreshold;

    public function __construct(
        string $zoneId,
        int $newCapacity,
        string $reason,
        ?float $newUtilizationThreshold = null
    ) {
        parent::__construct($zoneId);
        $this->newCapacity = $newCapacity;
        $this->reason = $reason;
        $this->newUtilizationThreshold = $newUtilizationThreshold;
    }

    public function toPayload(): array
    {
        return array_merge(parent::toPayload(), [
            'new_capacity' => $this->newCapacity,
            'reason' => $this->reason,
            'new_utilization_threshold' => $this->newUtilizationThreshold,
        ]);
    }
}
