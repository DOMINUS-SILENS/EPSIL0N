<?php

declare(strict_types=1);

namespace App\Events\Zone;

class ZoneRegistered extends ZoneEvent
{
    public string $regionId;
    public string $name;
    public int $dailyCapacity;
    public array $terrainConstraints;
    public array $mandatoryCustomers;

    public function __construct(
        string $zoneId,
        string $regionId,
        string $name,
        int $dailyCapacity,
        array $terrainConstraints,
        array $mandatoryCustomers
    ) {
        parent::__construct($zoneId);
        $this->regionId = $regionId;
        $this->name = $name;
        $this->dailyCapacity = $dailyCapacity;
        $this->terrainConstraints = $terrainConstraints;
        $this->mandatoryCustomers = $mandatoryCustomers;
    }

    public function toPayload(): array
    {
        return array_merge(parent::toPayload(), [
            'region_id' => $this->regionId,
            'name' => $this->name,
            'daily_capacity' => $this->dailyCapacity,
            'terrain_constraints' => $this->terrainConstraints,
            'mandatory_customers' => $this->mandatoryCustomers,
        ]);
    }
}
