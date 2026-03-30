<?php

namespace App\Services\Projectors;

use App\Models\ProjectionVersion;
use App\Services\Projector;
use Illuminate\Support\Facades\DB;
use App\Models\DomainOutbox;

class CrmProjector extends Projector
{
    protected string $table = 'communication_client';
    protected string $idField = 'communication_id'; // Logical abstraction

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

    public function handleCustomerVisited(array $payload, DomainOutbox $event): void
    {
        // Mutate the contact's last visit date explicitly protecting state chronological integrity
        DB::table('contacts')
            ->where('entreprise_id', $payload['entrepriseId'])
            ->where('contact_id', $payload['contactId'])
            ->where('last_event_id', '<', $event->id)
            ->update([
                'last_visit_date' => $event->created_at, // Fast timestamp injection
                'last_event_id' => $event->id,
                'updated_at' => now()
            ]);
    }

    public function handleMarketingInteractionRecorded(array $payload, DomainOutbox $event): void
    {
        DB::table('marketing')->insertOrIgnore([
            'entreprise_id' => $payload['entrepriseId'],
            'contact_id' => $payload['contactId'],
            'campaign_id' => $payload['campaignId'],
            'interaction_type' => $payload['interactionType'],
            'metadata' => json_encode($payload['metadata']),
            'last_event_id' => $event->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    public function handleCommunicationLogged(array $payload, DomainOutbox $event): void
    {
        DB::table('communication_client')->insertOrIgnore([
            'entreprise_id' => $payload['entrepriseId'],
            'contact_id' => $payload['contactId'],
            'channel' => $payload['channel'],
            'direction' => $payload['direction'],
            'content' => json_encode($payload['content']),
            'last_event_id' => $event->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}
