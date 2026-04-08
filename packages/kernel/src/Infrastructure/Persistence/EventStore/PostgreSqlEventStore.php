<?php

declare(strict_types=1);

namespace Spiral\Kernel\Infrastructure\Persistence\EventStore;

use PDO;
use Spiral\Kernel\Domain\Identity\TenantId;
use Spiral\Kernel\Domain\Shared\Event\DomainEvent;
use Spiral\Kernel\Domain\Shared\Event\ExpectedVersion;
use Spiral\Kernel\Domain\Shared\Event\StoredEvent;
use Spiral\Kernel\Infrastructure\Contract\EventStore\IEventStore;
use Spiral\Kernel\Support\Exception\ConcurrencyConflictException;
use Spiral\Kernel\Support\Exception\EventStoreException;

/**
 * PostgreSQL implementation of the Event Store.
 *
 * Enforces:
 * - Append transactionality
 * - Unique stream version (optimistic concurrency)
 * - Ordering
 * - Metadata persistence
 * - Tenant-aware stream access
 */
final class PostgreSqlEventStore implements IEventStore
{
    private const STREAMS_TABLE = 'event_streams';
    private const EVENTS_TABLE = 'domain_events';

    private readonly EventSerializer $serializer;

    public function __construct(
        private readonly PDO $connection,
        ?EventSerializer $serializer = null,
    ) {
        $this->serializer = $serializer ?? new EventSerializer();
    }

    public function append(
        TenantId $tenantId,
        string $streamId,
        ExpectedVersion $expectedVersion,
        array $events,
    ): int {
        if (\count($events) === 0) {
            return $this->getStreamVersion($tenantId, $streamId);
        }

        $currentVersion = $this->getStreamVersion($tenantId, $streamId);

        $this->validateExpectedVersion($expectedVersion, $currentVersion, $streamId);

        return $this->performAppend($tenantId, $streamId, $currentVersion, $events);
    }

    public function load(
        TenantId $tenantId,
        string $streamId,
        int $fromVersion = 0,
        ?int $maxCount = null,
    ): array {
        $sql = \sprintf(
            'SELECT * FROM %s WHERE tenant_id = :tenant_id AND stream_id = :stream_id AND stream_version >= :from_version',
            $this->quoteIdentifier(self::EVENTS_TABLE)
        );

        $params = [
            'tenant_id' => $tenantId->toString(),
            'stream_id' => $streamId,
            'from_version' => $fromVersion,
        ];

        if ($maxCount !== null) {
            $sql .= ' ORDER BY stream_version ASC LIMIT :limit';
            $params['limit'] = $maxCount;
        } else {
            $sql .= ' ORDER BY stream_version ASC';
        }

        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return \array_map(
                fn(array $row) => StoredEvent::fromDatabaseRow($row),
                $rows
            );
        } catch (\PDOException $e) {
            $reason = $this->buildErrorReason($e, "loading events from stream version $fromVersion");
            throw EventStoreException::failedToLoad($streamId, $reason, $e);
        }
    }

    public function loadReverse(
        TenantId $tenantId,
        string $streamId,
        int $fromVersion = 0,
        ?int $maxCount = null,
    ): array {
        $sql = \sprintf(
            'SELECT * FROM %s WHERE tenant_id = :tenant_id AND stream_id = :stream_id AND stream_version <= :from_version',
            $this->quoteIdentifier(self::EVENTS_TABLE)
        );

        $params = [
            'tenant_id' => $tenantId->toString(),
            'stream_id' => $streamId,
            'from_version' => $fromVersion,
        ];

        if ($maxCount !== null) {
            $sql .= ' ORDER BY stream_version DESC LIMIT :limit';
            $params['limit'] = $maxCount;
        } else {
            $sql .= ' ORDER BY stream_version DESC';
        }

        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return \array_map(
                fn(array $row) => StoredEvent::fromDatabaseRow($row),
                $rows
            );
        } catch (\PDOException $e) {
            $reason = $this->buildErrorReason($e, "loading events in reverse from stream version $fromVersion");
            throw EventStoreException::failedToLoad($streamId, $reason, $e);
        }
    }

    public function getStreamVersion(TenantId $tenantId, string $streamId): int
    {
        $sql = \sprintf(
            'SELECT version FROM %s WHERE tenant_id = :tenant_id AND stream_id = :stream_id',
            $this->quoteIdentifier(self::STREAMS_TABLE)
        );

        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([
                'tenant_id' => $tenantId->toString(),
                'stream_id' => $streamId,
            ]);

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            /** @var array<string, mixed>|false $row */
            if ($row === false) {
                return 0;
            }

            /** @var int $version */
            $version = $row['version'];
            return $version;
        } catch (\PDOException $e) {
            $reason = $this->buildErrorReason($e, 'unable to determine stream version');
            throw EventStoreException::failedToLoad($streamId, $reason, $e);
        }
    }

    private function validateExpectedVersion(
        ExpectedVersion $expectedVersion,
        int $currentVersion,
        string $streamId,
    ): void {
        if ($expectedVersion->isAny()) {
            return;
        }

        if ($expectedVersion->isNoStream()) {
            if ($currentVersion > 0) {
                throw new ConcurrencyConflictException(
                    aggregateType: 'Stream',
                    aggregateId: $streamId,
                    expectedVersion: 0,
                    actualVersion: $currentVersion,
                    previous: null,
                );
            }
            return;
        }

        // Exact mode
        if (!$expectedVersion->isSatisfiedBy($currentVersion)) {
            throw new ConcurrencyConflictException(
                aggregateType: 'Stream',
                aggregateId: $streamId,
                expectedVersion: $expectedVersion->version(),
                actualVersion: $currentVersion,
                previous: null,
            );
        }
    }

    /**
     * @param list<DomainEvent> $events
     */
    private function performAppend(
        TenantId $tenantId,
        string $streamId,
        int $currentVersion,
        array $events,
    ): int {
        $this->connection->beginTransaction();

        try {
            // 1. Ensure stream exists and update version atomically
            $streamSql = \sprintf(
                'INSERT INTO %s (tenant_id, stream_id, version)
                 VALUES (:tenant_id, :stream_id, :version)
                 ON CONFLICT (tenant_id, stream_id)
                 DO UPDATE SET version = EXCLUDED.version
                 RETURNING version',
                $this->quoteIdentifier(self::STREAMS_TABLE)
            );

            $newVersionFinal = $currentVersion + \count($events);
            $stmtStream = $this->connection->prepare($streamSql);
            $stmtStream->execute([
                'tenant_id' => $tenantId->toString(),
                'stream_id' => $streamId,
                'version' => $newVersionFinal,
            ]);

            // 2. Append events to the log
            $eventSql = \sprintf(
                'INSERT INTO %s (tenant_id, stream_id, stream_version, event_id, event_type, correlation_id, causation_id, occurred_at, schema_version, payload, metadata)
                 VALUES (:tenant_id, :stream_id, :stream_version, :event_id, :event_type, :correlation_id, :causation_id, :occurred_at, :schema_version, :payload, :metadata)',
                $this->quoteIdentifier(self::EVENTS_TABLE)
            );

            $outboxSql = 'INSERT INTO outbox (tenant_id, event_id, stream_id, stream_version, event_type, payload, metadata, occurred_at, status)
                 VALUES (:tenant_id, :event_id, :stream_id, :stream_version, :event_type, :payload, :metadata, :occurred_at, \'pending\')';

            $stmtEvent = $this->connection->prepare($eventSql);
            $stmtOutbox = $this->connection->prepare($outboxSql);
            $version = $currentVersion + 1;

            // Extract correlation and causation from first event for error context
            $firstEvent = $events[0] ?? null;
            $correlationId = $firstEvent !== null ? $firstEvent->getCorrelationId()->toString() : null;
            $causationId = $firstEvent !== null ? $firstEvent->getCausationId()->toString() : null;

            foreach ($events as $event) {
                $storedEvent = $this->serializer->serialize($event, $streamId, $version);
                $row = $this->serializer->toDatabaseRow($storedEvent);

                /** @var array<string, mixed> $metadata */
                $metadata = $row['metadata'];

                $stmtEvent->execute([
                    'tenant_id' => $row['tenant_id'],
                    'stream_id' => $row['stream_id'],
                    'stream_version' => $row['stream_version'],
                    'event_id' => $row['event_id'],
                    'event_type' => $row['event_type'],
                    'correlation_id' => $metadata['correlation_id'] ?? null,
                    'causation_id' => $metadata['causation_id'] ?? null,
                    'occurred_at' => $row['occurred_at'],
                    'schema_version' => $metadata['schema_version'] ?? '1.0',
                    'payload' => $row['payload'],
                    'metadata' => $metadata,
                ]);

                $stmtOutbox->execute([
                    'tenant_id' => $row['tenant_id'],
                    'event_id' => $row['event_id'],
                    'stream_id' => $row['stream_id'],
                    'stream_version' => $row['stream_version'],
                    'event_type' => $row['event_type'],
                    'payload' => $row['payload'],
                    'metadata' => $row['metadata'],
                    'occurred_at' => $row['occurred_at'],
                ]);

                $version++;
            }

            $this->connection->commit();

            return $newVersionFinal;
        } catch (\PDOException $e) {
            $this->connection->rollBack();

            if ($e->getCode() === '23505') {
                throw new ConcurrencyConflictException(
                    aggregateType: 'Stream',
                    aggregateId: $streamId,
                    expectedVersion: $currentVersion,
                    actualVersion: $this->getStreamVersion($tenantId, $streamId),
                    previous: null,
                );
            }

            // Build detailed error reason with full context
            $eventCount = \count($events);
            $newVersion = $currentVersion + $eventCount;
            $reason = \sprintf(
                'append %d events to stream "%s" (tenant=%s, version %d→%d): %s',
                $eventCount,
                $streamId,
                $tenantId->toString(),
                $currentVersion,
                $newVersion,
                $this->buildErrorReason($e, 'database error')
            );

            // Add causation context if available
            if (!empty($events)) {
                $firstEvent = $events[0];
                $correlationIdStr = $firstEvent->getCorrelationId()->toString();
                $causationIdStr = $firstEvent->getCausationId()->toString();

                if ($correlationIdStr || $causationIdStr) {
                    $reason .= ' (';
                    if ($correlationIdStr) {
                        $reason .= "correlation=$correlationIdStr";
                    }
                    if ($causationIdStr) {
                        $reason .= ($correlationIdStr ? ', ' : '') . "causation=$causationIdStr";
                    }
                    $reason .= ')';
                }
            }

            throw EventStoreException::failedToAppend($streamId, $reason, $e);
        } catch (\Throwable $e) {
            $this->connection->rollBack();

            // Wrap any other exception with EventStoreException
            $reason = \sprintf(
                'append %d events to stream "%s": unexpected failure during event persistence',
                \count($events),
                $streamId
            );
            throw EventStoreException::failedToAppend($streamId, $reason, $e);
        }
    }

    private function buildErrorReason(\PDOException $e, string $context): string
    {
        $errorCode = $e->getCode();

        return match ($errorCode) {
            '08006', '08003' => "$context — database connection error: {$e->getMessage()}",
            '42P01' => "$context — event stream table missing: {$e->getMessage()}",
            '42703' => "$context — corrupted event stream schema: {$e->getMessage()}",
            '23503' => "$context — foreign key constraint violated: {$e->getMessage()}",
            default => "$context — {$e->getMessage()}",
        };
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '"' . \str_replace('"', '""', $identifier) . '"';
    }
}
