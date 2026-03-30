<?php

namespace App\Events;

class MarketingInteractionRecorded
{
    public string $uuid;
    public int $contactId;
    public int $entrepriseId;
    public string $campaignId;
    public string $interactionType; // e.g., 'interest_expressed', 'catalog_given'
    public array $metadata;

    public function __construct(string $uuid, int $contactId, int $entrepriseId, string $campaignId, string $interactionType, array $metadata = [])
    {
        $this->uuid = $uuid;
        $this->contactId = $contactId;
        $this->entrepriseId = $entrepriseId;
        $this->campaignId = $campaignId;
        $this->interactionType = $interactionType;
        $this->metadata = $metadata;
    }
}
