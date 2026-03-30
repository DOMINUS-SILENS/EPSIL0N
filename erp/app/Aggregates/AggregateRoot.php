<?php

namespace App\Aggregates;

use App\Services\OutboxService;
use App\Models\DomainEvent;

abstract class AggregateRoot
{
    protected string $uuid;
    protected array $recordedEvents = [];
    protected ?int $tenantId = null;
    protected int $version = 0;
    protected int $snapshotThreshold = 100;

    protected function __construct(string $uuid)
    {
        $this->uuid = $uuid;
    }

    /**
     * CQRS Mathematical Closure: Reconstitute state purely from historical events.
     * Supports Snapshotting for high-performance reconstruction.
     */
    public static function retrieve(string $uuid): self
    {
        $aggregate = new static($uuid);
        $eventStore = app(\App\Services\EventStoreService::class);
        $snapshotService = app(\App\Services\SnapshotService::class);
        
        // 1. Try to load latest snapshot securely
        $snapshot = $snapshotService->getLatestSnapshot(
            class_basename(static::class),
            $uuid,
            (string) ($aggregate->tenantId ?? 1)
        );

        if ($snapshot) {
            $aggregate->fromSnapshot(json_decode($snapshot->state_json, true));
            $aggregate->version = (int) $snapshot->last_aggregate_sequence;
        }

        // 2. Load events after snapshot
        $historicalEvents = $eventStore->getEventsForAggregate(
            class_basename(static::class),
            $uuid,
            $aggregate->version
        );
            
        foreach ($historicalEvents as $record) {
            if ($aggregate->tenantId === null && isset($record->tenant_id)) {
                $aggregate->tenantId = $record->tenant_id;
            }

            $eventClass = "App\\Events\\" . $record->event_type;
            if (class_exists($eventClass)) {
                $payload = is_string($record->payload) ? json_decode($record->payload, true) : (array)$record->payload;
                try {
                    // Reconstruct using PHP 8 named arguments
                    $event = new $eventClass(...$payload);
                } catch (\Throwable $e) {
                    try {
                        $event = new $eventClass($payload);
                    } catch (\Throwable $e2) {
                        $event = new $eventClass($record->aggregate_id, $payload);
                    }
                }
                $aggregate->apply($event, false); // false = don't increment version during replay
            }
            $aggregate->version = $record->local_sequence;
        }
        
        return $aggregate;
    }

    public function uuid(): string
    {
        return $this->uuid;
    }

    protected function recordThat(object $event): self
    {
        $this->recordedEvents[] = $event;
        $this->apply($event);
        return $this;
    }

    protected function apply(object $event, bool $isNew = true): void
    {
        $method = 'apply' . class_basename($event);
        if (method_exists($this, $method)) {
            $this->$method($event);
        }

        if ($isNew) {
            $this->version++;
        }
    }

    /**
     * Persist accumulated domain events and trigger snapshotting if threshold reached.
     */
    public function persist(): self
    {
        $outboxService = app(OutboxService::class);
        $snapshotService = app(\App\Services\SnapshotService::class);
        
        foreach ($this->recordedEvents as $event) {
            $payload = property_exists($event, 'payload') ? $event->payload : (array) $event;
            
            if ($this->tenantId === null) {
                $this->tenantId = $payload['entrepriseId'] ?? $payload['entreprise_id'] ?? 1;
            }

            $outboxService->publishDomain(
                class_basename($this),
                $this->uuid,
                class_basename($event),
                $payload,
                $this->tenantId
            );
        }
        
        // Trigger Snapshotting
        if ($this->version > 0 && ($this->version % $this->snapshotThreshold) === 0) {
            $snapshotService->saveSnapshot(
                class_basename($this),
                $this->uuid,
                $this->toSnapshot(),
                $this->version, // event_id pseudo mapping
                (string) $this->tenantId,
                1,              // version
                $this->version  // sequence mapping
            );
        }

        $this->recordedEvents = [];
        return $this;
    }

    /**
     * Default Implementation for Snapshotting. 
     * Overriden by child aggregates.
     */
    protected function toSnapshot(): array
    {
        return (array) $this;
    }

    protected function fromSnapshot(array $data): void
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
}
