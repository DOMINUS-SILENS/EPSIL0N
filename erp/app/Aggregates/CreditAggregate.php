<?php

namespace App\Aggregates;

use App\Events\CreditCreated;
use App\Events\CreditUpdated;

class CreditAggregate extends AggregateRoot
{
    public function create(array $data): self
    {
        $this->recordThat(new CreditCreated($this->uuid(), $data));
        return $this;
    }

    public function update(array $data): self
    {
        $this->recordThat(new CreditUpdated($this->uuid(), $data));
        return $this;
    }
}
