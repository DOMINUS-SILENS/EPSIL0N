<?php

namespace App\Services;

use App\Models\DomainEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\QueryException;
use ReflectionMethod;

/**
 * Optimized Projector Base Class
 * Supports batch processing and caching for improved SFA sync performance
 */
abstract class Projector
{
    protected bool $isRebuilding = false;

    /** @var int Batch size for bulk operations */
    protected int $batchSize = 100;

    /** @var array Batch buffer for collecting operations */
    protected array $batchBuffer = [];

    /** @var bool Whether batch mode is active */
    protected bool $batchMode = false;

    /** @var string Request-level cache for checkpoint lookups */
    protected string $checkpointCacheKey = '';

    /**
     * Determines if the projector is currently executing a historical rebuild.
     * Used to suppress external side-effects (e.g., Algolia pushes, integrations).
     */
    protected function isRebuild(): bool
    {
        return $this->isRebuilding;
    }

    /**
     * Formal Axiom: Every projector must know how to structurally obliterate its own read models
     * to guarantee a clean slate before a Full Rebuild is executed.
     */
    abstract public function resetState(): void;

    /**
     * Derives a stable, versioned idempotency key natively from the projector class.
     */
    protected function getVersionedProjectorId(): string
    {
        return static::class . '_v1';
    }

    /**
     * Get checkpoint from cache or database
     */
    protected function getCachedCheckpoint(string $sourceType): ?object
    {
        $key = "checkpoint:{$this->getVersionedProjectorId()}:{$sourceType}";

        return Cache::store('array')->remember($key, 1, function () use ($sourceType) {
            return DB::table('projector_checkpoints')
                ->where('projector_name', $this->getVersionedProjectorId())
                ->where('source_type', $sourceType)
                ->first();
        });
    }

    /**
     * Invalidate checkpoint cache
     */
    protected function invalidateCheckpointCache(string $sourceType): void
    {
        $key = "checkpoint:{$this->getVersionedProjectorId()}:{$sourceType}";
        Cache::store('array')->forget($key);
    }

    /**
     * Start batch processing mode
     */
    public function beginBatch(): void
    {
        $this->batchMode = true;
        $this->batchBuffer = [];
    }

    /**
     * Commit batched operations
     */
    public function commitBatch(): void
    {
        if (!$this->batchMode || empty($this->batchBuffer)) {
            return;
        }

        $this->flushBatch();
        $this->batchMode = false;
    }

    /**
     * Flush batch buffer - override in child classes for batch operations
     */
    protected function flushBatch(): void
    {
        // Default: no-op - child projectors should override for batch inserts
    }

    /**
     * Rollback batch
     */
    public function rollbackBatch(): void
    {
        $this->batchBuffer = [];
        $this->batchMode = false;
    }

    /**
     * Resolves the legacy execution routing natively via Reflection, mapping the domain event gracefully
     * without shattering backward compatibility on older un-migrated projector signatures.
     */
    protected function applyEvent(array $payload, DomainEvent $event): void
    {
        $eventBase = class_basename($event->event_type);
        $studlyEvent = \Illuminate\Support\Str::studly(str_replace('.', '_', $eventBase));
        $method = 'handle' . $studlyEvent;

        if (method_exists($this, $method)) {
            // Formal Closure backwards-compatibility proxy
            $refMethod = new ReflectionMethod($this, $method);
            $params = $refMethod->getParameters();

            if (isset($params[1]) && $params[1]->getType() && $params[1]->getType()->getName() === \App\Models\DomainOutbox::class) {
                // Mock the Outbox struct for legacy consumers, securing the Event ID and payload safely.
                $legacyProxy = new \App\Models\DomainOutbox();
                $legacyProxy->id = $event->id;
                $legacyProxy->payload = is_string($event->payload) ? $event->payload : json_encode($event->payload);
                $this->{$method}($payload, $legacyProxy);
            } else {
                $this->{$method}($payload, $event);
            }
        }
    }

    /**
     * Standard entry point for the ProjectionDispatcher via the Outbox.
     * Consumes specifically from the outbox source pipe.
     * Optimized with checkpoint caching and batch support.
     */
    public function process(\App\Models\DomainOutbox $outbox): void
    {
        $this->isRebuilding = false;
        $payload = is_string($outbox->event->payload) ? json_decode($outbox->event->payload, true) : $outbox->event->payload;

        // Quick checkpoint check without lock first (idempotency)
        $checkpoint = $this->getCachedCheckpoint('outbox');
        if ($checkpoint && $checkpoint->last_outbox_id >= $outbox->id) {
            return;
        }

        DB::transaction(function () use ($outbox, $payload) {
            // Lock-based check for concurrency safety
            $lockedCheckpoint = DB::table('projector_checkpoints')
                ->where('projector_name', $this->getVersionedProjectorId())
                ->where('source_type', 'outbox')
                ->lockForUpdate()
                ->first();

            // Formal Closure Axiom: Checkpoint Determinism
            if ($lockedCheckpoint && $lockedCheckpoint->last_outbox_id >= $outbox->id) {
                return;
            }

            $this->applyEvent($payload, $outbox->event);

            // Atomic Replay Guard: Mark event as processed for this bucket
            if (config('epsilon.canonical_dual_write', false) || env('CANONICAL_DUAL_WRITE', false)) {
                DB::table('canonical_projection_events')->insertOrIgnore([
                    'event_id' => $outbox->event->id,
                    'projector' => static::class,
                    'aggregate_id' => $outbox->event->aggregate_id ?? null,
                    'processed_at' => now(),
                ]);
            }

            DB::table('projector_checkpoints')->updateOrInsert(
                ['projector_name' => $this->getVersionedProjectorId(), 'source_type' => 'outbox'],
                [
                    'last_outbox_id' => $outbox->id,
                    'last_global_sequence' => $outbox->event->id,
                    'last_processed_at' => now(),
                    'updated_at' => now()
                ]
            );

            // Invalidate cache after update
            $this->invalidateCheckpointCache('outbox');
        });
    }

    /**
     * Process an event via strict Event Store replay.
     * Keeps replay coordinate state entirely segregated from Live Outbox consumption.
     * Optimized for bulk replays with checkpoint caching.
     */
    public function handle(DomainEvent $event): void
    {
        $this->isRebuilding = true;
        $payload = is_string($event->payload) ? json_decode($event->payload, true) : $event->payload;

        // Quick checkpoint check without lock
        $checkpoint = $this->getCachedCheckpoint('event_store');
        if ($checkpoint && $checkpoint->last_global_sequence >= $event->id) {
            return;
        }

        DB::transaction(function () use ($event, $payload) {
            $lockedCheckpoint = DB::table('projector_checkpoints')
                ->where('projector_name', $this->getVersionedProjectorId())
                ->where('source_type', 'event_store')
                ->lockForUpdate()
                ->first();

            // Replay Checkpoint determinism
            if ($lockedCheckpoint && $lockedCheckpoint->last_global_sequence >= $event->id) {
                return;
            }

            $this->applyEvent($payload, $event);

            // Atomic Replay Guard: Mark event as processed for this bucket
            if (config('epsilon.canonical_dual_write', false) || env('CANONICAL_DUAL_WRITE', false)) {
                DB::table('canonical_projection_events')->insertOrIgnore([
                    'event_id' => $event->id,
                    'projector' => static::class,
                    'aggregate_id' => $event->aggregate_id ?? null,
                    'processed_at' => now(),
                ]);
            }

            DB::table('projector_checkpoints')->updateOrInsert(
                ['projector_name' => $this->getVersionedProjectorId(), 'source_type' => 'event_store'],
                [
                    'last_global_sequence' => $event->id,
                    'last_processed_at' => now(),
                    'updated_at' => now()
                ]
            );

            $this->invalidateCheckpointCache('event_store');
        });
    }

    /**
     * Process multiple events in a batch for replay/initial sync
     * Much more efficient than individual event processing
     *
     * @param array $events Array of DomainEvent objects
     * @return int Number of events processed
     */
    public function processBatch(array $events): int
    {
        if (empty($events)) {
            return 0;
        }

        $this->isRebuilding = true;
        $this->beginBatch();

        $processed = 0;
        $lastSequence = 0;

        try {
            DB::transaction(function () use ($events, &$processed, &$lastSequence) {
                // Get current checkpoint once at start
                $checkpoint = DB::table('projector_checkpoints')
                    ->where('projector_name', $this->getVersionedProjectorId())
                    ->where('source_type', 'event_store')
                    ->forceLockForUpdate() // Lock for entire batch
                    ->first();

                $lastProcessedId = $checkpoint->last_global_sequence ?? 0;

                foreach ($events as $event) {
                    // Skip already processed
                    if ($event->id <= $lastProcessedId) {
                        continue;
                    }

                    $payload = is_string($event->payload) ? json_decode($event->payload, true) : $event->payload;
                    $this->applyEvent($payload, $event);
                    $processed++;
                    $lastSequence = $event->id;
                }

                // Single checkpoint update for entire batch
                if ($lastSequence > $lastProcessedId) {
                    DB::table('projector_checkpoints')->updateOrInsert(
                        ['projector_name' => $this->getVersionedProjectorId(), 'source_type' => 'event_store'],
                        [
                            'last_global_sequence' => $lastSequence,
                            'last_processed_at' => now(),
                            'updated_at' => now()
                        ]
                    );
                }
            });

            $this->commitBatch();
        } catch (\Throwable $e) {
            $this->rollbackBatch();
            throw $e;
        }

        return $processed;
    }

    /**
     * Get processing statistics
     */
    public function getStats(): array
    {
        $outboxCheckpoint = $this->getCachedCheckpoint('outbox');
        $storeCheckpoint = $this->getCachedCheckpoint('event_store');

        return [
            'projector' => static::class,
            'version' => 'v1',
            'last_outbox_id' => $outboxCheckpoint->last_outbox_id ?? 0,
            'last_global_sequence' => $storeCheckpoint->last_global_sequence ?? 0,
            'last_processed_at' => $outboxCheckpoint->last_processed_at ?? null,
        ];
    }
}
