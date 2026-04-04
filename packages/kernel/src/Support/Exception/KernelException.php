<?php

declare(strict_types=1);

namespace Spiral\Kernel\Support\Exception;

/**
 * Base exception for all Kernel-level failures.
 *
 * This is the root of the Kernel exception hierarchy. All exceptions
 * thrown by the Kernel MUST extend from this class to ensure consistent
 * error handling across the system.
 *
 * Kernel exceptions represent structural/infrastructure failures that
 * are NOT business domain concerns - they indicate programming errors,
 * configuration issues, or system-level problems.
 *
 * For business domain failures, use DomainException or its subclasses.
 *
 * @package Spiral\Kernel\Support\Exception
 */
abstract class KernelException extends \Exception
{
    /**
     * Returns a machine-readable error code for this exception type.
     *
     * @return non-empty-string
     */
    abstract public function getErrorCode(): string;

    /**
     * Returns additional context about this failure.
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return [];
    }
}