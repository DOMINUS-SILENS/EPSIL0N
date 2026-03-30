<?php

declare(strict_types=1);

namespace App\Events\Region;

class DiscountRuleAdded extends RegionEvent
{
    public string $ruleId;
    public array $conditions;
    public float $discountPercentage;
    public int $priority;
    public ?string $effectiveFrom;
    public ?string $effectiveUntil;

    public function __construct(
        string $regionId,
        string $ruleId,
        array $conditions,
        float $discountPercentage,
        int $priority = 0,
        ?string $effectiveFrom = null,
        ?string $effectiveUntil = null
    ) {
        parent::__construct($regionId);
        $this->ruleId = $ruleId;
        $this->conditions = $conditions;
        $this->discountPercentage = $discountPercentage;
        $this->priority = $priority;
        $this->effectiveFrom = $effectiveFrom;
        $this->effectiveUntil = $effectiveUntil;
    }

    public function toPayload(): array
    {
        return array_merge(parent::toPayload(), [
            'rule_id' => $this->ruleId,
            'conditions' => $this->conditions,
            'discount_percentage' => $this->discountPercentage,
            'priority' => $this->priority,
            'effective_from' => $this->effectiveFrom,
            'effective_until' => $this->effectiveUntil,
        ]);
    }
}
