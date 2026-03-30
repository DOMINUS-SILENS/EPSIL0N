<?php

namespace App\Services\Projectors;

use App\Services\Projector;
use Illuminate\Support\Facades\DB;
use App\Models\DomainOutbox;

class ProjectProjector extends Projector
{
    public function handleProjectCreated(array $payload, DomainOutbox $event): void
    {
        DB::table('projects')->updateOrInsert(
            [
                'entreprise_id' => $payload['entrepriseId'],
                'project_id' => $payload['projectId'],
            ],
            [
                'name' => $payload['name'],
                'description' => $payload['data']['description'] ?? null,
                'start_date' => $payload['data']['start_date'] ?? null,
                'end_date' => $payload['data']['end_date'] ?? null,
                'status' => 'planned',
                'last_event_id' => $event->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
