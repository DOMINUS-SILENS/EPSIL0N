<?php declare(strict_types=1);

namespace Spiral\Kernel\Support\Exception;

/**
 * Base exception for event store failures.
 *
 * Subclasses:
 * - ConcurrencyConflictException (version mismatch)
 * - NotFoundException (stream not found)
 * - EventStoreException (general failures)
 *
 * Use for: Failed appends, invalid streams, persistence errors, etc.
 */
class EventStoreException extends KernelException
{
    public static function failedToAppend(string $streamId, string $reason): self
    {
        return new self(\sprintf(
            'Failed to append events to stream "%s": %s',
            $streamId,
            $reason
        ));
    }

    public static function failedToLoad(string $streamId, string $reason): self
    {
        return new self(\sprintf(
            'Failed to load stream "%s": %s',
            $streamId,
            $reason
        ));
    }

    public static function invalidStreamState(string $streamId, string $detail): self
    {
        return new self(\sprintf(
            'Invalid stream state for "%s": %s',
            $streamId,
            $detail
        ));
    }

    public function getErrorCode(): string
    {
        return 'EVENTSTORE_FAILURE';
    }
}
