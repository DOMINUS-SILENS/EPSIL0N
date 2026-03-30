<?php

namespace App\Services\Projectors;

use App\Services\Projector;
use App\Models\ProjectionVersion;
use Illuminate\Support\Facades\DB;
use App\Models\DomainOutbox;

class CreditProjector extends Projector
{
    protected string $table = 'balances';
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

    public function handleCreditCreated(array $payload, DomainOutbox $event): void
    {
        // Implement Idempotent UPSERT logic for balances
    }

    public function handleCreditUpdated(array $payload, DomainOutbox $event): void
    {
        // Implement Idempotent UPSERT logic for balances
    }
}
