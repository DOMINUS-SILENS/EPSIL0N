<?php

declare(strict_types=1);

namespace App\Events\Region;

class RegionCoveragePlanGenerated extends RegionEvent
{
    public string $planId;
    public array $zoneConfigs;
    public string $effectiveDate;
    public ?string $notes;

    public function __construct(
        string $regionId,
        string $planId,
        array $zoneConfigs,
        string $effectiveDate,
        ?string $notes = null
    ) {
        parent::__construct($regionId);
        $this->planId = $planId;
        $this->zoneConfigs = $zoneConfigs;
        $this->effectiveDate = $effectiveDate;
        $this->notes = $notes;
    }

    public function toPayload(): array
    {
        return array_merge(parent::toPayload(), [
            'plan_id' => $this->planId,
            'zone_configs' => $this->zoneConfigs,
            'effective_date' => $this->effectiveDate,
            'notes' => $this->notes,
        ]);
    }
}
