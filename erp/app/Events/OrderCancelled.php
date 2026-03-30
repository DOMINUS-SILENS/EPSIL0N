<?php

namespace App\Events;

class OrderCancelled
{
    public function __construct(
        public string $uuid,
        public int|string $orderId,
        public array $data
    ) {}
}
