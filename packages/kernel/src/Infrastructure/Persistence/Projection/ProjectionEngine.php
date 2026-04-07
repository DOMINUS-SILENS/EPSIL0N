<?php

declare(strict_types=1);

namespace Spiral\Kernel\Infrastructure\Persistence\Projection;

use Spiral\Kernel\Infrastructure\Contract\Projection\IProjectionEngine;
use Spiral\Kernel\Infrastructure\Contract\Projection\IEventProjector;
use Spiral\Kernel\Domain\Shared\Event\DomainEvent;

/**
 * Implementation of SDR-009: Mobile Projection Feed.
 * Orchestrates the flow of events into projectors and ensures
 * ordered delivery for the mobile sync feed.
 */
final class ProjectionEngine implements IProjectionEngine
{
    /** @var IEventProjector[] */
    private array $projectors = [];

    public function registerProjector(IEventProjector $projector): void
    {
        $this->projectors[$projector->getProjectionId()] = $projector;
    }

    public function dispatch(DomainEvent $event): void
    {
        foreach ($this->projectors as $projector) {
            $projector->project($event);
        }
    }

    public function replay(string $projectionId, int $fromVersion): void
    {
        // Implementation would load events from EventStore and call $projector->project()
    }
}
