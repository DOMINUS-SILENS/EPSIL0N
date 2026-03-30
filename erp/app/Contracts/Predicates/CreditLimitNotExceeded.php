<?php

namespace App\Contracts\Predicates;

use App\Contracts\Predicate;
use App\Services\CreditService;

class CreditLimitNotExceeded implements Predicate
{
    public function __construct(protected CreditService $credit) {}

    public function evaluate(array $context): bool
    {
        $customerId = $context['customer_id'] ?? null;
        $amount = $context['amount'] ?? null;
        if (! $customerId || ! $amount) {
            return false;
        }

        $available = $this->credit->getAvailableCredit($customerId);

        return $available >= $amount;
    }
}
