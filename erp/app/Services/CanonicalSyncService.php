<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\DomainOutbox;

class CanonicalSyncService
{
    /**
     * Unified write contract for canonical entities.
     * This ensures all canonical writes include source lineage and use business-safe keys.
     */
    public function sync(string $table, int $entrepriseId, int|string $legacyId, array $data, ?int $eventId = null, ?string $projector = null): void
    {
        $id = $data['id'] ?? $legacyId;

        $payload = array_merge($data, [
            'entreprise_id' => $entrepriseId,
            'source_legacy_id' => (string) $legacyId,
            'source_system' => 'legacy',
            'updated_at' => now(),
        ]);

        if (!isset($payload['created_at'])) {
            $payload['created_at'] = now();
        }

        // Hardened Identity Key Strategy
        $key = ['entreprise_id' => $entrepriseId, 'source_legacy_id' => (string) $legacyId];
        
        // Special case for order lines where the key is (order_id, source_legacy_id)
        if ($table === 'canonical_order_lines') {
            $key = ['order_id' => $data['order_id'], 'source_legacy_id' => (string) $legacyId];
        }

        DB::table($table)->updateOrInsert($key, $payload);
    }
}
