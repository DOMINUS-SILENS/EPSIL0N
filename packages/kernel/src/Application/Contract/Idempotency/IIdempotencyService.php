<?php

declare(strict_types=1);

namespace Spiral\Kernel\Application\Contract\Idempotency;

use Spiral\Kernel\Domain\Identity\CorrelationId;

interface IIdempotencyService
{
    /**
     * Checks if a request with the given correlation ID has already been processed.
     *
     * @return bool True if the request is new, false if it has been processed.
     */
    public function isProcessed(CorrelationId $correlationId): bool;

    /**
     * Marks a correlation ID as processed.
     */
    public function markAsProcessed(CorrelationId $correlationId, string $resultHash): void;
}
