<?php

declare(strict_types=1);

namespace Spiral\Kernel\Infrastructure\Projection;

use Spiral\Kernel\Domain\Shared\Event\DomainEvent;

interface IProjectionEngine
{
    /**
     * Process a domain event by dispatching it to all interested projectors
     * and the mobile sync feed.
     */
    public function dispatch(DomainEvent $event): void;

    /**
     * Rebuild a specific projection by replaying events from the event store.
     *
     * @param string $projectionId Identifier for the read model to rebuild
     * @param int $fromVersion Version to start replaying from
     */
    public function replay(string $projectionId, int $fromVersion): void;
}
