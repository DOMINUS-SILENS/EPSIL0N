<?php

declare(strict_types=1);

namespace App\Events\Zone;

/**
 * Base class for Zone domain events
 */
abstract class ZoneEvent
{
    public string $zoneId;
    public string $occurredAt;

    public function __construct(string $zoneId)
    {
        $this->zoneId = $zoneId;
        $this->occurredAt = now()->toDateTimeString();
    }

    public function toPayload(): array
    {
        return [
            'zone_id' => $this->zoneId,
            'occurred_at' => $this->occurredAt,
        ];
    }
}
