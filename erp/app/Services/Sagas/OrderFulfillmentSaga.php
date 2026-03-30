<?php

namespace App\Services\Sagas;

use Illuminate\Support\Facades\Log;

/**
 * Saga 1 - Order Fulfillment Orchestrator
 * Trigger: OrderSubmitted
 * Goal: Transform a field order into a fulfilled operational flow.
 */
class OrderFulfillmentSaga extends Saga
{
    public function onOrderSubmitted(\App\Events\OrderSubmitted $event): void
    {
        $this->state['order_id'] = $event->orderId;
        $this->state['lines'] = $event->payload['lines'] ?? [];
        $this->state['step'] = 'reserving_stock';

        if ($this->isReplay()) return; // Replay Safety Constraint

        // Zone Command: Dispatch ReserveStockCommand
        // In reality, this would use a CommandBus
        Log::info("Zone [Saga]: Dispatching ReserveStockCommand for Order {$event->orderId}");
    }

    public function onStockReserved(\App\Events\StockReserved $event): void
    {
        if (($this->state['step'] ?? '') !== 'reserving_stock') return;

        $this->state['step'] = 'assigning_delivery';
        
        if ($this->isReplay()) return;

        // Zone Command: Dispatch AssignDeliveryCommand
        Log::info("Zone [Saga]: Stock secured. Dispatching AssignDeliveryCommand for Order {$this->state['order_id']}");
    }

    public function onStockReservationFailed(\App\Events\StockReservationFailed $event): void
    {
        $this->state['step'] = 'fallback_allocation';
        
        if ($this->isReplay()) return;

        // Zone orchestrator attempts exception handling
        Log::warn("Zone [Saga]: Stock missing. Dispatching CreateFallbackAllocationCommand for Order {$this->state['order_id']}");
        
        // If fallback allocation eventually fails, a subsequent event would trigger Backorder marking.
    }

    public function onDeliveryCompleted(\App\Events\DeliveryCompleted $event): void
    {
        Log::info("Zone [Saga]: Delivery Completed for Order {$this->state['order_id']}. Saga Finished.");
        
        // Dispatch MarkOrderFulfilledCommand (to Secteur)
        $this->complete();
    }

    public function onDeliveryFailed(\App\Events\DeliveryFailed $event): void
    {
        Log::error("Zone [Saga]: Delivery Failed. Dispatching ReleaseStockReservationCommand.");
        
        $this->state['step'] = 'delivery_failed_reassignment';
        
        // Emits ReleaseStockReservationCommand and ReassignDeliveryOrBackorderCommand
    }
}
