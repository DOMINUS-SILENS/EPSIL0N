<?php

declare(strict_types=1);

namespace App\Events\Zone;

class ZoneTerrainConstraintAdded extends ZoneEvent
{
    public string $constraintId;
    public string $type;
    public array $parameters;
    public ?array $activeHours;

    public function __construct(
        string $zoneId,
        string $constraintId,
        string $type,
        array $parameters,
        ?array $activeHours = null
    ) {
        parent::__construct($zoneId);
        $this->constraintId = $constraintId;
        $this->type = $type;
        $this->parameters = $parameters;
        $this->activeHours = $activeHours;
    }

    public function toPayload(): array
    {
        return array_merge(parent::toPayload(), [
            'constraint_id' => $this->constraintId,
            'type' => $this->type,
            'parameters' => $this->parameters,
            'active_hours' => $this->activeHours,
        ]);
    }
}
