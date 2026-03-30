<?php

declare(strict_types=1);

namespace App\Events\Region;

class DiscountRuleRemoved extends RegionEvent
{
    public string $ruleId;
    public string $reason;

    public function __construct(
        string $regionId,
        string $ruleId,
        string $reason
    ) {
        parent::__construct($regionId);
        $this->ruleId = $ruleId;
        $this->reason = $reason;
    }

    public function toPayload(): array
    {
        return array_merge(parent::toPayload(), [
            'rule_id' => $this->ruleId,
            'reason' => $this->reason,
        ]);
    }
}
