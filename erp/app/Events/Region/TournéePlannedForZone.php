<?php

declare(strict_types=1);

namespace App\Events\Region;

class TournéePlannedForZone extends RegionEvent
{
    public string $tournéeId;
    public string $zoneId;
    public string $date;
    public string $routeId;
    public string $pricingProfileId;
    public float $targetVolume;
    public array $customers;
    public ?string $notes;

    public function __construct(
        string $regionId,
        string $tournéeId,
        string $zoneId,
        string $date,
        string $routeId,
        string $pricingProfileId,
        float $targetVolume,
        array $customers,
        ?string $notes = null
    ) {
        parent::__construct($regionId);
        $this->tournéeId = $tournéeId;
        $this->zoneId = $zoneId;
        $this->date = $date;
        $this->routeId = $routeId;
        $this->pricingProfileId = $pricingProfileId;
        $this->targetVolume = $targetVolume;
        $this->customers = $customers;
        $this->notes = $notes;
    }

    public function toPayload(): array
    {
        return array_merge(parent::toPayload(), [
            'tournée_id' => $this->tournéeId,
            'zone_id' => $this->zoneId,
            'date' => $this->date,
            'route_id' => $this->routeId,
            'pricing_profile_id' => $this->pricingProfileId,
            'target_volume' => $this->targetVolume,
            'customers' => $this->customers,
            'notes' => $this->notes,
        ]);
    }
}
