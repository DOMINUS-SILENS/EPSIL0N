<?php

namespace App\Events;

class LeadCreated
{
    public function __construct(
        public string $uuid,
        public int|string $leadId,
        public array $data
    ) {}
}
