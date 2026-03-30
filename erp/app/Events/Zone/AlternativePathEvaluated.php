<?php

declare(strict_types=1);

namespace App\Events\Zone;

class AlternativePathEvaluated extends ZoneEvent
{
    public string $sagaId;
    public string $routeId;
    public array $alternative;
    public string $evaluatedAt;

    public function __construct(
        string $zoneId,
        string $sagaId,
        string $routeId,
        array $alternative,
        string $evaluatedAt
    ) {
        parent::__construct($zoneId);
        $this->sagaId = $sagaId;
        $this->routeId = $routeId;
        $this->alternative = $alternative;
        $this->evaluatedAt = $evaluatedAt;
    }

    public function toPayload(): array
    {
        return array_merge(parent::toPayload(), [
            'saga_id' => $this->sagaId,
            'route_id' => $this->routeId,
            'alternative' => $this->alternative,
            'evaluated_at' => $this->evaluatedAt,
        ]);
    }
}
