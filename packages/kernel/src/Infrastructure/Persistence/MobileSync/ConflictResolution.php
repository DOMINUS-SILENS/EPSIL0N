<?php

declare(strict_types=1);

namespace Spiral\Kernel\Infrastructure\Persistence\MobileSync;

use Spiral\Kernel\Domain\Sync\SyncVersion;
use Spiral\Kernel\Domain\Shared\Event\DomainEvent;

/**
 * Result of a conflict resolution.
 *
 * @package Spiral\Kernel\Infrastructure\Persistence\MobileSync
 */
final class ConflictResolution
{
    private function __construct(
        public readonly bool $shouldAccept,
        public readonly string $resolutionNote,
        public readonly SyncVersion $mergedVersion,
        public readonly ?DomainEvent $resolvedEvent = null,
        /** @var array<string, mixed>|null */
        public readonly ?array $conflictData = null,
    ) {}

    /**
     * Accept the client event.
     */
    public static function accept(
        string $resolutionNote,
        SyncVersion $mergedVersion,
    ): self {
        return new self(
            shouldAccept: true,
            resolutionNote: $resolutionNote,
            mergedVersion: $mergedVersion,
        );
    }

    /**
     * Reject the client event (server wins).
     *
     * @param array<string, mixed> $conflictData
     */
    public static function reject(
        string $resolutionNote,
        array $conflictData,
    ): self {
        return new self(
            shouldAccept: false,
            resolutionNote: $resolutionNote,
            mergedVersion: SyncVersion::empty(),
            conflictData: $conflictData,
        );
    }

    /**
     * Merge the events (operational transform).
     *
     * @param array<string, mixed> $mergedPayload
     */
    public static function merge(
        string $resolutionNote,
        SyncVersion $mergedVersion,
        array $mergedPayload,
    ): self {
        return new self(
            shouldAccept: true,
            resolutionNote: $resolutionNote,
            mergedVersion: $mergedVersion,
            // Note: resolvedEvent would need to be constructed from mergedPayload
            // This requires a domain event factory
        );
    }

    /**
     * Check if this resolution requires manual intervention.
     */
    public function requiresManualResolution(): bool
    {
        return !$this->shouldAccept && $this->conflictData !== null;
    }
}
