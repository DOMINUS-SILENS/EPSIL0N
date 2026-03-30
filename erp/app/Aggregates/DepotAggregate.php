<?php

namespace App\Aggregates;

use App\Events\DepotCreated;
use App\Events\DepotUpdated;

class DepotAggregate extends AggregateRoot
{
    public function create(array $data): self
    {
        $this->recordThat(new DepotCreated($this->uuid(), $data));
        return $this;
    }

    public function update(array $data): self
    {
        $this->recordThat(new DepotUpdated($this->uuid(), $data));
        return $this;
    }
}
