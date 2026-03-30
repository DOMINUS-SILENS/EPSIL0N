<?php

namespace App\Aggregates;

use App\Events\CustomerVisited;
use App\Events\MarketingInteractionRecorded;
use App\Events\CommunicationLogged;

class CrmAggregate extends AggregateRoot
{
    public function recordVisit(int $contactId, int $entrepriseId, string $outcome, array $meta = []): static
    {
        $this->recordThat(new CustomerVisited($this->uuid(), $contactId, $entrepriseId, $outcome, $meta));
        return $this;
    }

    public function logMarketingLead(int $contactId, int $entrepriseId, string $campaignId, string $type, array $meta = []): static
    {
        $this->recordThat(new MarketingInteractionRecorded($this->uuid(), $contactId, $entrepriseId, $campaignId, $type, $meta));
        return $this;
    }

    public function logCommunication(int $contactId, int $entrepriseId, string $channel, string $dir, array $content): static
    {
        $this->recordThat(new CommunicationLogged($this->uuid(), $contactId, $entrepriseId, $channel, $dir, $content));
        return $this;
    }
}
