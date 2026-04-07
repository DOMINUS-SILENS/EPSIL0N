<?php

declare(strict_types=1);

namespace Spiral\Kernel\Domain\Shared\Event;

use Spiral\Kernel\Domain\Identity\EventId;
use Spiral\Kernel\Domain\Identity\TenantId;
use Spiral\Kernel\Domain\Sync\SyncMetadata;

/**
 * Base interface for all domain events.
 *
 * Domain events are:
 * - Immutable facts about something that happened in the domain
 * - Named in past tense (e.g., OrderPlaced, InvoiceSent)
 * - Versioned with schemaVersion for replay determinism
 * - Self-contained with all data needed for reconstruction
 *
 * Every event must carry:
 * - EventId: Unique identifier (UUID v7 for time-ordering)
 * - TenantId: Multi-tenant isolation
 * - CorrelationId: Request correlation for tracing
 * - CausationId: What caused this event
 * - Timestamp: When it occurred
 * - SchemaVersion: Event schema version for upgraders
 *
 * For offline mobile sync, events may optionally carry:
 * - SyncMetadata: Device attribution, vector clock, sync status
 */
interface DomainEvent
{
    /**
     * Unique identifier for this event (UUID v7).
     */
    public function getEventId(): EventId;

    /**
     * Tenant ID for multi-tenant isolation.
     */
    public function getTenantId(): TenantId;

    /**
     * Correlation ID for request tracing.
     */
    public function getCorrelationId(): \Spiral\Kernel\Domain\Identity\CorrelationId;

    /**
     * Causation ID - ID of the event/command that caused this event.
     */
    public function getCausationId(): \Spiral\Kernel\Domain\Identity\CausationId;

    /**
     * When the event occurred.
     */
    public function getOccurredAt(): \DateTimeImmutable;

    /**
     * Schema version for event upgraders.
     *
     * Format: "1.0", "2.1", etc.
     * Used to determine if event needs upgrading before replay.
     */
    public function getSchemaVersion(): string;

    /**
     * Event type name (class name or custom string).
     */
    public function getEventType(): string;

    /**
     * Serialize event to array for storage.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;

    /**
     * Get the fully qualified event class name.
     */
    public function getClassName(): string;

    /**
     * Optional sync metadata for offline mobile events.
     *
     * Returns null for events created online or events that don't
     * participate in offline sync.
     *
     * When present, enables:
     * - Device attribution for conflict resolution
     * - Vector clock ordering for causal consistency
     * - Sync status tracking for offline queue management
     */
    public function getSyncMetadata(): ?SyncMetadata;
}
