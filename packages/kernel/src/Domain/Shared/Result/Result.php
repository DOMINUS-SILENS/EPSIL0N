<?php

declare(strict_types=1);

namespace Spiral\Kernel\Domain\Shared\Result;

use Spiral\Kernel\Domain\Shared\Error\ErrorDetail;

/**
 * Result monad representing either a successful value or a structured failure.
 *
 * The Result type enforces explicit error handling without exceptions. Every
 * operation that can fail returns a Result, forcing callers to acknowledge
 * and handle the failure case explicitly.
 *
 * Result is immutable and implements the following semantics:
 * - Success: contains a typed value, accessible via unwrap()
 * - Failure: contains an ErrorDetail, accessible via error()
 *
 * Use Result for:
 * - Application layer service methods
 * - Command handler results
 * - Query handler results
 * - Domain service operations
 *
 * Do NOT use Result for:
 * - Unrecoverable programming errors (throw exceptions)
 * - Infrastructure failures (throw exceptions)
 * - Validation of caller input (throw ValidationException)
 *
 * @template-covariant TData The type of the successful value
 *
 * @package Spiral\Kernel\Domain\Shared\Result
 */
abstract class Result
{
    /**
     * Private constructor enforces use of factory methods.
     */
    private function __construct()
    {
    }

    /**
     * Create a successful result containing the given value.
     *
     * @template T of mixed
     * @param T $value The successful value
     * @return Success<T>
     */
    public static function success(mixed $value): Success
    {
        return new Success($value);
    }

    /**
     * Create a failed result containing the given error.
     *
     * @param ErrorDetail $error The error detail
     * @return Failure
     */
    public static function failure(ErrorDetail $error): Failure
    {
        return new Failure($error);
    }

    /**
     * Check if this result represents success.
     */
    abstract public function isSuccess(): bool;

    /**
     * Check if this result represents failure.
     */
    abstract public function isFailure(): bool;

    /**
     * Get the successful value, throwing if this is a failure.
     *
     * @return TData
     * @throws \LogicException If this is a failure result
     */
    abstract public function unwrap(): mixed;

    /**
     * Get the successful value, or return the default if this is a failure.
     *
     * @template TDefault
     * @param TDefault $default The default value to return on failure
     * @return TData|TDefault
     */
    abstract public function unwrapOr(mixed $default): mixed;

    /**
     * Get the error detail, throwing if this is a success.
     *
     * @return ErrorDetail
     * @throws \LogicException If this is a success result
     */
    abstract public function error(): ErrorDetail;

    /**
     * Map the successful value through a transformer function.
     *
     * @template TNew
     * @param callable(TData): TNew $transformer
     * @return Result<TNew>
     */
    abstract public function map(callable $transformer): Result;

    /**
     * Flat map the successful value through a function that returns a Result.
     *
     * @template TNew
     * @param callable(TData): Result<TNew> $transformer
     * @return Result<TNew>
     */
    abstract public function flatMap(callable $transformer): Result;

    /**
     * Execute a side effect on success and return this result unchanged.
     *
     * @param callable(TData): void $sideEffect
     * @return Result<TData>
     */
    abstract public function onSuccess(callable $sideEffect): Result;

    /**
     * Execute a side effect on failure and return this result unchanged.
     *
     * @param callable(ErrorDetail): void $sideEffect
     * @return Result<TData>
     */
    abstract public function onFailure(callable $sideEffect): Result;

    /**
     * Match on success or failure, returning the appropriate value.
     *
     * @template TOut
     * @param callable(TData): TOut $success Handler for success case
     * @param callable(ErrorDetail): TOut $failure Handler for failure case
     * @return TOut
     */
    abstract public function match(callable $success, callable $failure): mixed;
}

/**
 * Successful result containing a value.
 *
 * @template TData The type of the successful value
 * @extends Result<TData>
 *
 * @package Spiral\Kernel\Domain\Shared\Result
 */
final class Success extends Result
{
    /**
     * @param TData $value
     */
    public function __construct(
        private readonly mixed $value
    ) {
    }

    public function isSuccess(): bool
    {
        return true;
    }

    public function isFailure(): bool
    {
        return false;
    }

    /**
     * @return TData
     */
    public function unwrap(): mixed
    {
        return $this->value;
    }

    /**
     * @return TData
     */
    public function unwrapOr(mixed $default): mixed
    {
        return $this->value;
    }

    public function error(): ErrorDetail
    {
        throw new \LogicException('Cannot get error from a successful result');
    }

    /**
     * @template TNew
     * @param callable(TData): TNew $transformer
     * @return Success<TNew>
     */
    public function map(callable $transformer): Success
    {
        return new Success($transformer($this->value));
    }

    /**
     * @template TNew
     * @param callable(TData): Result<TNew> $transformer
     * @return Result<TNew>
     */
    public function flatMap(callable $transformer): Result
    {
        return $transformer($this->value);
    }

    /**
     * @return Success<TData>
     */
    public function onSuccess(callable $sideEffect): Success
    {
        $sideEffect($this->value);
        return $this;
    }

    /**
     * @return Success<TData>
     */
    public function onFailure(callable $sideEffect): Success
    {
        // Do nothing on success
        return $this;
    }

    /**
     * @template TOut
     * @param callable(TData): TOut $success Handler for success case
     * @param callable(ErrorDetail): TOut $failure Handler for failure case
     * @return TOut
     */
    public function match(callable $success, callable $failure): mixed
    {
        return $success($this->value);
    }
}

/**
 * Failed result containing an error detail.
 *
 * @extends Result<mixed>
 *
 * @package Spiral\Kernel\Domain\Shared\Result
 */
final class Failure extends Result
{
    public function __construct(
        private readonly ErrorDetail $errorDetail
    ) {
    }

    public function isSuccess(): bool
    {
        return false;
    }

    public function isFailure(): bool
    {
        return true;
    }

    public function unwrap(): mixed
    {
        throw new \LogicException(
            'Cannot unwrap a failed result: ' . $this->errorDetail->message()
        );
    }

    public function unwrapOr(mixed $default): mixed
    {
        return $default;
    }

    public function error(): ErrorDetail
    {
        return $this->errorDetail;
    }

    /**
     * @template TNew
     * @return Result<TNew>
     */
    public function map(callable $transformer): Result
    {
        // Transform on failure is a no-op - return same failure
        /** @var Result<TNew> */
        return $this;
    }

    /**
     * @template TNew
     * @return Result<TNew>
     */
    public function flatMap(callable $transformer): Result
    {
        // FlatMap on failure is a no-op - return same failure
        /** @var Result<TNew> */
        return $this;
    }

    public function onSuccess(callable $sideEffect): Result
    {
        // Do nothing on failure
        return $this;
    }

    public function onFailure(callable $sideEffect): Result
    {
        $sideEffect($this->errorDetail);
        return $this;
    }

    public function match(callable $success, callable $failure): mixed
    {
        return $failure($this->errorDetail);
    }
}