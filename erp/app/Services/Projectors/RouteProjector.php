<?php

namespace App\Services\Projectors;

use App\Models\ProjectionVersion;
use App\Services\Projector;
use Illuminate\Support\Facades\DB;
use App\Models\DomainOutbox;

class RouteProjector extends Projector
{
    protected string $table = 'routes'; // Using logical base for now
    protected string $idField = 'route_id';

    protected function getVersionFromDatabase(): int
    {
        return ProjectionVersion::where('projector_name', self::class)->value('version') ?? 0;
    }

    protected function setVersion(int $version): void
    {
        ProjectionVersion::updateOrCreate(['projector_name' => self::class], ['version' => $version]);
    }

    protected function getState(int $aggregateId): array { return []; }
    protected function restoreState(int $aggregateId, array $state): void {}
    protected function setLastProcessedEventId(int $aggregateId, int $lastEventId): void {}

    public function handleRouteCreated(array $payload, DomainOutbox $event): void
    {
        DB::table('mission_zone')->insertOrIgnore([
            'entreprise_id' => $payload['entrepriseId'],
            'zone_id' => $payload['routeId'], // Mapping logic ID
            ...$payload['data'],
            'last_event_id' => $event->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    public function handleRegularTourScheduled(array $payload, DomainOutbox $event): void
    {
        DB::table('mission_planning')->insertOrIgnore([
            'entreprise_id' => $payload['entrepriseId'],
            'planning_id' => $payload['routeId'],
            'days_of_week' => json_encode($payload['planningData']['days_of_week'] ?? []),
            'assigned_clients' => json_encode($payload['assignedClients']),
            'last_event_id' => $event->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    public function handleOptimizationRun(array $payload, DomainOutbox $event): void
    {
        DB::table('optimisation')->insertOrIgnore([
            'entreprise_id' => $payload['entrepriseId'],
            'optimisation_id' => $payload['optimizationId'],
            'parameters' => json_encode($payload['parameters']),
            'status' => 'computed',
            'last_event_id' => $event->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        foreach ($payload['optimizedMissions'] as $mission) {
            DB::table('optimisation_mission')->insertOrIgnore([
                'entreprise_id' => $payload['entrepriseId'],
                'optimisation_id' => $payload['optimizationId'],
                ...$mission,
                'last_event_id' => $event->id
            ]);
        }
    }

    public function handleOptimizationApplied(array $payload, DomainOutbox $event): void
    {
        DB::table('optimisation')
            ->where('entreprise_id', $payload['entrepriseId'])
            ->where('optimisation_id', $payload['optimizationId'])
            ->where('last_event_id', '<', $event->id)
            ->update([
                'status' => 'applied',
                'last_event_id' => $event->id,
                'updated_at' => now()
            ]);
    }
}
