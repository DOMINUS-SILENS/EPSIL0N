<?php

declare(strict_types=1);

namespace App\Events\Region;

class RegionCoveragePlanUpdated extends RegionEvent
{
    public string $planId;
    public array $zoneConfigs;
    public ?string $notes;

    public function __construct(
        string $regionId,
        string $planId,
        array $zoneConfigs,
        ?string $notes = null
    ) {
        parent::__construct($regionId);
        $this->planId = $planId;
        $this->zoneConfigs = $zoneConfigs;
        $this->notes = $notes;
    }

    public function toPayload(): array
    {
        return array_merge(parent::toPayload(), [
            'plan_id' => $this->planId,
            'zone_configs' => $this->zoneConfigs,
            'notes' => $this->notes,
        ]);
    }
}
