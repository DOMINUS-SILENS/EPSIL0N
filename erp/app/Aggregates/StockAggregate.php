<?php

namespace App\Aggregates;

use App\Events\StockReceived;
use App\Events\StockConsumed;
use App\Events\StockTransferred;
use App\Events\StockAdjusted;
use Exception;

class StockAggregate extends AggregateRoot
{
    protected float $balance = 0.0;
    protected int $depotId;
    protected int $articleId;
    protected int $entrepriseId;

    protected function applyStockReceived(StockReceived $event): void
    {
        $this->balance += $event->quantity;
        $this->entrepriseId = $event->entrepriseId;
        $this->depotId = $event->depotId;
        $this->articleId = $event->articleId;
    }

    protected function applyStockConsumed(StockConsumed $event): void
    {
        $this->balance -= $event->quantity;
    }

    protected function applyStockTransferred(StockTransferred $event): void
    {
        if ($event->sourceDepotId === $this->depotId) {
            $this->balance -= $event->quantity;
        } elseif ($event->targetDepotId === $this->depotId) {
            $this->balance += $event->quantity;
        }
    }

    protected function applyStockAdjusted(StockAdjusted $event): void
    {
        $this->balance = $event->actualQuantity;
    }

    /**
     * Snapshottable interface
     */
    protected function toSnapshot(): array
    {
        return [
            'balance' => $this->balance,
            'depotId' => $this->depotId,
            'articleId' => $this->articleId,
            'entrepriseId' => $this->entrepriseId,
        ];
    }

    protected function fromSnapshot(array $data): void
    {
        $this->balance = $data['balance'] ?? 0.0;
        $this->depotId = $data['depotId'] ?? 0;
        $this->articleId = $data['articleId'] ?? 0;
        $this->entrepriseId = $data['entrepriseId'] ?? 0;
    }

    public function receive(int $depotId, int $articleId, int $entrepriseId, float $quantity, string $reason): static
    {
        if ($quantity <= 0) {
            throw new Exception("Physical bounds violation: received stocks must be strictly positive.");
        }
        $this->recordThat(new StockReceived($this->uuid(), $depotId, $articleId, $entrepriseId, $quantity, $reason));
        return $this;
    }

    public function consume(int $depotId, int $articleId, int $entrepriseId, float $quantity, string $reason): static
    {
        if ($quantity <= 0) {
            throw new Exception("Physical bounds violation: consumed stocks must be strictly positive.");
        }

        if ($this->balance < $quantity) {
            throw new Exception("Insufficient stock for article {$articleId} in depot {$depotId}. Available: {$this->balance}, Requested: {$quantity}");
        }

        $this->recordThat(new StockConsumed($this->uuid(), $depotId, $articleId, $entrepriseId, $quantity, $reason));
        return $this;
    }

    public function transfer(int $sourceDepotId, int $targetDepotId, int $articleId, int $entrepriseId, float $quantity): static
    {
        if ($quantity <= 0) {
            throw new Exception("Physical bounds violation: stock transfers must transmit absolute positive vectors.");
        }
        if ($sourceDepotId === $targetDepotId) {
            throw new Exception("Logical anomaly: Origin and destination depots match. Net zero mutation ignored.");
        }

        if ($this->balance < $quantity) {
            throw new Exception("Insufficient stock for article {$articleId} in source depot {$sourceDepotId}. Available: {$this->balance}, Requested: {$quantity}");
        }

        $this->recordThat(new StockTransferred($this->uuid(), $sourceDepotId, $targetDepotId, $articleId, $entrepriseId, $quantity));
        return $this;
    }

    public function adjust(int $depotId, int $articleId, int $entrepriseId, float $actualQuantity, float $delta): static
    {
        if ($actualQuantity < 0) {
            throw new Exception("Physical bounds violation: Warehouse quantities cannot exist within negative topology natively.");
        }
        $this->recordThat(new StockAdjusted($this->uuid(), $depotId, $articleId, $entrepriseId, $actualQuantity, $delta));
        return $this;
    }
}
