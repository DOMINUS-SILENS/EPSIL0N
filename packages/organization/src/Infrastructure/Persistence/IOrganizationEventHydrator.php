<?php

declare(strict_types=1);

namespace Spiral\Organization\Infrastructure\Persistence;

use Spiral\Kernel\Domain\Shared\Event\DomainEvent;
use Spiral\Kernel\Domain\Shared\Event\StoredEvent;

/**
 * Hydrator interface for Organization context events.
 *
 * Defines the contract for reconstructing domain events from stored events.
 * Implemented by context-specific hydrators that understand organization event types.
 */
interface IOrganizationEventHydrator
{
    /**
     * Hydrate a stored event to a domain event.
     */
    public function hydrate(StoredEvent $storedEvent): DomainEvent;

    /**
     * Hydrate multiple stored events.
     *
     * @param list<StoredEvent> $storedEvents
     * @return list<DomainEvent>
     */
    public function hydrateAll(array $storedEvents): array;
}
