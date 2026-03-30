<?php

namespace App\Events;

class PurchaseOrderCreated
{
    public function __construct(
        public string $uuid,
        public int $purchaseOrderId,
        public int $entrepriseId,
        public int $supplierId,
        public array $items,
        public float $totalAmount
    ) {}
}
