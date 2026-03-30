<?php

declare(strict_types=1);

namespace App\Events\Zone;

class ZoneTerrainConstraintRemoved extends ZoneEvent
{
    public string $constraintId;
    public string $reason;

    public function __construct(
        string $zoneId,
        string $constraintId,
        string $reason
    ) {
        parent::__construct($zoneId);
        $this->constraintId = $constraintId;
        $this->reason = $reason;
    }

    public function toPayload(): array
    {
        return array_merge(parent::toPayload(), [
            'constraint_id' => $this->constraintId,
            'reason' => $this->reason,
        ]);
    }
}
