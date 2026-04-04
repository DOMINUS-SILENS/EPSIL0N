<?php

declare(strict_types=1);

namespace Spiral\Kernel\Domain\Shared\Error;

use function assert;

/**
 * Immutable value object representing a structured error code.
 *
 * Error codes provide machine-readable identifiers for failure conditions.
 * They enable consistent error handling, logging, and client responses
 * across the entire system.
 *
 * Error codes follow a hierarchical naming convention:
 * - KERNEL_* for infrastructure/structural failures
 * - DOMAIN_* for business rule violations
 * - VALIDATION_* for input validation failures
 * - AUTH_* for authentication/authorization failures
 *
 * Example codes:
 * - KERNEL.CONCURRENCY_CONFLICT
 * - DOMAIN.CUSTOMER.CREDIT_LIMIT_EXCEEDED
 * - VALIDATION.FIELD.REQUIRED
 * - AUTH.TENANT.ACCESS_DENIED
 *
 * @package Spiral\Kernel\Domain\Shared\Error
 */
final class ErrorCode
{
    /**
     * Common kernel error codes.
     */
    public const CONCURRENCY_CONFLICT = 'KERNEL.CONCURRENCY_CONFLICT';
    public const NOT_FOUND = 'KERNEL.NOT_FOUND';
    public const INVALID_STATE = 'KERNEL.INVALID_STATE';
    public const OPERATION_FAILED = 'KERNEL.OPERATION_FAILED';

    /**
     * @param non-empty-string $code The error code identifier
     * @param non-empty-string $domain The domain this error belongs to (KERNEL, DOMAIN, VALIDATION, AUTH)
     */
    private function __construct(
        private readonly string $code,
        private readonly string $domain
    ) {
        assert($code !== '');
        assert($domain !== '');
    }

    /**
     * Create an error code from its full string representation.
     *
     * @param non-empty-string $fullCode Full error code (e.g., "DOMAIN.CUSTOMER.CREDIT_LIMIT_EXCEEDED")
     */
    public static function fromString(string $fullCode): self
    {
        $parts = explode('.', $fullCode, 2);
        /** @var non-empty-string $domain */
        $domain = $parts[0];

        return new self($fullCode, $domain);
    }

    /**
     * Create a kernel-level error code.
     *
     * @param non-empty-string $code The kernel-specific error code
     */
    public static function kernel(string $code): self
    {
        return new self('KERNEL.' . $code, 'KERNEL');
    }

    /**
     * Create a domain-level error code.
     *
     * @param non-empty-string $context The domain context (e.g., "CUSTOMER", "ORDER")
     * @param non-empty-string $errorType The specific error type
     */
    public static function domainError(string $context, string $errorType): self
    {
        return new self('DOMAIN.' . $context . '.' . $errorType, 'DOMAIN');
    }

    /**
     * Create a validation error code.
     *
     * @param non-empty-string $code The validation-specific error code
     */
    public static function validation(string $code): self
    {
        return new self('VALIDATION.' . $code, 'VALIDATION');
    }

    /**
     * Create an auth-related error code.
     *
     * @param non-empty-string $code The auth-specific error code
     */
    public static function auth(string $code): self
    {
        return new self('AUTH.' . $code, 'AUTH');
    }

    /**
     * Get the full error code string.
     *
     * @return non-empty-string
     */
    public function code(): string
    {
        return $this->code;
    }

    /**
     * Get the domain this error belongs to.
     *
     * @return non-empty-string
     */
    public function domain(): string
    {
        return $this->domain;
    }

    /**
     * Check if this is a kernel-level error.
     */
    public function isKernelError(): bool
    {
        return $this->domain === 'KERNEL';
    }

    /**
     * Check if this is a domain-level error.
     */
    public function isDomainError(): bool
    {
        return $this->domain === 'DOMAIN';
    }

    /**
     * Check if this is a validation error.
     */
    public function isValidationError(): bool
    {
        return $this->domain === 'VALIDATION';
    }

    /**
     * Check if this is an auth-related error.
     */
    public function isAuthError(): bool
    {
        return $this->domain === 'AUTH';
    }

    public function __toString(): string
    {
        return $this->code;
    }

    public function equals(self $other): bool
    {
        return $this->code === $other->code;
    }
}