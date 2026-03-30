<?php

namespace App\Events;

class LeadConverted
{
    public function __construct(
        public string $uuid,
        public int|string $leadId,
        public array $data
    ) {}
}
