<?php

namespace App\Services;

use App\Helpers\Logging;
use App\Models\Intent;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class IntentService
{
    /**
     * Verify the intent of a command.
     *
     * @throws RuntimeException
     */
    public function verify(string $commandType, array $command): void
    {
        $verifierClass = Cache::rememberForever("intent_verifier:{$commandType}", function () use ($commandType) {
            return Intent::where('command_type', $commandType)->where('is_active', true)->value('verifier_class');
        });

        if (! $verifierClass) {
            // Log skip
            DB::table('decision_audit')->insert([
                'decision_type' => 'intent',
                'context' => json_encode(['command_type' => $commandType, 'command' => $command]),
                'result' => true,
                'correlation_id' => Logging::getCorrelationId(),
                'made_at' => now(),
            ]);

            return;
        }

        $verifier = App::make($verifierClass);
        $result = $verifier->verify($command);

        DB::table('decision_audit')->insert([
            'decision_type' => 'intent',
            'context' => json_encode(['command_type' => $commandType, 'command' => $command]),
            'result' => $result,
            'correlation_id' => Logging::getCorrelationId(),
            'made_at' => now(),
        ]);

        if (! $result) {
            throw new RuntimeException("Intent verification failed for command: {$commandType}");
        }
    }
}
