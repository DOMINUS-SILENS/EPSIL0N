<?php

namespace App\Aggregates;

use App\Aggregates\AggregateRoot;

class OrderAggregate extends AggregateRoot
{
    protected string $state = 'none';

    protected function applyOrderCreated(\App\Events\OrderCreated $event): void
    {
        $this->state = 'draft';
    }

    protected function applyOrderValidated(\App\Events\OrderValidated $event): void
    {
        $this->state = 'validated';
    }

    protected function applyOrderCancelled(\App\Events\OrderCancelled $event): void
    {
        $this->state = 'cancelled';
    }

    public function createOrder(array $attributes): self
    {
        if ($this->state !== 'none') {
            throw new \Exception("Order already exists.");
        }
        $this->recordThat(new \App\Events\OrderCreated($this->uuid(), $this->uuid(), $attributes));
        return $this;
    }

    public function updateOrder(array $attributes): self
    {
        if ($this->state !== 'draft') {
            throw new \Exception("Can only update draft orders.");
        }
        $this->recordThat(new \App\Events\OrderUpdated($this->uuid(), $this->uuid(), $attributes));
        return $this;
    }

    public function confirmOrder(): self
    {
        if ($this->state !== 'draft') {
            throw new \Exception("Can only confirm draft orders.");
        }
        $this->recordThat(new \App\Events\OrderValidated($this->uuid(), $this->uuid(), []));
        return $this;
    }

    public function cancelOrder(): self
    {
        if ($this->state === 'cancelled') {
            throw new \Exception("Order is already cancelled.");
        }
        $this->recordThat(new \App\Events\OrderCancelled($this->uuid(), $this->uuid(), []));
        return $this;
    }
}
