<?php

declare(strict_types=1);

namespace Spiral\Kernel\Infrastructure\Persistence\MobileSync;

/**
 * Result of processing a single queued event.
 *
 * @package Spiral\Kernel\Infrastructure\Persistence\MobileSync
 */
final class EventProcessingResult
{
    private function __construct(
        public readonly ProcessingStatus $status,
        public readonly ?int $newVersion = null,
        public readonly ?string $resolutionNote = null,
        /** @var array<string, mixed>|null */
        public readonly ?array $conflictData = null,
        public readonly ?string $errorMessage = null,
    ) {}

    /**
     * Event was successfully synced.
     */
    public static function synced(int $newVersion): self
    {
        return new self(
            status: ProcessingStatus::Synced,
            newVersion: $newVersion,
        );
    }

    /**
     * Event was merged with conflicting event.
     */
    public static function merged(int $newVersion, string $resolutionNote): self
    {
        return new self(
            status: ProcessingStatus::Merged,
            newVersion: $newVersion,
            resolutionNote: $resolutionNote,
        );
    }

    /**
     * Event was rejected due to error.
     */
    public static function rejected(string $errorMessage): self
    {
        return new self(
            status: ProcessingStatus::Rejected,
            errorMessage: $errorMessage,
        );
    }

    /**
     * Event is in conflict and needs resolution.
     *
     * @param array<string, mixed> $conflictData
     */
    public static function conflict(array $conflictData): self
    {
        return new self(
            status: ProcessingStatus::Conflict,
            conflictData: $conflictData,
        );
    }

    /**
     * Check if the event was successfully processed.
     */
    public function isSuccess(): bool
    {
        return $this->status === ProcessingStatus::Synced
            || $this->status === ProcessingStatus::Merged;
    }

    /**
     * Check if the event needs manual resolution.
     */
    public function needsResolution(): bool
    {
        return $this->status === ProcessingStatus::Conflict;
    }
}
