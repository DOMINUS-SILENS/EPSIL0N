<?php

namespace App\Aggregates;

use App\Aggregates\AggregateRoot;

class LeadAggregate extends AggregateRoot
{
    public function createLead(array $attributes): self
    {
        $this->recordThat(new \App\Events\LeadCreated($this->uuid(), $this->uuid(), $attributes));
        return $this;
    }

    public function updateLead(array $attributes): self
    {
        $this->recordThat(new \App\Events\LeadUpdated($this->uuid(), $this->uuid(), $attributes));
        return $this;
    }

    public function convertToCustomer(): self
    {
        $this->recordThat(new \App\Events\LeadConverted($this->uuid(), $this->uuid(), []));
        return $this;
    }
}
