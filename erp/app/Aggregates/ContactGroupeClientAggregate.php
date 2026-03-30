<?php

namespace App\Aggregates;

use App\Events\ContactGroupeClientCreated;
use App\Events\ContactGroupeClientUpdated;

class ContactGroupeClientAggregate extends AggregateRoot
{
    public function create(array $data): self
    {
        $this->recordThat(new ContactGroupeClientCreated($this->uuid(), $data));
        return $this;
    }

    public function update(array $data): self
    {
        $this->recordThat(new ContactGroupeClientUpdated($this->uuid(), $data));
        return $this;
    }
}
