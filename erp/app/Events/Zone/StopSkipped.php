<?php

declare(strict_types=1);

namespace App\Events\Zone;

class StopSkipped extends ZoneEvent
{
    public string $routeId;
    public string $customerId;
    public string $reason;
    public string $attemptedAt;
    public ?string $rescheduleDate;

    public function __construct(
        string $zoneId,
        string $routeId,
        string $customerId,
        string $reason,
        string $attemptedAt,
        ?string $rescheduleDate = null
    ) {
        parent::__construct($zoneId);
        $this->routeId = $routeId;
        $this->customerId = $customerId;
        $this->reason = $reason;
        $this->attemptedAt = $attemptedAt;
        $this->rescheduleDate = $rescheduleDate;
    }

    public function toPayload(): array
    {
        return array_merge(parent::toPayload(), [
            'route_id' => $this->routeId,
            'customer_id' => $this->customerId,
            'reason' => $this->reason,
            'attempted_at' => $this->attemptedAt,
            'reschedule_date' => $this->rescheduleDate,
        ]);
    }
}
