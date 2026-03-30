<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * Sequence Validator - Optimized for batch sync operations
 * Validates causal ordering of events per aggregate
 */
class SequenceValidator
{
    /** @var array Request-level cache for sequences */
    protected array $sequenceCache = [];

    /**
     * Validates that the upcoming sequence is exactly the next in line
     * for the aggregate (current_sequence + 1). If valid, it updates the sequence.
     *
     * Legacy method - kept for backward compatibility
     */
    public function isValid(string $aggregateType, string $aggregateId, int $sequence, int $entrepriseId): bool
    {
        return $this->isValidWithUpdate($aggregateType, $aggregateId, $sequence, $entrepriseId);
    }

    /**
     * Validate sequence and update in transaction
     */
    protected function isValidWithUpdate(string $aggregateType, string $aggregateId, int $sequence, int $entrepriseId): bool
    {
        $cacheKey = "{$entrepriseId}:{$aggregateType}:{$aggregateId}";

        return DB::transaction(function () use ($aggregateType, $aggregateId, $sequence, $entrepriseId, $cacheKey) {
            $currentSequence = DB::table('aggregate_sequences')
                ->where('entreprise_id', $entrepriseId)
                ->where('aggregate_type', $aggregateType)
                ->where('aggregate_id', $aggregateId)
                ->lockForUpdate()
                ->value('current_sequence');

            $currentSequence = $currentSequence ? (int) $currentSequence : 0;

            if ($sequence === $currentSequence + 1) {
                DB::table('aggregate_sequences')->updateOrInsert(
                    [
                        'entreprise_id' => $entrepriseId,
                        'aggregate_type' => $aggregateType,
                        'aggregate_id' => $aggregateId
                    ],
                    [
                        'current_sequence' => $sequence,
                        'updated_at' => now()
                    ]
                );

                // Update cache
                $this->sequenceCache[$cacheKey] = $sequence;

                return true;
            }

            return false;
        });
    }

    /**
     * Non-transactional validation for batch pre-checking
     * Uses cache to avoid database hits
     */
    public function isValidCached(string $aggregateType, string $aggregateId, int $sequence, int $entrepriseId): bool
    {
        $cacheKey = "{$entrepriseId}:{$aggregateType}:{$aggregateId}";

        // Check request-level cache first
        if (isset($this->sequenceCache[$cacheKey])) {
            return $sequence === $this->sequenceCache[$cacheKey] + 1;
        }

        // Check Laravel cache (short TTL)
        $currentSequence = Cache::store('array')->remember(
            "seq:{$cacheKey}",
            1, // 1 second TTL
            function () use ($entrepriseId, $aggregateType, $aggregateId) {
                return DB::table('aggregate_sequences')
                    ->where('entreprise_id', $entrepriseId)
                    ->where('aggregate_type', $aggregateType)
                    ->where('aggregate_id', $aggregateId)
                    ->value('current_sequence') ?? 0;
            }
        );

        return $sequence === ((int) $currentSequence) + 1;
    }

    /**
     * Atomic validation for batch processing
     * Optionally defers the actual update
     */
    public function isValidAtomic(string $aggregateType, string $aggregateId, int $sequence, int $entrepriseId, bool $updateImmediately = true): bool
    {
        $cacheKey = "{$entrepriseId}:{$aggregateType}:{$aggregateId}";

        // Fast path: check cache
        if (isset($this->sequenceCache[$cacheKey])) {
            $expected = $this->sequenceCache[$cacheKey] + 1;
            if ($sequence === $expected) {
                if ($updateImmediately) {
                    $this->sequenceCache[$cacheKey] = $sequence;
                }
                return true;
            }
            return false;
        }

        // Get current sequence with lock
        $currentSequence = DB::table('aggregate_sequences')
            ->where('entreprise_id', $entrepriseId)
            ->where('aggregate_type', $aggregateType)
            ->where('aggregate_id', $aggregateId)
            ->lockForUpdate()
            ->value('current_sequence');

        $currentSequence = $currentSequence ? (int) $currentSequence : 0;

        if ($sequence === $currentSequence + 1) {
            if ($updateImmediately) {
                DB::table('aggregate_sequences')->updateOrInsert(
                    [
                        'entreprise_id' => $entrepriseId,
                        'aggregate_type' => $aggregateType,
                        'aggregate_id' => $aggregateId
                    ],
                    [
                        'current_sequence' => $sequence,
                        'updated_at' => now()
                    ]
                );
            }

            $this->sequenceCache[$cacheKey] = $sequence;
            return true;
        }

        return false;
    }

    /**
     * Batch update sequences after successful batch processing
     *
     * @param array $validations Array of ['aggregateType', 'aggregateId', 'sequence']
     * @param int $entrepriseId
     */
    public function updateSequencesBatch(array $validations, int $entrepriseId): void
    {
        if (empty($validations)) {
            return;
        }

        // Group by aggregate to get max sequence per aggregate
        $sequences = [];
        foreach ($validations as $validation) {
            $key = "{$validation['aggregateType']}:{$validation['aggregateId']}";
            $sequences[$key] = max($sequences[$key] ?? 0, $validation['sequence']);
        }

        // Batch update
        $now = now();
        foreach ($sequences as $key => $maxSequence) {
            [$aggregateType, $aggregateId] = explode(':', $key, 2);

            DB::table('aggregate_sequences')->updateOrInsert(
                [
                    'entreprise_id' => $entrepriseId,
                    'aggregate_type' => $aggregateType,
                    'aggregate_id' => $aggregateId
                ],
                [
                    'current_sequence' => $maxSequence,
                    'updated_at' => $now
                ]
            );
        }
    }

    /**
     * Get next expected sequence for an aggregate
     */
    public function getNextSequence(string $aggregateType, string $aggregateId, int $entrepriseId): int
    {
        $cacheKey = "{$entrepriseId}:{$aggregateType}:{$aggregateId}";

        if (isset($this->sequenceCache[$cacheKey])) {
            return $this->sequenceCache[$cacheKey] + 1;
        }

        $current = DB::table('aggregate_sequences')
            ->where('entreprise_id', $entrepriseId)
            ->where('aggregate_type', $aggregateType)
            ->where('aggregate_id', $aggregateId)
            ->value('current_sequence') ?? 0;

        return ((int) $current) + 1;
    }

    /**
     * Get sequence gaps for an aggregate (for debugging)
     */
    public function getSequenceGaps(string $aggregateType, string $aggregateId, int $entrepriseId): array
    {
        $currentSeq = DB::table('aggregate_sequences')
            ->where('entreprise_id', $entrepriseId)
            ->where('aggregate_type', $aggregateType)
            ->where('aggregate_id', $aggregateId)
            ->value('current_sequence') ?? 0;

        // Get all event sequences for this aggregate
        $eventSequences = DB::table('event_store')
            ->where('aggregate_type', $aggregateType)
            ->where('aggregate_id', $aggregateId)
            ->orderBy('local_sequence')
            ->pluck('local_sequence')
            ->toArray();

        $gaps = [];
        $expected = 1;
        foreach ($eventSequences as $seq) {
            while ($expected < $seq) {
                $gaps[] = $expected;
                $expected++;
            }
            $expected = $seq + 1;
        }

        return [
            'current_sequence' => $currentSeq,
            'highest_sequence' => max($eventSequences) ?? 0,
            'gaps' => $gaps,
            'gap_count' => count($gaps),
        ];
    }

    /**
     * Clear the request-level cache
     */
    public function clearCache(): void
    {
        $this->sequenceCache = [];
    }
}
