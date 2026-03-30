<?php

namespace App\Aggregates;

use App\Events\EmployeeCreated;

class HrAggregate extends AggregateRoot
{
    public function createEmployee(int $employeeId, int $entrepriseId, array $data): static
    {
        $this->recordThat(new EmployeeCreated(
            $this->uuid(),
            $employeeId,
            $entrepriseId,
            $data
        ));

        return $this;
    }

    protected function applyEmployeeCreated(EmployeeCreated $event): void
    {
        $this->tenantId = $event->entrepriseId;
    }
}
