<?php

namespace App\Events;

class OrderUpdated
{
    public function __construct(
        public string $uuid,
        public int|string $orderId,
        public array $data
    ) {}
}
