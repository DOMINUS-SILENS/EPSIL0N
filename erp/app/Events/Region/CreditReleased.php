<?php

declare(strict_types=1);

namespace App\Events\Region;

class CreditReleased extends RegionEvent
{
    public string $tournéeId;
    public array $releasedCredit;

    public function __construct(
        string $regionId,
        string $tournéeId,
        array $releasedCredit
    ) {
        parent::__construct($regionId);
        $this->tournéeId = $tournéeId;
        $this->releasedCredit = $releasedCredit;
    }

    public function toPayload(): array
    {
        return array_merge(parent::toPayload(), [
            'tournée_id' => $this->tournéeId,
            'released_credit' => $this->releasedCredit,
        ]);
    }
}
