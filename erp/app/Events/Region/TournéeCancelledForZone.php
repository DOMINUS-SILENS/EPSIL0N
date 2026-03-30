<?php

declare(strict_types=1);

namespace App\Events\Region;

class TournéeCancelledForZone extends RegionEvent
{
    public string $tournéeId;
    public string $reason;
    public bool $releaseLocks;

    public function __construct(
        string $regionId,
        string $tournéeId,
        string $reason,
        bool $releaseLocks = true
    ) {
        parent::__construct($regionId);
        $this->tournéeId = $tournéeId;
        $this->reason = $reason;
        $this->releaseLocks = $releaseLocks;
    }

    public function toPayload(): array
    {
        return array_merge(parent::toPayload(), [
            'tournée_id' => $this->tournéeId,
            'reason' => $this->reason,
            'release_locks' => $this->releaseLocks,
        ]);
    }
}
