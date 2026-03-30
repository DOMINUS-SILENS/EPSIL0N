<?php

namespace App\Services\Sequences;

use Illuminate\Support\Facades\DB;

class AggregateSequenceService
{
    private array $leases = [];

    /**
     * CQRS Formal Axiom: Lease-Based Sequence Allocation for Aggregate Streams.
     */
    public function ensureExists(string $aggregateType, string|int $aggregateId): void
    {
    }

    public function next(int|string $tenantIdOrAggregateType, string|int $aggregateTypeOrId, string|int|null $aggregateId = null): int
    {
        if ($aggregateId === null) {
            $tenantId = 1;
            $aggregateType = (string) $tenantIdOrAggregateType;
            $aggregateId = (string) $aggregateTypeOrId;
        } else {
            $tenantId = (int) $tenantIdOrAggregateType;
            $aggregateType = (string) $aggregateTypeOrId;
            $aggregateId = (string) $aggregateId;
        }

        $key = "seq:{$tenantId}:aggregate:{$aggregateType}:{$aggregateId}";

        if (!isset($this->leases[$key]) || $this->leases[$key]['current'] > $this->leases[$key]['max']) {
            $this->allocateLease($tenantId, $aggregateType, $aggregateId, $key);
        }

        return $this->leases[$key]['current']++;
    }

    private function allocateLease(int $tenantId, string $aggregateType, string $aggregateId, string $key): void
    {
        $blockSize = 100;

        // 1. Redis Lease Allocation
        $maxSeq = \Illuminate\Support\Facades\Redis::incrby($key, $blockSize);
        
        // 2. Cold Start Recovery from SQL
        if ($maxSeq === $blockSize) {
            $dbSeq = DB::table('aggregate_sequences')
                ->where('tenant_id', $tenantId)
                ->where('aggregate_type', $aggregateType)
                ->where('aggregate_id', $aggregateId)
                ->value('seq') ?? 0;
                
            $maxSeq = $dbSeq + $blockSize;
            \Illuminate\Support\Facades\Redis::set($key, $maxSeq);
        }

        // 3. Final Truth: Write lease upper bound to SQL securely
        DB::table('aggregate_sequences')->updateOrInsert(
            [
                'tenant_id' => $tenantId,
                'aggregate_type' => $aggregateType,
                'aggregate_id' => $aggregateId
            ],
            ['seq' => $maxSeq]
        );

        // 4. Set local process memory block limits
        $this->leases[$key] = [
            'current' => $maxSeq - $blockSize + 1,
            'max' => $maxSeq,
        ];
    }
}
