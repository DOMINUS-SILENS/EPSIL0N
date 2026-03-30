<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class SnapshotService
{
    private const TABLE = 'aggregate_snapshots';

    public function getLatestSnapshot(string $aggregateType, string $aggregateId, ?string $tenantId = null): ?object
    {
        if (empty($tenantId)) {
            throw new \InvalidArgumentException("Enterprise Closure Violation: tenant_id must be explicitly provided for Snapshot retrieval.");
        }

        $snapshot = DB::table(self::TABLE)
            ->where('aggregate_type', $aggregateType)
            ->where('aggregate_id', $aggregateId)
            ->where('tenant_id', $tenantId)
            ->orderByDesc('last_aggregate_sequence')
            ->first();

        if (!$snapshot) {
            return null;
        }

        // Integrity sealing validation
        $expectedHash = hash('sha256', $snapshot->state_json);
        if (!hash_equals($expectedHash, $snapshot->state_hash)) {
            \Illuminate\Support\Facades\Log::critical("Snapshot Data Tampered for {$aggregateType}:{$aggregateId}. state_json does not match state_hash. Forcing full event store rebuild.");
            return null;
        }

        $schemaVersion = $snapshot->schema_version ?? 1;
        $signaturePayload = "{$tenantId}:{$aggregateId}:{$snapshot->last_aggregate_sequence}:{$snapshot->state_hash}:{$schemaVersion}";
        $expectedSignature = hash_hmac('sha256', $signaturePayload, config('app.key'));

        if (!hash_equals($expectedSignature, $snapshot->signature ?? '')) {
            \Illuminate\Support\Facades\Log::critical("Snapshot Cryptographic Signature Invalid for {$aggregateType}:{$aggregateId}. Forcing full event store rebuild.");
            return null;
        }

        return $snapshot;
    }

    /**
     * Store snapshot and mathematically seal payload to guarantee immutability before subsequent replay reconstructions.
     */
    public function saveSnapshot(string $aggregateType, string $aggregateId, array $data, int $lastEventId, ?string $tenantId = null, int $schemaVersion = 1, int $lastAggregateSequence = 0): void
    {
        if (empty($tenantId)) {
            throw new \InvalidArgumentException("Enterprise Closure Violation: tenant_id must be explicitly provided for Snapshot creation.");
        }

        $stateJson = json_encode($data);
        $stateHash = hash('sha256', $stateJson);
        $signaturePayload = "{$tenantId}:{$aggregateId}:{$lastAggregateSequence}:{$stateHash}:{$schemaVersion}";
        $signature = hash_hmac('sha256', $signaturePayload, config('app.key'));

        DB::table(self::TABLE)->updateOrInsert(
            [
                'tenant_id' => $tenantId,
                'aggregate_type' => $aggregateType,
                'aggregate_id' => $aggregateId,
                'last_aggregate_sequence' => $lastAggregateSequence,
            ],
            [
                'id' => \Illuminate\Support\Str::uuid()->toString(),
                'state_json' => $stateJson,
                'state_hash' => $stateHash,
                'signature' => $signature,
                'last_event_store_id' => $lastEventId,
                'schema_version' => $schemaVersion,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
