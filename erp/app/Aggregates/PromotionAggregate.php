<?php

namespace App\Aggregates;

use App\Events\PromotionCreated;
use App\Events\PromotionAppliedToOrder;
use Exception;

class PromotionAggregate extends AggregateRoot
{
    public function create(int $promotionId, int $entrepriseId, array $data, array $conditions = [], array $rewards = [], array $tiers = []): static
    {
        if (empty($data['date_debut']) || empty($data['date_fin'])) {
            throw new Exception("God-Level Logic Rule: Promotions must be chronologically bounded contextually.");
        }

        $this->recordThat(new PromotionCreated($this->uuid(), $promotionId, $entrepriseId, $data, $conditions, $rewards, $tiers));
        return $this;
    }

    public function recordApplication(int $promotionId, int $entrepriseId, int $movementId, array $appliedBenefits): static
    {
        // Metric aggregation triggered dynamically by Engine
        $this->recordThat(new PromotionAppliedToOrder($this->uuid(), $promotionId, $entrepriseId, $movementId, $appliedBenefits));
        return $this;
    }
}
