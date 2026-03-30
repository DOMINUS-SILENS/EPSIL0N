<?php

namespace App\Services\Sequences;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class BusinessReferenceSequenceService
{
    /**
     * Generates a human-auditable document reference number (e.g. INV-2026-0012).
     */
    public function generateReference(int|string $tenantId, string $documentPrefix, string $fiscalPeriod): string
    {
        $key = "seq:{$tenantId}:business:{$documentPrefix}:{$fiscalPeriod}";
        
        $incremental = Redis::incr($key);
        
        if ($incremental === 1) {
            $dbIncremental = DB::table('sequence_heads')
                          ->where('tenant_id', $tenantId)
                          ->where('sequence_type', "bus_{$documentPrefix}_{$fiscalPeriod}")
                          ->value('current_value') ?? 0;
            $incremental = $dbIncremental + 1;
            Redis::set($key, $incremental);
        }

        // Periodic durable flush
        if ($incremental % 10 === 0) {
            DB::table('sequence_heads')->updateOrInsert(
                [
                    'tenant_id' => $tenantId,
                    'sequence_type' => "bus_{$documentPrefix}_{$fiscalPeriod}"
                ],
                [
                    'current_value' => $incremental,
                    'updated_at' => now()
                ]
            );
        }

        // Pad sequence
        $formatted = str_pad($incremental, 6, '0', STR_PAD_LEFT);
        return "{$documentPrefix}-{$fiscalPeriod}-{$formatted}";
    }
}
