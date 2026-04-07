<?php

declare(strict_types=1);

namespace Spiral\Kernel\Infrastructure\Persistence\MobileSync;

use Spiral\Kernel\Domain\Identity\DeviceId;
use Spiral\Kernel\Domain\Sync\ConflictStrategy;
use Spiral\Kernel\Domain\Sync\SyncVersion;
use Spiral\Kernel\Infrastructure\Contract\MobileSync\QueuedEvent;
use Spiral\Kernel\Domain\Shared\Event\DomainEvent;
use Spiral\Kernel\Domain\Shared\Event\StoredEvent;

/**
 * Resolves conflicts between concurrent offline edits.
 *
 * Supports multiple resolution strategies:
 * - LastWriterWins / FirstWriterWins: Timestamp-based
 * - DevicePriority: Configurable device hierarchy
 * - ServerWins / ClientWins: Authority-based
 * - OperationalTransform: Intent-preserving merge
 *
 * @package Spiral\Kernel\Infrastructure\Persistence\MobileSync
 */
final class ConflictResolver
{
    /**
     * @param ConflictStrategy $defaultStrategy Default resolution strategy
     * @param array<non-empty-string, int> $devicePriorities Device ID => priority mapping
     */
    public function __construct(
        private readonly ConflictStrategy $defaultStrategy = ConflictStrategy::LastWriterWins,
        private readonly array $devicePriorities = [],
    ) {}

    /**
     * Resolve a conflict between a client event and server state.
     *
     * @param list<StoredEvent> $serverEvents
     */
    public function resolve(
        QueuedEvent $clientEvent,
        SyncVersion $serverVersion,
        array $serverEvents,
    ): ConflictResolution {
        $strategy = $this->determineStrategy($clientEvent);

        return match ($strategy) {
            ConflictStrategy::LastWriterWins => $this->resolveLastWriterWins($clientEvent, $serverEvents),
            ConflictStrategy::FirstWriterWins => $this->resolveFirstWriterWins($clientEvent, $serverEvents),
            ConflictStrategy::DevicePriority => $this->resolveDevicePriority($clientEvent, $serverEvents),
            ConflictStrategy::ServerWins => $this->resolveServerWins($clientEvent, $serverEvents),
            ConflictStrategy::ClientWins => $this->resolveClientWins($clientEvent, $serverVersion),
            ConflictStrategy::OperationalTransform => $this->resolveOperationalTransform($clientEvent, $serverEvents),
        };
    }

    /**
     * Determine the strategy to use for a specific event.
     *
     * Can be overridden per event type or device.
     */
    private function determineStrategy(QueuedEvent $event): ConflictStrategy
    {
        // Future: Allow per-event-type strategy configuration
        return $this->defaultStrategy;
    }

    /**
     * Last writer wins based on timestamp.
     *
     * @param list<StoredEvent> $serverEvents
     */
    private function resolveLastWriterWins(
        QueuedEvent $clientEvent,
        array $serverEvents,
    ): ConflictResolution {
        $clientTime = $clientEvent->event->getOccurredAt();
        $serverTime = $this->getLastEventTime($serverEvents);

        if ($clientTime > $serverTime) {
            return ConflictResolution::accept(
                'Client event is newer (last writer wins)',
                $clientEvent->syncVersion,
            );
        }

        return ConflictResolution::reject(
            'Server event is newer (last writer wins)',
            $this->buildConflictData($clientEvent, $serverEvents),
        );
    }

    /**
     * First writer wins based on timestamp.
     *
     * @param list<StoredEvent> $serverEvents
     */
    private function resolveFirstWriterWins(
        QueuedEvent $clientEvent,
        array $serverEvents,
    ): ConflictResolution {
        $clientTime = $clientEvent->event->getOccurredAt();
        $serverTime = $this->getFirstEventTime($serverEvents);

        if ($clientTime < $serverTime) {
            return ConflictResolution::accept(
                'Client event is older (first writer wins)',
                $clientEvent->syncVersion,
            );
        }

        return ConflictResolution::reject(
            'Server event is older (first writer wins)',
            $this->buildConflictData($clientEvent, $serverEvents),
        );
    }

    /**
     * Device with higher priority wins.
     *
     * @param list<StoredEvent> $serverEvents
     */
    private function resolveDevicePriority(
        QueuedEvent $clientEvent,
        array $serverEvents,
    ): ConflictResolution {
        $clientDeviceId = $clientEvent->deviceId->toString();
        $clientPriority = $this->devicePriorities[$clientDeviceId] ?? 0;

        $serverDeviceId = $this->getLastDeviceId($serverEvents);
        $serverPriority = $serverDeviceId !== null
            ? ($this->devicePriorities[$serverDeviceId] ?? 0)
            : 0;

        if ($clientPriority > $serverPriority) {
            return ConflictResolution::accept(
                sprintf('Client device has higher priority (%d > %d)', $clientPriority, $serverPriority),
                $clientEvent->syncVersion,
            );
        }

        if ($clientPriority < $serverPriority) {
            return ConflictResolution::reject(
                sprintf('Server device has higher priority (%d > %d)', $serverPriority, $clientPriority),
                $this->buildConflictData($clientEvent, $serverEvents),
            );
        }

        // Equal priority - fall back to timestamp
        return $this->resolveLastWriterWins($clientEvent, $serverEvents);
    }

    /**
     * Server always wins.
     *
     * @param list<StoredEvent> $serverEvents
     */
    private function resolveServerWins(
        QueuedEvent $clientEvent,
        array $serverEvents,
    ): ConflictResolution {
        return ConflictResolution::reject(
            'Server wins by policy',
            $this->buildConflictData($clientEvent, $serverEvents),
        );
    }

    /**
     * Client always wins.
     */
    private function resolveClientWins(
        QueuedEvent $clientEvent,
        SyncVersion $serverVersion,
    ): ConflictResolution {
        $mergedVersion = $clientEvent->syncVersion->merge($serverVersion);

        return ConflictResolution::accept(
            'Client wins by policy',
            $mergedVersion,
        );
    }

    /**
     * Use operational transform to merge changes.
     *
     * This is a placeholder - real OT requires domain-specific logic.
     *
     * @param list<StoredEvent> $serverEvents
     */
    private function resolveOperationalTransform(
        QueuedEvent $clientEvent,
        array $serverEvents,
    ): ConflictResolution {
        // OT requires domain-specific transformation functions
        // This is a simplified implementation that attempts a merge

        $clientPayload = $clientEvent->event->toArray();
        $serverPayload = $this->getLastEventPayload($serverEvents);

        $mergedPayload = $this->attemptMerge($clientPayload, $serverPayload);

        if ($mergedPayload !== null) {
            // Create a merged event (simplified - real implementation would need event factory)
            return ConflictResolution::merge(
                'Merged via operational transform',
                $clientEvent->syncVersion,
                $mergedPayload,
            );
        }

        // Cannot merge - fall back to device priority
        return $this->resolveDevicePriority($clientEvent, $serverEvents);
    }

    /**
     * Attempt to merge two event payloads.
     *
     * @param array<string, mixed> $clientPayload
     * @param array<string, mixed> $serverPayload
     * @return array<string, mixed>|null
     */
    private function attemptMerge(array $clientPayload, array $serverPayload): ?array
    {
        // Simple field-level merge - real OT needs domain knowledge
        $merged = $serverPayload;

        foreach ($clientPayload as $key => $value) {
            // If client changed a field that server didn't change, accept it
            if (!isset($serverPayload[$key]) || $serverPayload[$key] === $serverPayload[$key]) {
                $merged[$key] = $value;
            }
        }

        // Check if merge is actually different from both
        if ($merged === $serverPayload || $merged === $clientPayload) {
            return null; // No meaningful merge possible
        }

        return $merged;
    }

    /**
     * Get the timestamp of the last event in server events.
     *
     * @param list<StoredEvent> $serverEvents
     */
    private function getLastEventTime(array $serverEvents): \DateTimeImmutable
    {
        if (empty($serverEvents)) {
            return new \DateTimeImmutable('@0');
        }

        $last = end($serverEvents);
        \assert($last instanceof StoredEvent);
        return $last->metadata->occurredAt;
    }

    /**
     * Get the timestamp of the first event in server events.
     *
     * @param list<StoredEvent> $serverEvents
     */
    private function getFirstEventTime(array $serverEvents): \DateTimeImmutable
    {
        if (empty($serverEvents)) {
            return new \DateTimeImmutable('@0');
        }

        $first = reset($serverEvents);
        \assert($first instanceof StoredEvent);
        return $first->metadata->occurredAt;
    }

    /**
     * Get the device ID of the last event in server events.
     * First checks sync metadata, then falls back to payload deviceId field.
     *
     * @param list<StoredEvent> $serverEvents
     */
    private function getLastDeviceId(array $serverEvents): ?string
    {
        if (empty($serverEvents)) {
            return null;
        }

        $last = end($serverEvents);
        \assert($last instanceof StoredEvent);
        
        // Try sync metadata first
        $syncMetadata = $last->metadata->syncMetadata ?? null;
        if ($syncMetadata !== null) {
            return $syncMetadata->deviceId->toString();
        }
        
        // Fall back to payload deviceId field (for events without full sync metadata)
        return $last->payload['deviceId'] ?? null;
    }

    /**
     * Get the payload of the last event in server events.
     */
    private function getLastEventPayload(array $serverEvents): array
    {
        if (empty($serverEvents)) {
            return [];
        }

        $last = end($serverEvents);
        \assert($last instanceof StoredEvent);
        return $last->payload;
    }

    /**
     * Build conflict data for manual resolution.
     */
    private function buildConflictData(QueuedEvent $clientEvent, array $serverEvents): array
    {
        $serverPayloads = array_map(
            fn(StoredEvent $e) => $e->payload,
            $serverEvents
        );

        return [
            'clientEvent' => $clientEvent->event->toArray(),
            'clientVersion' => $clientEvent->syncVersion->toArray(),
            'serverEvents' => $serverPayloads,
            'resolutionOptions' => [
                'accept_client',
                'accept_server',
                'manual_merge',
            ],
        ];
    }
}
