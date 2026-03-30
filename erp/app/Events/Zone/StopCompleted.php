<?php

declare(strict_types=1);

namespace App\Events\Zone;

class StopCompleted extends ZoneEvent
{
    public string $routeId;
    public string $customerId;
    public string $arrivedAt;
    public ?string $departedAt;
    public array $transactions;

    public function __construct(
        string $zoneId,
        string $routeId,
        string $customerId,
        string $arrivedAt,
        ?string $departedAt = null,
        array $transactions = []
    ) {
        parent::__construct($zoneId);
        $this->routeId = $routeId;
        $this->customerId = $customerId;
        $this->arrivedAt = $arrivedAt;
        $this->departedAt = $departedAt;
        $this->transactions = $transactions;
    }

    public function toPayload(): array
    {
        return array_merge(parent::toPayload(), [
            'route_id' => $this->routeId,
            'customer_id' => $this->customerId,
            'arrived_at' => $this->arrivedAt,
            'departed_at' => $this->departedAt,
            'transactions' => $this->transactions,
        ]);
    }
}
