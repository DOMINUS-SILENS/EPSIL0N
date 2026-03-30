<?php

namespace App\Aggregates;

use App\Events\VehicleRegistered;

class FleetAggregate extends AggregateRoot
{
    public function registerVehicle(int $vehicleId, int $entrepriseId, array $data): static
    {
        $this->recordThat(new VehicleRegistered(
            $this->uuid(),
            $vehicleId,
            $entrepriseId,
            $data
        ));

        return $this;
    }

    protected function applyVehicleRegistered(VehicleRegistered $event): void
    {
        $this->tenantId = $event->entrepriseId;
    }
}
