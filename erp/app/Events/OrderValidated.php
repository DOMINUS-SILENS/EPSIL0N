<?php

namespace App\Events;

class OrderValidated
{
    public function __construct(
        public string $uuid,
        public int|string $orderId,
        public array $data
    ) {}
}
