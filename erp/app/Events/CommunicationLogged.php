<?php

namespace App\Events;

class CommunicationLogged
{
    public string $uuid;
    public int $contactId;
    public int $entrepriseId;
    public string $channel;
    public string $direction; // 'inbound', 'outbound'
    public array $content;

    public function __construct(string $uuid, int $contactId, int $entrepriseId, string $channel, string $direction, array $content)
    {
        $this->uuid = $uuid;
        $this->contactId = $contactId;
        $this->entrepriseId = $entrepriseId;
        $this->channel = $channel;
        $this->direction = $direction;
        $this->content = $content;
    }
}
