<?php

namespace App\Services;

use App\Models\DomainOutbox;
use App\Services\Projectors\SalesDashboardProjector;
use App\Services\Projectors\MovementProjector;
use App\Services\Projectors\StockBalanceProjector;
use App\Services\Projectors\MissionProjector;
use App\Services\Projectors\CustomerBalanceProjector;
use Illuminate\Support\Facades\Log;

/**
 * Central dispatcher for routing domain events to their respective projectors.
 * Implements the fan-out pattern: 1 event -> N projectors.
 */
class ProjectionDispatcher
{
    protected array $projectors = [];

    public function __construct(
        protected SalesDashboardProjector $salesDashboardProjector,
        protected MovementProjector $movementProjector,
        protected StockBalanceProjector $stockBalanceProjector,
        protected MissionProjector $missionProjector,
        protected CustomerBalanceProjector $customerBalanceProjector,
    ) {
        $this->registerProjectors();
    }

    /**
     * Map each event type to the projectors that handle it.
     */
    protected function registerProjectors(): void
    {
        // Dashboard Analytics
        $this->projectors['MovementValidated'][] = $this->salesDashboardProjector;
        $this->projectors['StopVisited'][] = $this->salesDashboardProjector;

        // Movement State Management
        $this->projectors['MovementCreated'][] = $this->movementProjector;
        $this->projectors['MovementValidated'][] = $this->movementProjector;
        $this->projectors['MovementDelivered'][] = $this->movementProjector;
        $this->projectors['MovementCancelled'][] = $this->movementProjector;

        // Stock Balance Management
        $this->projectors['MovementValidated'][] = $this->stockBalanceProjector;
        $this->projectors['MovementDelivered'][] = $this->stockBalanceProjector;
        $this->projectors['MovementCancelled'][] = $this->stockBalanceProjector;
        $this->projectors['StockReceived'][] = $this->stockBalanceProjector;
        $this->projectors['StockConsumed'][] = $this->stockBalanceProjector;
        $this->projectors['StockTransferred'][] = $this->stockBalanceProjector;
        $this->projectors['StockAdjusted'][] = $this->stockBalanceProjector;

        // Mission Management
        $this->projectors['MissionCreated'][] = $this->missionProjector;
        $this->projectors['MissionLoaded'][] = $this->missionProjector;
        $this->projectors['StopVisited'][] = $this->missionProjector;
        $this->projectors['MissionCompleted'][] = $this->missionProjector;

        // Customer Balance (Journal entries)
        $this->projectors['JournalEntryPosted'][] = $this->customerBalanceProjector;
    }

    /**
     * Dispatch a domain event to all registered projectors.
     */
    public function dispatch(DomainOutbox $event): void
    {
        $eventType = $this->normalizeEventType($event->event_type);
        $projectors = $this->projectors[$eventType] ?? [];

        if (empty($projectors)) {
            Log::debug("No projectors registered for event type: {$eventType}");
            return;
        }

        foreach ($projectors as $projector) {
            try {
                $projector->process($event);
            } catch (\Exception $e) {
                Log::error("Projector failed to process event", [
                    'projector' => get_class($projector),
                    'event_id' => $event->id,
                    'event_type' => $eventType,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }
    }

    /**
     * Dispatch multiple events in batch.
     */
    public function dispatchBatch(array $events): void
    {
        foreach ($events as $event) {
            $this->dispatch($event);
        }
    }

    /**
     * Normalize event type to match handler method naming.
     * e.g., "movement.validated" -> "MovementValidated"
     */
    protected function normalizeEventType(string $eventType): string
    {
        return \Illuminate\Support\Str::studly(str_replace('.', '_', $eventType));
    }
}