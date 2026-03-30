<?php

namespace App\Services\Projectors;

use App\Services\Projector;
use Illuminate\Support\Facades\DB;
use App\Models\DomainOutbox;

class HrProjector extends Projector
{
    public function handleEmployeeCreated(array $payload, DomainOutbox $event): void
    {
        DB::table('employees')->updateOrInsert(
            [
                'entreprise_id' => $payload['entrepriseId'],
                'employee_id' => $payload['employeeId'],
            ],
            [
                'first_name' => $payload['data']['first_name'],
                'last_name' => $payload['data']['last_name'],
                'email' => $payload['data']['email'] ?? null,
                'phone' => $payload['data']['phone'] ?? null,
                'job_title' => $payload['data']['job_title'] ?? null,
                'hire_date' => $payload['data']['hire_date'] ?? null,
                'salary' => $payload['data']['salary'] ?? 0,
                'last_event_id' => $event->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
