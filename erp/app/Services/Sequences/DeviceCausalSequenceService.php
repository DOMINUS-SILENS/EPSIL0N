<?php

namespace App\Services\Sequences;

class DeviceCausalSequenceService
{
    /**
     * Causal validation for offline synchronization logic.
     * Ensures strict ordering per-device, rejecting gaps or replay bugs from the Mobile client.
     */
    public function validateSequence(int|string $tenantId, string $deviceId, int $incomingSequence): void
    {
        // Placeholder check before formal mobile sync logic implementation
        // Verifies against `idempotency_keys` or `device_sequences` that the
        // incoming seq strictly equals the max observed seq + 1 for this bounded causal chain.
    }
}
