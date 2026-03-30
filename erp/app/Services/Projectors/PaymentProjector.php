<?php

namespace App\Services\Projectors;

use App\Services\Projector;
use App\Models\ProjectionVersion;
use Illuminate\Support\Facades\DB;
use App\Models\DomainOutbox;

class PaymentProjector extends Projector
{
    protected string $table = 'reglements';
    protected string $idField = 'id';

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

    public function handlePaymentCreated(array $payload, DomainOutbox $event): void
    {
        DB::table('reglements')->insertOrIgnore([
            'entreprise_id' => $payload['entrepriseId'],
            'reglement_id' => $payload['paymentId'],
            'contact_id' => $payload['contactId'],
            'montant' => $payload['amount'],
            'mode_reglement' => $payload['mode'],
            'last_event_id' => $event->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function handlePaymentUpdated(array $payload, DomainOutbox $event): void
    {
        DB::table('reglements')
            ->where('entreprise_id', $payload['entrepriseId'])
            ->where('reglement_id', $payload['paymentId'])
            ->where('last_event_id', '<', $event->id)
            ->update([
                'montant' => $payload['amount'],
                'mode_reglement' => $payload['mode'],
                'last_event_id' => $event->id,
                'updated_at' => now(),
            ]);
    }
}
