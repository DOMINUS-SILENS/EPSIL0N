<?php

namespace App\Events;

class LeadUpdated
{
    public function __construct(
        public string $uuid,
        public int|string $leadId,
        public array $data
    ) {}
}
