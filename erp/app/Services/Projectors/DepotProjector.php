<?php

namespace App\Services\Projectors;

use App\Services\Projector;
use App\Models\ProjectionVersion;
use Illuminate\Support\Facades\DB;
use App\Models\DomainOutbox;
use App\Services\CanonicalIdentityService;
use App\Services\CanonicalSyncService;

class DepotProjector extends Projector
{
    protected string $table = 'depots';
    protected string $idField = 'id';

    protected CanonicalIdentityService $identity;
    protected CanonicalSyncService $sync;

    public function __construct()
    {
        $this->identity = app(CanonicalIdentityService::class);
        $this->sync = app(CanonicalSyncService::class);
    }

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

    public function resetState(): void
    {
        DB::table('depots')->truncate();
        if (config('epsilon.canonical_dual_write', false) || env('CANONICAL_DUAL_WRITE', false)) {
            DB::table('canonical_depots')->truncate();
        }
    }

    public function handleDepotCreated(array $payload, DomainOutbox $event): void
    {
        $insertData = [
            'id' => $payload['depotId'] ?? ($payload['id'] ?? 0),
            'entreprise_id' => $payload['entrepriseId'] ?? ($payload['entrepriseId'] ?? 0),
            'designation' => $payload['designation'] ?? ($payload['label'] ?? '[UNNAMED DEPOT]'),
            'code' => $payload['code'] ?? null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        
        DB::table('depots')->updateOrInsert(['id' => $insertData['id']], $insertData);

        // Dual-Write to Canonical Schema
        if (config('epsilon.canonical_dual_write', false) || env('CANONICAL_DUAL_WRITE', false)) {
            $canonicalId = $this->identity->generateId('depot', $insertData['entreprise_id'], $insertData['id']);

            $this->sync->sync('canonical_depots', $insertData['entreprise_id'], $insertData['id'], [
                'id' => $canonicalId,
                'designation' => $insertData['designation'],
                'code' => $insertData['code'] ?? null,
                'is_active' => true,
            ]);
        }
    }

    public function handleDepotUpdated(array $payload, DomainOutbox $event): void
    {
        $this->handleDepotCreated($payload, $event);
    }
}
