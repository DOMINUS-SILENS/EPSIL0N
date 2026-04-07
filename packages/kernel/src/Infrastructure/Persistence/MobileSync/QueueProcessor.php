<?php

declare(strict_types=1);

namespace Spiral\Kernel\Infrastructure\Persistence\MobileSync;

use Spiral\Kernel\Domain\Identity\DeviceId;
use Spiral\Kernel\Domain\Identity\TenantId;
use Spiral\Kernel\Domain\Sync\SyncVersion;
use Spiral\Kernel\Domain\Sync\SyncStatus;
use Spiral\Kernel\Infrastructure\Contract\EventStore\IEventStore;
use Spiral\Kernel\Infrastructure\Contract\MobileSync\IMobileOfflineQueue;
use Spiral\Kernel\Infrastructure\Contract\MobileSync\QueuedEvent;
use Spiral\Kernel\Domain\Shared\Event\ExpectedVersion;

/**
 * Processes offline queue events with vector clock merge.
 *
 * QueueProcessor handles the sync lifecycle:
 * 1. Fetch pending events from offline queue
 * 2. Detect conflicts via vector clock comparison
 * 3. Apply conflict resolution strategy
 * 4. Persist events to event store
 * 5. Update queue status
 *
 * @package Spiral\Kernel\Infrastructure\Persistence\MobileSync
 */
final class QueueProcessor
{
    public function __construct(
        private readonly IMobileOfflineQueue $queue,
        private readonly IEventStore $eventStore,
        private readonly ConflictResolver $conflictResolver,
    ) {}

    /**
     * Process all pending events for a device.
     *
     * Returns a processing report with results.
     */
    public function processDeviceQueue(TenantId $tenantId, DeviceId $deviceId): ProcessingReport
    {
        $pendingEvents = $this->queue->getSyncBatch($tenantId, $deviceId);
        $report = new ProcessingReport();

        foreach ($pendingEvents as $queuedEvent) {
            $result = $this->processEvent($tenantId, $queuedEvent);
            $report->addResult($queuedEvent->queueItemId, $result);
        }

        return $report;
    }

    /**
     * Process a single queued event.
     */
    public function processEvent(TenantId $tenantId, QueuedEvent $queuedEvent): EventProcessingResult
    {
        $event = $queuedEvent->event;
        $streamId = $this->buildStreamId($event);

        // Get current server version
        $serverVersion = $this->eventStore->getStreamVersion($tenantId, $streamId);

        // Get the sync metadata from the event
        $syncMetadata = $event->getSyncMetadata();

        if ($syncMetadata === null) {
            // No sync metadata - treat as regular event
            return $this->appendWithoutSync($tenantId, $streamId, $queuedEvent);
        }

        $clientVersion = $syncMetadata->syncVersion;

        // Check for conflicts using vector clock
        if ($serverVersion > 0) {
            $serverSyncVersion = $this->getServerSyncVersion($tenantId, $streamId);

            if ($clientVersion->isConcurrent($serverSyncVersion)) {
                // Conflict detected - apply resolution strategy
                return $this->resolveConflict($tenantId, $streamId, $queuedEvent, $serverSyncVersion);
            }
        }

        // No conflict - append event
        return $this->appendEvent($tenantId, $streamId, $queuedEvent, $serverVersion);
    }

    /**
     * Append an event without sync metadata (regular online event).
     */
    private function appendWithoutSync(
        TenantId $tenantId,
        string $streamId,
        QueuedEvent $queuedEvent,
    ): EventProcessingResult {
        try {
            $newVersion = $this->eventStore->append(
                $tenantId,
                $streamId,
                ExpectedVersion::any(),
                [$queuedEvent->event]
            );

            $this->queue->markSynced($queuedEvent->queueItemId, SyncVersion::empty());

            return EventProcessingResult::synced($newVersion);
        } catch (\Throwable $e) {
            $this->queue->updateStatus(
                $queuedEvent->queueItemId,
                SyncStatus::Rejected,
                $e->getMessage()
            );

            return EventProcessingResult::rejected($e->getMessage());
        }
    }

    /**
     * Append an event with sync metadata.
     */
    private function appendEvent(
        TenantId $tenantId,
        string $streamId,
        QueuedEvent $queuedEvent,
        int $serverVersion,
    ): EventProcessingResult {
        try {
            $newVersion = $this->eventStore->append(
                $tenantId,
                $streamId,
                ExpectedVersion::from($serverVersion),
                [$queuedEvent->event]
            );

            // Update the queue with merged version
            $syncMetadata = $queuedEvent->event->getSyncMetadata();
            $mergedVersion = $syncMetadata !== null
                ? $syncMetadata->syncVersion->increment($queuedEvent->deviceId)
                : SyncVersion::empty();

            $this->queue->markSynced($queuedEvent->queueItemId, $mergedVersion);

            return EventProcessingResult::synced($newVersion);
        } catch (\Spiral\Kernel\Support\Exception\ConcurrencyConflictException $e) {
            // Optimistic concurrency failure - re-check for conflict
            $serverSyncVersion = $this->getServerSyncVersion($tenantId, $streamId);
            return $this->resolveConflict($tenantId, $streamId, $queuedEvent, $serverSyncVersion);
        } catch (\Throwable $e) {
            $this->queue->updateStatus(
                $queuedEvent->queueItemId,
                SyncStatus::Rejected,
                $e->getMessage()
            );

            return EventProcessingResult::rejected($e->getMessage());
        }
    }

    /**
     * Resolve a conflict using the configured strategy.
     */
    private function resolveConflict(
        TenantId $tenantId,
        string $streamId,
        QueuedEvent $queuedEvent,
        SyncVersion $serverVersion,
    ): EventProcessingResult {
        $resolution = $this->conflictResolver->resolve(
            $queuedEvent,
            $serverVersion,
            $this->eventStore->load($tenantId, $streamId)
        );

        if ($resolution->shouldAccept) {
            // Client wins or merge succeeded
            $newVersion = $this->eventStore->append(
                $tenantId,
                $streamId,
                ExpectedVersion::any(),
                [$resolution->resolvedEvent ?? $queuedEvent->event]
            );

            $this->queue->markSynced(
                $queuedEvent->queueItemId,
                $resolution->mergedVersion
            );

            return EventProcessingResult::merged($newVersion, $resolution->resolutionNote);
        }

        // Server wins or conflict requires manual resolution
        $this->queue->markConflict(
            $queuedEvent->queueItemId,
            $resolution->conflictData
        );

        return EventProcessingResult::conflict($resolution->conflictData);
    }

    /**
     * Get the sync version for an aggregate from the event store.
     */
    private function getServerSyncVersion(TenantId $tenantId, string $streamId): SyncVersion
    {
        $events = $this->eventStore->load($tenantId, $streamId);

        $merged = SyncVersion::empty();
        foreach ($events as $storedEvent) {
            $syncMetadata = $storedEvent->event->getSyncMetadata();
            if ($syncMetadata !== null) {
                $merged = $merged->merge($syncMetadata->syncVersion);
            }
        }

        return $merged;
    }

    /**
     * Build a stream ID from an event.
     */
    private function buildStreamId($event): string
    {
        // Extract aggregate type and ID from event
        $eventType = $event->getEventType();
        // This should be overridden per domain - placeholder implementation
        return sprintf('%s:%s', $eventType, $event->getEventId()->toString());
    }
}
