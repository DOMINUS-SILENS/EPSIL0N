<?php

declare(strict_types=1);

namespace Spiral\Kernel\Infrastructure\Projection;

use Spiral\Kernel\Domain\Shared\Event\DomainEvent;
use Spiral\Kernel\Infrastructure\Projection\IEventProjector;
use Spiral\Kernel\Infrastructure\Projection\Sync\IMobileSyncFeed;
use Spiral\Kernel\Infrastructure\Contract\EventStore\IEventStore;

class PostgresqlProjectionEngine implements IProjectionEngine
{
    /**
     * @param IEventProjector[] $projectors
     */
    public function __construct(
        private readonly array $projectors,
        private readonly IMobileSyncFeed $syncFeed,
        private readonly IEventStore $eventStore
    ) {}

    public function dispatch(DomainEvent $event): void
    {
        foreach ($this->projectors as $projector) {
            if (in_array($event->getEventType(), $projector->handledEventTypes(), true)) {
                $projector->project($event);
            }
        }

        $this->syncFeed->addToSyncFeed($event);
    }

    public function replay(string $projectionId, int $fromVersion): void
    {
        // In a real implementation, this would:
        // 1. Identify which projector corresponds to the projectionId
        // 2. Fetch all events from IEventStore since $fromVersion
        // 3. Project them sequentially

        // For now, we establish the structural contract.
        // Implementation would depend on how projectionId is mapped to projectors.
    }
}
