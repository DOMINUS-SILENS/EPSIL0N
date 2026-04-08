<?php

declare(strict_types=1);

namespace Spiral\Kernel\Domain\Shared\Aggregate;

use Spiral\Kernel\Domain\Identity\TenantId;
use Spiral\Kernel\Domain\Shared\Event\DomainEvent;

/**
 * Base class for event-sourced aggregates.
 *
 * Provides:
 * - Aggregate identity ownership (id + tenantId)
 * - Event recording (raise method)
 * - Replay/reconstitution (When method)
 * - Version tracking (optimistic concurrency)
 * - Pending event extraction (popUncommittedEvents)
 * - Internal mutation flow (apply method)
 */
abstract class AggregateRoot
{
    private string $id;

    private readonly TenantId $tenantId;

    private int $version = 0;

    private int $streamVersion = 0;

    /**
     * @var list<DomainEvent>
     */
    private array $uncommittedEvents = [];

    final public function __construct(string $id, TenantId $tenantId)
    {
        $this->id = $id;
        $this->tenantId = $tenantId;
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get the tenant ID for this aggregate.
     */
    public function getTenantId(): TenantId
    {
        return $this->tenantId;
    }

    /**
     * Get the current version (number of applied events including uncommitted).
     */
    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * Get the stream version (number of committed events in event store).
     */
    public function getStreamVersion(): int
    {
        return $this->streamVersion;
    }

    /**
     * Record a domain event and apply it to the aggregate state.
     *
     * This is the ONLY way to mutate aggregate state. All state changes
     * must be expressed as domain events.
     */
    protected function raise(DomainEvent $event): void
    {
        $this->uncommittedEvents[] = $event;
        $this->apply($event);
        $this->version++;
    }

    /**
     * Apply an event to the aggregate state.
     *
     * This method reconstructs the aggregate's state by replaying events in
     * sequence. Called during two scenarios:
     *
     * 1. When events are raised (raise method): Apply immediately after
     *    recording so aggregate state reflects the mutation
     *
     * 2. When replaying from event store (reconstituteFromEvents): Apply in
     *    sequence to rebuild aggregate from persisted history
     *
     * Event application MUST be:
     * - Deterministic: Same event always produces same state change
     * - Idempotent: Re-applying same event produces equivalent result
     * - Isolated: Changes only aggregate state, no side effects
     *
     * Subclasses may throw exceptions for unknown event types (fail-fast pattern).
     * This is the contract for event handling: either handle all known event types
     * or throw explicitly.
     *
     * @throws \RuntimeException If the event type is unknown or cannot be handled
     *
     * Subclasses must implement this to handle their specific event types.
     */
    abstract protected function apply(DomainEvent $event): void;

    /**
     * Get all uncommitted events without clearing the buffer.
     *
     * @return list<DomainEvent>
     */
    public function getUncommittedEvents(): array
    {
        return $this->uncommittedEvents;
    }

    /**
     * Extract all uncommitted events and clear the buffer.
     *
     * Used by the event store to persist events.
     *
     * @return list<DomainEvent>
     */
    public function popUncommittedEvents(): array
    {
        $events = $this->uncommittedEvents;
        $this->uncommittedEvents = [];
        return $events;
    }

    /**
     * Check if there are uncommitted events.
     */
    public function hasUncommittedEvents(): bool
    {
        return \count($this->uncommittedEvents) > 0;
    }

    /**
     * Get count of uncommitted events.
     */
    public function getUncommittedEventCount(): int
    {
        return \count($this->uncommittedEvents);
    }

    /**
     * Reconstitute aggregate from event history.
     *
     * Called by repositories to rebuild aggregate state from stored events.
     *
     * @param list<DomainEvent> $events
     * @param int $streamVersion The version of the stream after these events are applied
     */
    public function reconstituteFromEvents(array $events, int $streamVersion = 0): void
    {
        $this->streamVersion = $streamVersion;

        foreach ($events as $event) {
            $this->apply($event);
        }

        // Version reflects the stream version (number of events applied up to this point)
        $this->version = $streamVersion;
    }

    /**
     * Mark the aggregate as newly created (not yet persisted).
     *
     * Convention:
     * - streamVersion = 0 means "new aggregate, no events persisted yet"
     * - streamVersion > 0 means "aggregate has N events persisted"
     *
     * This is called after construction before any events are raised.
     * After persistence, markCommitted() updates streamVersion to the
     * actual version number.
     */
    public function markAsNew(): void
    {
        $this->streamVersion = 0;
    }

    /**
     * Update stream version after persistence.
     *
     * Called after successful event store append.
     */
    public function markCommitted(int $streamVersion): void
    {
        $this->streamVersion = $streamVersion;
    }
}
