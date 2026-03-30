<?php

declare(strict_types=1);

namespace App\Events\Zone;

class CriticalCustomerAtRisk extends ZoneEvent
{
    public string $customerId;
    public string $reason;
    public string $detectedAt;
    public int $priority;

    public function __construct(
        string $zoneId,
        string $customerId,
        string $reason,
        string $detectedAt,
        int $priority
    ) {
        parent::__construct($zoneId);
        $this->customerId = $customerId;
        $this->reason = $reason;
        $this->detectedAt = $detectedAt;
        $this->priority = $priority;
    }

    public function toPayload(): array
    {
        return array_merge(parent::toPayload(), [
            'customer_id' => $this->customerId,
            'reason' => $this->reason,
            'detected_at' => $this->detectedAt,
            'priority' => $this->priority,
        ]);
    }
}
