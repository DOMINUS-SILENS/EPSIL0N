<?php

declare(strict_types=1);

namespace App\Events\Zone;

class FallbackPricingApplied extends ZoneEvent
{
    public string $routeId;
    public string $customerId;
    public string $originalPricingProfileId;
    public string $fallbackPricingProfileId;
    public string $reason;
    public string $appliedAt;

    public function __construct(
        string $zoneId,
        string $routeId,
        string $customerId,
        string $originalPricingProfileId,
        string $fallbackPricingProfileId,
        string $reason,
        string $appliedAt
    ) {
        parent::__construct($zoneId);
        $this->routeId = $routeId;
        $this->customerId = $customerId;
        $this->originalPricingProfileId = $originalPricingProfileId;
        $this->fallbackPricingProfileId = $fallbackPricingProfileId;
        $this->reason = $reason;
        $this->appliedAt = $appliedAt;
    }

    public function toPayload(): array
    {
        return array_merge(parent::toPayload(), [
            'route_id' => $this->routeId,
            'customer_id' => $this->customerId,
            'original_pricing_profile_id' => $this->originalPricingProfileId,
            'fallback_pricing_profile_id' => $this->fallbackPricingProfileId,
            'reason' => $this->reason,
            'applied_at' => $this->appliedAt,
        ]);
    }
}
