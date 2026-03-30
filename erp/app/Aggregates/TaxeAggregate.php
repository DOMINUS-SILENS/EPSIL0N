<?php

namespace App\Aggregates;

use App\Events\TaxeCreated;
use App\Events\TaxeUpdated;

class TaxeAggregate extends AggregateRoot
{
    public function create(array $data): self
    {
        $this->recordThat(new TaxeCreated($this->uuid(), $data));
        return $this;
    }

    public function update(array $data): self
    {
        $this->recordThat(new TaxeUpdated($this->uuid(), $data));
        return $this;
    }

    protected function applyTaxeCreated(TaxeCreated $event): void
    {
        // State update logic for tests
    }

    protected function applyTaxeUpdated(TaxeUpdated $event): void
    {
        // State update logic for tests
    }
}
