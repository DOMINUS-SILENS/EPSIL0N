<?php

namespace App\Aggregates;

use App\Events\PaymentRecorded;
use Exception;

class PaymentAggregate extends AggregateRoot
{
    public function recordPayment(int $paymentId, int $entrepriseId, int $contactId, float $amount, string $mode, array $meta = []): static
    {
        if ($amount <= 0) {
            throw new Exception("God-Level Logic exception: Payment quantities must be strictly positive integers/floats.");
        }

        $this->recordThat(new PaymentRecorded($this->uuid(), $paymentId, $entrepriseId, $contactId, $amount, $mode, $meta));
        return $this;
    }
}
