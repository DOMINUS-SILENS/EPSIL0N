<?php

declare(strict_types=1);

namespace Spiral\Kernel\Application\Service;

use Spiral\Kernel\Domain\Shared\Error\ErrorCode;
use Spiral\Kernel\Domain\Shared\Error\ErrorDetail;
use Spiral\Kernel\Support\Exception\KernelException;

/**
 * Factory for creating ErrorDetail from exceptions.
 *
 * This factory bridges the cross-layer dependency: KernelException (Support/Infrastructure)
 * → ErrorDetail (Domain).
 *
 * By placing this factory in the Application layer, we preserve the dependency law:
 * - Domain primitives remain independent of infrastructure
 * - Application layer correctly depends on both Domain and Infrastructure
 * - KernelException coupling is resolved at application boundaries
 *
 * @package Spiral\Kernel\Application\Service
 */
final class ErrorDetailFactory
{
    /**
     * Create an error detail from a Kernel exception.
     *
     * @param KernelException $exception The exception to convert
     * @param string|null $traceIdentifier Optional trace identifier for observability
     * @param string|null $correlationIdentifier Optional correlation identifier for tracing
     * @return ErrorDetail
     */
    public static function fromException(
        KernelException $exception,
        ?string $traceIdentifier = null,
        ?string $correlationIdentifier = null
    ): ErrorDetail {
        $errorCode = ErrorCode::fromString($exception->getErrorCode());
        $errorDetail = ErrorDetail::withContextData(
            code: $errorCode,
            message: $exception->getMessage() ?: 'An error occurred',
            contextData: $exception->getContext()
        );

        if ($traceIdentifier !== null) {
            $errorDetail = $errorDetail->withTraceIdentifiers($traceIdentifier, $correlationIdentifier);
        } elseif ($correlationIdentifier !== null) {
            // If only correlation ID provided (no trace ID), still need to set it
            $errorDetail = $errorDetail->withAddedContext(['correlationId' => $correlationIdentifier]);
        }

        return $errorDetail;
    }

    /**
     * Create an error detail from any exception, with safe fallback.
     *
     * Returns a generic error if the exception is not a KernelException.
     *
     * @param \Throwable $exception Any exception
     * @param string|null $traceIdentifier Optional trace identifier
     * @param string|null $correlationIdentifier Optional correlation identifier
     * @return ErrorDetail
     */
    public static function fromThrowable(
        \Throwable $exception,
        ?string $traceIdentifier = null,
        ?string $correlationIdentifier = null
    ): ErrorDetail {
        if ($exception instanceof KernelException) {
            return self::fromException($exception, $traceIdentifier, $correlationIdentifier);
        }

        // Fallback for non-Kernel exceptions
        $errorDetail = ErrorDetail::withContextData(
            code: ErrorCode::kernel('UNHANDLED_EXCEPTION'),
            message: $exception->getMessage() ?: 'An unhandled exception occurred',
            contextData: [
                'exception_class' => $exception::class,
                'exception_code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]
        );

        if ($traceIdentifier !== null) {
            $errorDetail = $errorDetail->withTraceIdentifiers($traceIdentifier, $correlationIdentifier);
        } elseif ($correlationIdentifier !== null) {
            // If only correlation ID provided (no trace ID), still need to set it
            $errorDetail = $errorDetail->withAddedContext(['correlationId' => $correlationIdentifier]);
        }

        return $errorDetail;
    }
}

