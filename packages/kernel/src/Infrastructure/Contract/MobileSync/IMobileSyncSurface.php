<?php

declare(strict_types=1);

namespace Spiral\Kernel\Infrastructure\Contract\MobileSync;

/**
 * Sync Law 4: Sync feeds must be explicit surfaces.
 * This interface prevents leakage of internal domain/infrastructure logic to the mobile client.
 */
interface IMobileSyncSurface
{
    /**
     * Processes a mobile intent.
     * Sync Law 2: Every mobile write must be idempotent.
     *
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
    ): SyncResult;
}

final class SyncResult
{
    public function __construct(
        public readonly string $status, // 'Accepted', 'Rejected', 'AlreadyProcessed', 'Conflict'
        public readonly ?string $error = null,
        /** @var array<string, mixed>|null */
        public readonly ?array $conflictPayload = null
    ) {}

    public static function accepted(): self { return new self('Accepted'); }
    public static function rejected(string $error): self { return new self('Rejected', $error); }
    public static function alreadyProcessed(): self { return new self('AlreadyProcessed'); }
    /**
     * @param array<string, mixed> $payload
     */
    public static function conflict(array $payload): self { return new self('Conflict', null, $payload); }
}
