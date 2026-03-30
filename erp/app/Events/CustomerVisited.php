<?php

namespace App\Events;

class CustomerVisited
{
    public string $uuid;
    public int $contactId;
    public int $entrepriseId;
    public string $outcome;
    public array $metadata;

    public function __construct(string $uuid, int $contactId, int $entrepriseId, string $outcome, array $metadata = [])
    {
        $this->uuid = $uuid;
        $this->contactId = $contactId;
        $this->entrepriseId = $entrepriseId;
        $this->outcome = $outcome;
        $this->metadata = $metadata;
    }
}
