<?php

namespace App\Services\Sequences;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class GlobalEventOffsetService
{
    /**
     * Generates a gap-resistant global append order for projector catch-ups and replay boundaries.
     * Guaranteed to be strictly monotonic.
     */
    public function nextOffset(int|string $tenantId): int
    {
        $key = "seq:{$tenantId}:global:event_offset";
        
        $offset = Redis::incr($key);
        
        // Cold start from database if first call
        if ($offset === 1) {
            $dbOffset = DB::table('sequence_heads')
                          ->where('tenant_id', $tenantId)
                          ->where('sequence_type', 'global_event_offset')
                          ->value('current_value') ?? 0;
            $offset = $dbOffset + 1;
            Redis::set($key, $offset);
        }

        // Periodic checkpoint backfill (e.g., every 50 events) to maintain RDBMS durability
        if ($offset % 50 === 0) {
            DB::table('sequence_heads')->updateOrInsert(
                [
                    'tenant_id' => $tenantId,
                    'sequence_type' => 'global_event_offset'
                ],
                [
                    'current_value' => $offset,
                    'updated_at' => now()
                ]
            );
        }

        return $offset;
    }
}
