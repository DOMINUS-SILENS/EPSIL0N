<?php

declare(strict_types=1);

namespace Spiral\Kernel\Infrastructure\Projection;

use PDO;
use Spiral\Kernel\Domain\Shared\Event\StoredEvent;
use Spiral\Kernel\Infrastructure\Persistence\EventStore\EventHydrator;
use Spiral\Kernel\Infrastructure\Projection\IProjectionEngine;
use Spiral\Kernel\Infrastructure\Projection\Sync\IMobileSyncFeed;

final class ProjectionRelayWorker
{
    public function __construct(
        private readonly PDO $connection,
        private readonly IProjectionEngine $projectionEngine,
        private readonly IMobileSyncFeed $syncFeed,
        private readonly EventHydrator $hydrator,
    ) {}

    public function processOutbox(int $batchSize = 100): int
    {
        $processedCount = 0;

        $sql = 'SELECT * FROM outbox WHERE status = \'pending\' ORDER BY created_at ASC LIMIT :limit FOR UPDATE SKIP LOCKED';
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(':limit', $batchSize, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            try {
                /** @var array<string, mixed> $row */
                $this->dispatchToProjections($row);
                /** @var int $id */
                $id = $row['id'];
                $this->markAsProcessed($id);
                $processedCount++;
            } catch (\Throwable $e) {
                /** @var int $id */
                $id = $row['id'];
                $this->markAsFailed($id, $e->getMessage());
            }
        }

        return $processedCount;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function dispatchToProjections(array $row): void
    {
        $storedEvent = StoredEvent::fromDatabaseRow($row);
        $domainEvent = $this->hydrator->hydrate($storedEvent);

        // 1. Update Read Models
        $this->projectionEngine->dispatch($domainEvent);

        // 2. Push to Mobile Sync Feed
        $this->syncFeed->addToSyncFeed($domainEvent);
    }

    private function markAsProcessed(int $id): void
    {
        $stmt = $this->connection->prepare('UPDATE outbox SET status = \'processed\', processed_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private function markAsFailed(int $id, string $error): void
    {
        $stmt = $this->connection->prepare('UPDATE outbox SET status = \'failed\', attempts = attempts + 1 WHERE id = :id');
        $stmt->execute(['id' => $id]);
        // Log error to monitoring system
    }
}
