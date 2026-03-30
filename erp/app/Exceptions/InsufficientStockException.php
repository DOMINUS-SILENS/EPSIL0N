<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public function __construct(int $productId, float $requested, float $available)
    {
        parent::__construct(
            "Insufficient stock for product {$productId}. ".
            "Available: {$available}, Requested: {$requested}"
        );
    }
}
