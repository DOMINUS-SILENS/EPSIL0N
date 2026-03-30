<?php

namespace App\Services\Projectors;

use App\Services\Projector;
use Illuminate\Support\Facades\DB;
use App\Models\DomainOutbox;

class FleetProjector extends Projector
{
    public function handleVehicleRegistered(array $payload, DomainOutbox $event): void
    {
        DB::table('vehicles')->updateOrInsert(
            [
                'entreprise_id' => $payload['entrepriseId'],
                'vehicle_id' => $payload['vehicleId'],
            ],
            [
                'license_plate' => $payload['data']['license_plate'],
                'model' => $payload['data']['model'] ?? null,
                'year' => $payload['data']['year'] ?? null,
                'status' => 'active',
                'last_event_id' => $event->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
