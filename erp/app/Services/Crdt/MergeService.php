<?php

namespace App\Services\Crdt;

/**
 * Conflict-Free Replicated Data Types (CRDT) Merge Service.
 * Handles offline-first synchronization for mobile clients.
 */
class MergeService
{
    /**
     * Merge two G-Counters (increment-only counters).
     * Returns the element-wise max of both counters.
     */
    public function mergeGCounter(array $counterA, array $counterB): array
    {
        $result = [];
        $allKeys = array_unique(array_merge(array_keys($counterA), array_keys($counterB)));

        foreach ($allKeys as $key) {
            $valA = $counterA[$key] ?? 0;
            $valB = $counterB[$key] ?? 0;
            $result[$key] = max($valA, $valB);
        }

        return $result;
    }

    /**
     * Merge two PN-Counters (positive/negative counters).
     * Supports increments and decrements.
     */
    public function mergePNCounter(array $counterA, array $counterB): array
    {
        return [
            'p' => $this->mergeGCounter($counterA['p'] ?? [], $counterB['p'] ?? []),
            'n' => $this->mergeGCounter($counterA['n'] ?? [], $counterB['n'] ?? []),
        ];
    }

    /**
     * Calculate PN-Counter value (sum of positives minus sum of negatives).
     */
    public function calculatePNValue(array $pnCounter): int
    {
        $positive = array_sum($pnCounter['p'] ?? []);
        $negative = array_sum($pnCounter['n'] ?? []);
        return $positive - $negative;
    }

    /**
     * Merge Last-Write-Wins (LWW) registers.
     * Uses vector clock comparison for deterministic ordering.
     */
    public function mergeLWWRegister(array $regA, array $regB): array
    {
        $clockA = $regA['vector_clock'] ?? [];
        $clockB = $regB['vector_clock'] ?? [];

        $comparison = $this->compareVectorClocks($clockA, $clockB);

        if ($comparison === 1) {
            return $regA; // A is newer
        } elseif ($comparison === -1) {
            return $regB; // B is newer
        } else {
            // Concurrent - use tie-breaker (higher replica ID wins)
            $replicaA = $regA['replica_id'] ?? '';
            $replicaB = $regB['replica_id'] ?? '';
            return $replicaA > $replicaB ? $regA : $regB;
        }
    }

    /**
     * Compare vector clocks. Returns: 1 (a > b), -1 (a < b), 0 (concurrent).
     */
    public function compareVectorClocks(array $clockA, array $clockB): int
    {
        $allNodes = array_unique(array_merge(array_keys($clockA), array_keys($clockB)));

        $aGreater = false;
        $bGreater = false;

        foreach ($allNodes as $node) {
            $valA = $clockA[$node] ?? 0;
            $valB = $clockB[$node] ?? 0;

            if ($valA > $valB) {
                $aGreater = true;
            } elseif ($valB > $valA) {
                $bGreater = true;
            }
        }

        if ($aGreater && !$bGreater) return 1;
        if ($bGreater && !$aGreater) return -1;
        return 0;
    }

    /**
     * Increment vector clock for a specific node.
     */
    public function incrementVectorClock(array $clock, string $nodeId): array
    {
        $clock[$nodeId] = ($clock[$nodeId] ?? 0) + 1;
        return $clock;
    }

    /**
     * Merge two OR-Sets (Observed-Removed Sets) for client lists.
     */
    public function mergeORSet(array $setA, array $setB): array
    {
        $adds = array_merge($setA['adds'] ?? [], $setB['adds'] ?? []);
        $removes = array_merge($setA['removes'] ?? [], $setB['removes'] ?? []);

        return [
            'adds' => array_unique($adds),
            'removes' => array_unique($removes),
            'elements' => array_values(array_diff($adds, $removes)),
        ];
    }

    /**
     * State-based CRDT merge for full document sync.
     */
    public function mergeDocumentStates(array $localState, array $remoteState): array
    {
        $merged = [];

        foreach ($localState as $field => $localValue) {
            $remoteValue = $remoteState[$field] ?? null;

            if ($remoteValue === null) {
                $merged[$field] = $localValue;
            } elseif (isset($localValue['crdt_type'])) {
                // CRDT-aware field
                $merged[$field] = match ($localValue['crdt_type']) {
                    'gcounter' => ['crdt_type' => 'gcounter', 'value' => $this->mergeGCounter($localValue['value'], $remoteValue['value'])],
                    'pncounter' => ['crdt_type' => 'pncounter', 'value' => $this->mergePNCounter($localValue['value'], $remoteValue['value'])],
                    'lww' => ['crdt_type' => 'lww', 'value' => $this->mergeLWWRegister($localValue['value'], $remoteValue['value'])],
                    'orset' => ['crdt_type' => 'orset', 'value' => $this->mergeORSet($localValue['value'], $remoteValue['value'])],
                    default => $localValue,
                };
            } else {
                // Non-CRDT field - use LWW
                $merged[$field] = $this->mergeLWWRegister(
                    ['value' => $localValue, 'vector_clock' => $localState['_clocks'][$field] ?? [], 'replica_id' => $localState['_replica_id'] ?? ''],
                    ['value' => $remoteValue, 'vector_clock' => $remoteState['_clocks'][$field] ?? [], 'replica_id' => $remoteState['_replica_id'] ?? '']
                )['value'];
            }
        }

        // Add remote-only fields
        foreach ($remoteState as $field => $remoteValue) {
            if (!isset($localState[$field])) {
                $merged[$field] = $remoteValue;
            }
        }

        return $merged;
    }
}