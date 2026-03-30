<?php

namespace App\Aggregates;

use App\Events\ArticleFamilleCreated;
use App\Events\ArticleFamilleUpdated;

class ArticleFamilleAggregate extends AggregateRoot
{
    public function create(array $data): self
    {
        $this->recordThat(new ArticleFamilleCreated($this->uuid(), $data));
        return $this;
    }

    public function update(array $data): self
    {
        $this->recordThat(new ArticleFamilleUpdated($this->uuid(), $data));
        return $this;
    }
}
