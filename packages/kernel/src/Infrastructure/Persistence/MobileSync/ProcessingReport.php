<?php

declare(strict_types=1);

namespace Spiral\Kernel\Infrastructure\Persistence\MobileSync;

/**
 * Report of queue processing results.
 *
 * @package Spiral\Kernel\Infrastructure\Persistence\MobileSync
 */
final class ProcessingReport
{
    /** @var array<string, EventProcessingResult> */
    private array $results = [];

    private int $synced = 0;
    private int $rejected = 0;
    private int $conflicts = 0;
    private int $merged = 0;

    /**
     * Add a processing result.
     */
    public function addResult(string $queueItemId, EventProcessingResult $result): void
    {
        $this->results[$queueItemId] = $result;

        match ($result->status) {
            ProcessingStatus::Synced => $this->synced++,
            ProcessingStatus::Rejected => $this->rejected++,
            ProcessingStatus::Conflict => $this->conflicts++,
            ProcessingStatus::Merged => $this->merged++,
        };
    }

    /**
     * Get all results.
     *
     * @return array<string, EventProcessingResult>
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * Get count of synced events.
     */
    public function getSyncedCount(): int
    {
        return $this->synced;
    }

    /**
     * Get count of rejected events.
     */
    public function getRejectedCount(): int
    {
        return $this->rejected;
    }

    /**
     * Get count of conflicts.
     */
    public function getConflictCount(): int
    {
        return $this->conflicts;
    }

    /**
     * Get count of merged events.
     */
    public function getMergedCount(): int
    {
        return $this->merged;
    }

    /**
     * Get total events processed.
     */
    public function getTotalCount(): int
    {
        return $this->synced + $this->rejected + $this->conflicts + $this->merged;
    }

    /**
     * Check if all events were successfully processed.
     */
    public function isComplete(): bool
    {
        return $this->conflicts === 0 && $this->rejected === 0;
    }

    /**
     * Get a summary string.
     */
    public function getSummary(): string
    {
        return sprintf(
            'Processed %d events: %d synced, %d merged, %d rejected, %d conflicts',
            $this->getTotalCount(),
            $this->synced,
            $this->merged,
            $this->rejected,
            $this->conflicts
        );
    }
}
