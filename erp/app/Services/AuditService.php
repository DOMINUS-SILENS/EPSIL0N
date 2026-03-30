<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;

class AuditService
{
    /**
     * Log an audit entry.
     *
     * @param  array  $data  Must contain: entreprise_id, action, model, model_id, old_values, new_values, reason, trace_id, event_time
     */
    public function log(array $data, string $previousHash, int $sequence): void
    {
        $rowHash = $this->computeHash(array_merge($data, ['sequence' => $sequence, 'previous_hash' => $previousHash]));

        AuditLog::create([
            'entreprise_id' => $data['entreprise_id'],
            'sequence' => $sequence,
            'previous_hash' => $previousHash,
            'row_hash' => $rowHash,
            'action' => $data['action'],
            'model' => $data['model'],
            'model_id' => $data['model_id'] ?? null,
            'old_values' => $data['old_values'] ?? null,
            'new_values' => $data['new_values'] ?? null,
            'reason' => $data['reason'] ?? null,
            'trace_id' => $data['trace_id'] ?? null,
            'event_time' => $data['event_time'] ?? now(),
            'recorded_at' => now(),
        ]);
    }

    /**
     * Verify the hash chain for a given company.
     */
    public function verifyChain(int $entrepriseId): array
    {
        return DB::select('
            SELECT a1.id, a1.sequence
            FROM audit_logs a1
            INNER JOIN audit_logs a2 ON a2.entreprise_id = a1.entreprise_id
                AND a2.sequence = a1.sequence - 1
            WHERE a1.entreprise_id = ?
                AND a1.previous_hash != a2.row_hash
        ', [$entrepriseId]);
    }

    public function verifyChainIntegrity(): array
    {
        // Get all distinct company IDs
        $companies = DB::table('audit_logs')->distinct()->pluck('entreprise_id');
        $allBroken = [];
        foreach ($companies as $entrepriseId) {
            $allBroken = array_merge($allBroken, $this->verifyChain((int) $entrepriseId));
        }

        return $allBroken;
    }

    protected function computeHash(array $data): string
    {
        $normalized = [
            $data['entreprise_id'],
            $data['sequence'],
            $data['action'],
            $data['model'],
            $data['model_id'] ?? null,
            json_encode($data['old_values'] ?? []),
            json_encode($data['new_values'] ?? []),
            $data['trace_id'] ?? null,
            $data['previous_hash'],
        ];

        return hash('sha256', implode('|', $normalized));
    }
}
