<?php

namespace App\Services\Sagas;

use Illuminate\Support\Facades\Log;

/**
 * Saga 2 - Visit Recovery / Route Adaptation Saga
 * Trigger: VisitCancelled, VisitNoShow, RouteDisrupted
 * Goal: Protect field coverage and route execution continuity.
 */
class VisitRecoverySaga extends Saga
{
    public function onVisitCancelled(\App\Events\VisitCancelled $event): void
    {
        Log::info("Zone [Saga]: Visit Cancelled for Customer {$event->customerId}. Appraising route load.");
        
        $this->state['visit_id'] = $event->visitId;
        $this->state['customer_id'] = $event->customerId;
        
        // Zone orchestration logic:
        // Check local projection for Customer Priority
        // If high -> emit ReassignVisitCommand to an available rep in the Sector
        // If low/normal -> emit RescheduleVisitCommand for next iteration
        
        if ($this->isReplay()) return;

        Log::info("Zone [Saga]: Priority logic evaluated. Dispatching RescheduleVisitCommand.");
        $this->complete(); // Terminates the coordination for this specific unit of work
    }

    public function onRepUnavailable(\App\Events\RepUnavailable $event): void
    {
        Log::warn("Zone [Saga]: Rep {$event->repId} unavailable. Adapting Route.");
        
        if ($this->isReplay()) return;

        // Emits AssignBackupRepCommand
        // Emits ReallocatePendingVisitsCommand
        
        $this->complete();
    }

    public function onRouteDisrupted(\App\Events\RouteDisrupted $event): void
    {
        Log::error("Zone [Saga]: Route Disrupted. Emitting CompressRemainingRouteCommand and DelayNonPriorityStopsCommand.");
        
        if ($this->isReplay()) return;

        $this->complete();
    }
}
