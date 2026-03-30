<?php

namespace App\Aggregates;

use App\Events\ArticleMarqueCreated;
use App\Events\ArticleMarqueUpdated;

class ArticleMarqueAggregate extends AggregateRoot
{
    public function create(array $data): self
    {
        $this->recordThat(new ArticleMarqueCreated($this->uuid(), $data));
        return $this;
    }

    public function update(array $data): self
    {
        $this->recordThat(new ArticleMarqueUpdated($this->uuid(), $data));
        return $this;
    }
}
