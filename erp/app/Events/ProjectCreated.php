<?php

namespace App\Events;

class ProjectCreated
{
    public function __construct(
        public string $uuid,
        public int $projectId,
        public int $entrepriseId,
        public string $name,
        public array $data
    ) {}
}
