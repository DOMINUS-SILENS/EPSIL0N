<?php

namespace App\Aggregates;

use App\Events\ContactCreated;
use App\Events\ContactUpdated;

class ContactAggregate extends AggregateRoot
{
    public function createContact(int $contactId, array $data): self
    {
        // Add Business Invariants / Guards here (e.g. ContractService / IntentService)
        
        $this->recordThat(new ContactCreated(
            $this->uuid(),
            $contactId,
            $data['entreprise_id'],
            $data['contact_nom'] ?? null,
            $data['contact_prenom'] ?? null,
            $data['entreprise_id'] ?? null,
            $data['contact_raison_sociale'] ?? null
        ));

        return $this;
    }

    public function updateContact(int $contactId, array $data): self
    {
        // Business Rule validations...

        $this->recordThat(new ContactUpdated(
            $this->uuid(),
            $contactId,
            $data['entreprise_id'],
            $data['contact_nom'] ?? null,
            $data['contact_prenom'] ?? null,
            $data['entreprise_id'] ?? null,
            $data['contact_raison_sociale'] ?? null
        ));

        return $this;
    }
}
