<?php

declare(strict_types=1);

namespace Spiral\Organization\Tests\Integration;

use Spiral\Kernel\Domain\Shared\Event\DomainEvent;
use Spiral\Kernel\Domain\Shared\Event\StoredEvent;
use Spiral\Organization\Domain\Event\OrganizationActivated;
use Spiral\Organization\Domain\Event\OrganizationDeactivated;
use Spiral\Organization\Domain\Event\OrganizationRegistered;
use Spiral\Organization\Domain\Event\OrganizationRenamed;
use Spiral\Organization\Domain\Event\OrganizationSlugChanged;
use Spiral\Organization\Domain\Event\OrganizationTimezoneChanged;

/**
 * Event hydrator for Organization context using composition pattern.
 *
 * This class demonstrates the correct architectural boundary:
 * - Kernel provides generic StoredEvent structure
 * - Bounded context owns event-specific reconstruction
 *
 * Uses composition, not inheritance, keeping kernel final classes intact.
 */
final class OrganizationEventHydrator
{
    /**
     * Hydrate a stored event to a domain event.
     */
    public function hydrate(StoredEvent $storedEvent): DomainEvent
    {
        $eventClass = $storedEvent->eventClassName;

        return match ($eventClass) {
            OrganizationRegistered::class => OrganizationRegistered::fromStorage($storedEvent->payload, $storedEvent->metadata),
            OrganizationRenamed::class => OrganizationRenamed::fromStorage($storedEvent->payload, $storedEvent->metadata),
            OrganizationSlugChanged::class => OrganizationSlugChanged::fromStorage($storedEvent->payload, $storedEvent->metadata),
            OrganizationTimezoneChanged::class => OrganizationTimezoneChanged::fromStorage($storedEvent->payload, $storedEvent->metadata),
            OrganizationActivated::class => OrganizationActivated::fromStorage($storedEvent->payload, $storedEvent->metadata),
            OrganizationDeactivated::class => OrganizationDeactivated::fromStorage($storedEvent->payload, $storedEvent->metadata),
            default => throw new \RuntimeException(
                sprintf('Unsupported organization event: %s', $eventClass)
            ),
        };
    }

    /**
     * Hydrate multiple stored events.
     *
     * @param list<StoredEvent> $storedEvents
     * @return list<DomainEvent>
     */
    public function hydrateAll(array $storedEvents): array
    {
        return \array_map(fn(StoredEvent $e) => $this->hydrate($e), $storedEvents);
    }
}
