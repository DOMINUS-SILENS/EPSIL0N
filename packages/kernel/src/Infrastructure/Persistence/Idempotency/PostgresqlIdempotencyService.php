<?php

declare(strict_types=1);

namespace Spiral\Kernel\Infrastructure\Persistence\Idempotency;

use Spiral\Kernel\Application\Contract\Idempotency\IIdempotencyService;
use Spiral\Kernel\Domain\Identity\CorrelationId;

final class PostgresqlIdempotencyService implements IIdempotencyService
{
    public function __construct(
        private readonly \PDO $pdo
    ) {}

    public function isProcessed(CorrelationId $correlationId): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM idempotency_store WHERE correlation_id = :cid");
        $stmt->execute(['cid' => $correlationId->toString()]);
        return (bool) $stmt->fetch();
    }

    public function markAsProcessed(CorrelationId $correlationId, string $resultHash): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO idempotency_store (correlation_id, result_hash, created_at) VALUES (:cid, :hash, NOW())");
        $stmt->execute([
            'cid' => $correlationId->toString(),
            'hash' => $resultHash
        ]);
    }
}
