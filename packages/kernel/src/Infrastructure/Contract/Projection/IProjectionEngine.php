<?php

declare(strict_types=1);

namespace Spiral\Kernel\Infrastructure\Contract\Projection;

interface IProjectionEngine
{
    public function dispatch(DomainEvent $event): void;
    public function replay(string $projectionId, int $fromVersion): void;
}
