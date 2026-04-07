<?php

declare(strict_types=1);

namespace Spiral\Kernel\Infrastructure\Persistence\Projection;

use Spiral\Kernel\Infrastructure\Contract\MobileSync\IMobileSyncSurface;
use Spiral\Kernel\Infrastructure\Contract\MobileSync\SyncResult;

/**
 * Implementation of SDR-007: Mobile Command Inbox.
 * Ensures absolute idempotency for mobile-originated writes.
 */
final class MobileSyncSurface implements IMobileSyncSurface
{
    /**
     * @var array<string, array{device_id: string, user_id: string, processed_at: float}>
     */
    private array $processedCommands = [];

    /**
     * @param array<string, mixed> $payload
     */
    public function handleIntent(
        string $commandId,
        string $deviceId,
        string $userId,
        string $commandType,
        string $aggregateId,
        int $expectedVersion,
        array $payload
    ): SyncResult {
        // Sync Law 2: Idempotency check
        if (isset($this->processedCommands[$commandId])) {
            return SyncResult::alreadyProcessed();
        }

        // In a real implementation, this would trigger a Command Handler.
        // We simulate a successful "Acceptance" here.

        $this->processedCommands[$commandId] = [
            'device_id' => $deviceId,
            'user_id' => $userId,
            'processed_at' => microtime(true)
        ];

        return SyncResult::accepted();
    }
}
