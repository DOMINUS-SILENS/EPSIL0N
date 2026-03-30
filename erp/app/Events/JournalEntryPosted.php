<?php

namespace App\Events;

class JournalEntryPosted
{
    public string $uuid;
    public int $entrepriseId;
    public array $payload;
    public string $eventTime;

    public function __construct(string $uuid, int $entrepriseId, array $payload, string $eventTime)
    {
        $this->uuid = $uuid;
        $this->entrepriseId = $entrepriseId;
        $this->payload = $payload;
        $this->eventTime = $eventTime;
    }
}
