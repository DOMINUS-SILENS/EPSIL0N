<?php

declare(strict_types=1);

namespace App\Events\Zone;

class ZoneRoutePlanned extends ZoneEvent
{
    public string $routeId;
    public string $tournéeId;
    public string $date;
    public string $pricingProfileId;
    public float $targetVolume;
    public array $customers;
    public array $vehicles;

    public function __construct(
        string $zoneId,
        string $routeId,
        string $tournéeId,
        string $date,
        string $pricingProfileId,
        float $targetVolume,
        array $customers,
        array $vehicles
    ) {
        parent::__construct($zoneId);
        $this->routeId = $routeId;
        $this->tournéeId = $tournéeId;
        $this->date = $date;
        $this->pricingProfileId = $pricingProfileId;
        $this->targetVolume = $targetVolume;
        $this->customers = $customers;
        $this->vehicles = $vehicles;
    }

    public function toPayload(): array
    {
        return array_merge(parent::toPayload(), [
            'route_id' => $this->routeId,
            'tournée_id' => $this->tournéeId,
            'date' => $this->date,
            'pricing_profile_id' => $this->pricingProfileId,
            'target_volume' => $this->targetVolume,
            'customers' => $this->customers,
            'vehicles' => $this->vehicles,
        ]);
    }
}
