<?php

declare(strict_types=1);

namespace App\Events\Region;

/**
 * Base class for Region domain events
 */
abstract class RegionEvent
{
    public string $regionId;
    public string $occurredAt;

    public function __construct(string $regionId)
    {
        $this->regionId = $regionId;
        $this->occurredAt = now()->toDateTimeString();
    }

    public function toPayload(): array
    {
        return [
            'region_id' => $this->regionId,
            'occurred_at' => $this->occurredAt,
        ];
    }
}
