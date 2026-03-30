<?php

namespace App\Events;

class PaymentRecorded
{
    public string $uuid;
    public int $paymentId;
    public int $entrepriseId;
    public int $contactId;
    public float $amount;
    public string $paymentMode; // cash, cheque, bank_transfer
    public array $metadata;

    public function __construct(string $uuid, int $paymentId, int $entrepriseId, int $contactId, float $amount, string $paymentMode, array $metadata = [])
    {
        $this->uuid = $uuid;
        $this->paymentId = $paymentId;
        $this->entrepriseId = $entrepriseId;
        $this->contactId = $contactId;
        $this->amount = $amount;
        $this->paymentMode = $paymentMode;
        $this->metadata = $metadata;
    }
}
