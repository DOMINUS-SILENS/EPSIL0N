<?php

declare(strict_types=1);

namespace App\Events\Region;

class ZoneCoverageCompromised extends RegionEvent
{
    public string $zoneId;
    public string $reason;
    public array $affectedCustomers;
    public array $impactMetrics;
    public string $detectedAt;

    public function __construct(
        string $regionId,
        string $zoneId,
        string $reason,
        array $affectedCustomers,
        array $impactMetrics,
        string $detectedAt
    ) {
        parent::__construct($regionId);
        $this->zoneId = $zoneId;
        $this->reason = $reason;
        $this->affectedCustomers = $affectedCustomers;
        $this->impactMetrics = $impactMetrics;
        $this->detectedAt = $detectedAt;
    }

    public function toPayload(): array
    {
        return array_merge(parent::toPayload(), [
            'zone_id' => $this->zoneId,
            'reason' => $this->reason,
            'affected_customers' => $this->affectedCustomers,
            'impact_metrics' => $this->impactMetrics,
            'detected_at' => $this->detectedAt,
        ]);
    }
}
