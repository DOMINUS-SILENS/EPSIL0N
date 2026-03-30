<?php

declare(strict_types=1);

namespace App\Events\Region;

class TournéeFrequencyRuleChanged extends RegionEvent
{
    public string $zoneId;
    public int $minDaysBetween;
    public int $maxPerWeek;
    public array $preferredDays;
    public ?string $notes;

    public function __construct(
        string $regionId,
        string $zoneId,
        int $minDaysBetween,
        int $maxPerWeek,
        array $preferredDays,
        ?string $notes = null
    ) {
        parent::__construct($regionId);
        $this->zoneId = $zoneId;
        $this->minDaysBetween = $minDaysBetween;
        $this->maxPerWeek = $maxPerWeek;
        $this->preferredDays = $preferredDays;
        $this->notes = $notes;
    }

    public function toPayload(): array
    {
        return array_merge(parent::toPayload(), [
            'zone_id' => $this->zoneId,
            'min_days_between' => $this->minDaysBetween,
            'max_per_week' => $this->maxPerWeek,
            'preferred_days' => $this->preferredDays,
            'notes' => $this->notes,
        ]);
    }
}
