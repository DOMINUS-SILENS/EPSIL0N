<?php

namespace App\Events;

class EmployeeCreated
{
    public function __construct(
        public string $uuid,
        public int $employeeId,
        public int $entrepriseId,
        public array $data
    ) {}
}
