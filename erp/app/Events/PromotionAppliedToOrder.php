<?php

namespace App\Events;

class PromotionAppliedToOrder
{
    public string $uuid;
    public int $promotionId;
    public int $entrepriseId;
    public int $movementId;
    public array $appliedBenefits; // Financial values discounted, or free items inserted.

    public function __construct(string $uuid, int $promotionId, int $entrepriseId, int $movementId, array $appliedBenefits)
    {
        $this->uuid = $uuid;
        $this->promotionId = $promotionId;
        $this->entrepriseId = $entrepriseId;
        $this->movementId = $movementId;
        $this->appliedBenefits = $appliedBenefits;
    }
}
