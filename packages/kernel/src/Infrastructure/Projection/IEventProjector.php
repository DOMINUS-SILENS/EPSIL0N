<?php

declare(strict_types=1);

namespace Spiral\Kernel\Infrastructure\Projection;

use Spiral\Kernel\Domain\Shared\Event\DomainEvent;

interface IEventProjector
{
    /**
     * Project a domain event into a read model.
     */
    public function project(DomainEvent $event): void;

    /**
     * Returns a list of event types this projector handles.
     *
     * @return string[]
     */
    public function handledEventTypes(): array;
}
