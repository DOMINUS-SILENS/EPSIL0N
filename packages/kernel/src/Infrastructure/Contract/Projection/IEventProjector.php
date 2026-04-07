<?php

declare(strict_types=1);

namespace Spiral\Kernel\Infrastructure\Contract\Projection;

use Spiral\Kernel\Domain\Shared\Event\DomainEvent;

interface IEventProjector
{
    public function project(DomainEvent $event): void;
    public function getProjectionId(): string;
}
