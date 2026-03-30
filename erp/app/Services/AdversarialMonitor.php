<?php

namespace App\Services;

use App\Models\Anomaly;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AdversarialMonitor
{
    public function recordCommand(string $commandType, array $command, string $idempotencyKey): void
    {
        $key = "cmd:{$commandType}:{$idempotencyKey}";

        if (Cache::has($key)) {
            // Duplicate command detected
            Anomaly::create([
                'type' => 'duplicate_command',
                'context' => [
                    'command_type' => $commandType,
                    'idempotency_key' => $idempotencyKey,
                    'first_seen' => Cache::get($key),
                ],
            ]);
            Log::warning('Duplicate command detected', ['type' => $commandType, 'key' => $idempotencyKey]);
        } else {
            Cache::put($key, now(), 3600); // remember for 1 hour
        }
    }

    public function checkRetryRate(string $commandType, string $userId): void
    {
        $key = "retry:{$commandType}:{$userId}";
        $attempts = Cache::get($key, 0) + 1;
        Cache::put($key, $attempts, 60); // 60 seconds window

        if ($attempts > 10) {
            Anomaly::create([
                'type' => 'excessive_retry',
                'context' => [
                    'command_type' => $commandType,
                    'user_id' => $userId,
                    'attempts' => $attempts,
                ],
            ]);
            Log::warning('Excessive retry rate', ['type' => $commandType, 'user' => $userId, 'attempts' => $attempts]);
        }
    }

    public function checkGovernanceAnomalies(): void
    {
        // Example: count contract failures in last hour
        $failures = DB::table('decision_audit')
            ->where('decision_type', 'contract')
            ->where('result', false)
            ->where('made_at', '>=', now()->subHour())
            ->count();

        if ($failures > 10) {
            Anomaly::create([
                'type' => 'governance_anomaly',
                'context' => ['contract_failures' => $failures, 'period' => '1h'],
            ]);
            Log::warning('High contract failure rate', ['failures' => $failures]);
        }

        // Similarly for intent failures, saga failures, etc.
    }
}
