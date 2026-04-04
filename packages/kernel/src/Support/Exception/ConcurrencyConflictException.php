<?php

declare(strict_types=1);

namespace Spiral\Kernel\Support\Exception;

/**
 * Exception thrown when optimistic concurrency conflict is detected.
 *
 * This exception is thrown when an aggregate's version at persistence time
 * does not match the expected version, indicating that another process
 * has modified the aggregate concurrently.
 *
 * This is an expected failure mode in event-sourced systems using
 * optimistic concurrency control. The client should retry the operation
 * after rehydrating the aggregate to get the latest state.
 *
 * @package Spiral\Kernel\Support\Exception
 */
final class ConcurrencyConflictException extends KernelException
{
    /**
     * @param string $aggregateType The type of aggregate that had the conflict
     * @param string $aggregateId The ID of the aggregate
     * @param int $expectedVersion The version the operation expected
     * @param int $actualVersion The version found in the event store
     */
    public function __construct(
        private readonly string $aggregateType,
        private readonly string $aggregateId,
        private readonly int $expectedVersion,
        private readonly int $actualVersion,
        ?\Throwable $previous = null
    ) {
        $message = sprintf(
            'Concurrency conflict for %s(%s): expected version %d, found %d',
            $aggregateType,
            $aggregateId,
            $expectedVersion,
            $actualVersion
        );
        parent::__construct($message, 0, $previous);
    }

    public function getErrorCode(): string
    {
        return 'CONCURRENCY_CONFLICT';
    }

    public function getContext(): array
    {
        return [
            'aggregateType' => $this->aggregateType,
            'aggregateId' => $this->aggregateId,
            'expectedVersion' => $this->expectedVersion,
            'actualVersion' => $this->actualVersion,
        ];
    }

    public function getAggregateType(): string
    {
        return $this->aggregateType;
    }

    public function getAggregateId(): string
    {
        return $this->aggregateId;
    }

    public function getExpectedVersion(): int
    {
        return $this->expectedVersion;
    }

    public function getActualVersion(): int
    {
        return $this->actualVersion;
    }
}