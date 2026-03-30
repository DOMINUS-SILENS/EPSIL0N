<?php

namespace App\Services\Projectors;

use App\Models\ProjectionVersion;
use App\Services\Projector;
use Illuminate\Support\Facades\DB;
use App\Models\DomainOutbox;

class MissionProjector extends Projector
{
    protected string $table = 'missions';
    protected string $idField = 'mission_id';

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

    public function handleMissionCreated(array $payload, DomainOutbox $event): void
    {
        $missionExists = DB::table('missions')
            ->where('entreprise_id', $payload['entrepriseId'])
            ->where('mission_id', $payload['missionId'])
            ->exists();

        if (!$missionExists) {
            DB::table('missions')->insert([
                'entreprise_id' => $payload['entrepriseId'],
                'mission_id' => $payload['missionId'],
                ...$payload['data'],
                'status' => 'planned',
                'last_event_id' => $event->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach ($payload['points'] ?? [] as $point) {
            DB::table('mission_point')->insertOrIgnore([
                'entreprise_id' => $payload['entrepriseId'],
                'mission_id' => $payload['missionId'],
                'mission_point_id' => $point['mission_point_id'],
                ...$point,
                'status' => 'pending',
                'last_event_id' => $event->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function updateMissionStatus(int $entrepriseId, int $missionId, int $eventId, string $status): void
    {
        DB::table('missions')
            ->where('entreprise_id', $entrepriseId)
            ->where('mission_id', $missionId)
            ->where('last_event_id', '<', $eventId)
            ->update([
                'status' => $status,
                'last_event_id' => $eventId,
                'updated_at' => now(),
            ]);
    }

    public function handleMissionLoaded(array $payload, DomainOutbox $event): void
    {
        $this->updateMissionStatus($payload['entrepriseId'], $payload['missionId'], $event->id, 'loaded');
    }

    public function handleStopVisited(array $payload, DomainOutbox $event): void
    {
        // 1. Update the overall mission status ensuring it flags as in-progress natively
        $this->updateMissionStatus($payload['entrepriseId'], $payload['missionId'], $event->id, 'in_progress');

        // 2. Update specific point payload delivery statistics
        DB::table('mission_point')
            ->where('entreprise_id', $payload['entrepriseId'])
            ->where('mission_id', $payload['missionId'])
            ->where('mission_point_id', $payload['missionPointId'])
            ->where('last_event_id', '<', $event->id)
            ->update([
                'status' => 'visited',
                ...$payload['deliveryData'], // Map drops, drops_damaged etc
                'last_event_id' => $event->id,
                'updated_at' => now(),
            ]);
    }

    public function handleMissionCompleted(array $payload, DomainOutbox $event): void
    {
        $this->updateMissionStatus($payload['entrepriseId'], $payload['missionId'], $event->id, 'completed');
    }
}
