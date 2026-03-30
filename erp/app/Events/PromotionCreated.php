<?php

namespace App\Events;

class PromotionCreated
{
    public string $uuid;
    public int $promotionId;
    public int $entrepriseId;
    public array $data; // Rules, dates, zones
    public array $conditions; // promotion_article_commmande
    public array $rewards; // promotion_article_gratuite
    public array $tiers; // promotion_palier

    public function __construct(string $uuid, int $promotionId, int $entrepriseId, array $data, array $conditions = [], array $rewards = [], array $tiers = [])
    {
        $this->uuid = $uuid;
        $this->promotionId = $promotionId;
        $this->entrepriseId = $entrepriseId;
        $this->data = $data;
        $this->conditions = $conditions;
        $this->rewards = $rewards;
        $this->tiers = $tiers;
    }
}
