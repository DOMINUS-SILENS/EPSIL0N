<?php

namespace App\Aggregates;

use App\Events\PurchaseOrderCreated;

class PurchasingAggregate extends AggregateRoot
{
    public function createPurchaseOrder(int $purchaseOrderId, int $entrepriseId, int $supplierId, array $items): static
    {
        $totalAmount = array_reduce($items, fn($carry, $item) => $carry + ($item['quantity'] * $item['unit_price']), 0);

        $this->recordThat(new PurchaseOrderCreated(
            $this->uuid(),
            $purchaseOrderId,
            $entrepriseId,
            $supplierId,
            $items,
            $totalAmount
        ));

        return $this;
    }

    protected function applyPurchaseOrderCreated(PurchaseOrderCreated $event): void
    {
        $this->tenantId = $event->entrepriseId;
    }
}
