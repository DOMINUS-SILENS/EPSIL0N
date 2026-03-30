<?php

namespace App\Aggregates;

use App\Events\ProjectCreated;

class ProjectAggregate extends AggregateRoot
{
    public function createProject(int $projectId, int $entrepriseId, string $name, array $data): static
    {
        $this->recordThat(new ProjectCreated(
            $this->uuid(),
            $projectId,
            $entrepriseId,
            $name,
            $data
        ));

        return $this;
    }

    protected function applyProjectCreated(ProjectCreated $event): void
    {
        $this->tenantId = $event->entrepriseId;
    }
}
