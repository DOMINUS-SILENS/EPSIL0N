<?php

declare(strict_types=1);

namespace App\Events\Region;

class PricingPolicyChanged extends RegionEvent
{
    public string $policyId;
    public string $type;
    public array $rules;
    public string $effectiveDate;
    public ?string $endDate;
    public ?string $notes;

    public function __construct(
        string $regionId,
        string $policyId,
        string $type,
        array $rules,
        string $effectiveDate,
        ?string $endDate = null,
        ?string $notes = null
    ) {
        parent::__construct($regionId);
        $this->policyId = $policyId;
        $this->type = $type;
        $this->rules = $rules;
        $this->effectiveDate = $effectiveDate;
        $this->endDate = $endDate;
        $this->notes = $notes;
    }

    public function toPayload(): array
    {
        return array_merge(parent::toPayload(), [
            'policy_id' => $this->policyId,
            'type' => $this->type,
            'rules' => $this->rules,
            'effective_date' => $this->effectiveDate,
            'end_date' => $this->endDate,
            'notes' => $this->notes,
        ]);
    }
}
