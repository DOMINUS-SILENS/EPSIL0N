<?php

declare(strict_types=1);

namespace Spiral\Kernel\Domain\Shared\Error;

use function assert;

/**
 * Structured error detail containing comprehensive failure information.
 *
 * ErrorDetail provides a rich, structured representation of errors that
 * can be logged, traced, and transformed into client responses. It combines:
 * - A structured error code for machine processing
 * - A human-readable message
 * - Contextual data for debugging
 * - Field-level details for validation errors
 * - Trace/correlation identifiers for observability
 *
 * This is the standard error payload used within Result types and
 * exception contexts throughout the Kernel.
 *
 * @package Spiral\Kernel\Domain\Shared\Error
 */
final class ErrorDetail
{
    /**
     * @param ErrorCode $code Structured error code
     * @param non-empty-string $message Human-readable error description
     * @param array<string, mixed> $context Additional contextual data
     * @param array<string, array<int, string>> $fieldErrors Field-specific validation errors
     * @param string|null $traceId Request/operation trace identifier
     * @param string|null $correlationId Correlation identifier for distributed tracing
     */
    private function __construct(
        private readonly ErrorCode $code,
        private readonly string $message,
        private readonly array $context = [],
        private readonly array $fieldErrors = [],
        private readonly ?string $traceId = null,
        private readonly ?string $correlationId = null
    ) {
        assert($message !== '');
    }

    /**
     * Create an error detail from an error code and message.
     *
     * @param ErrorCode $code The structured error code
     * @param non-empty-string $message Human-readable description
     */
    public static function create(ErrorCode $code, string $message): self
    {
        return new self($code, $message);
    }

    /**
     * Create an error detail with additional context.
     *
     * @param ErrorCode $code The structured error code
     * @param non-empty-string $message Human-readable description
     * @param array<string, mixed> $contextData Additional contextual data
     */
    public static function withContextData(ErrorCode $code, string $message, array $contextData): self
    {
        return new self($code, $message, $contextData);
    }

    /**
     * Create a validation error with field-specific errors.
     *
     * @param non-empty-string $message Overall validation error message
     * @param array<string, array<int, string>> $fieldErrors Map of field name to error messages
     * @param string|null $traceIdentifier Optional trace identifier
     */
    public static function validationFailed(string $message, array $fieldErrors, ?string $traceIdentifier = null): self
    {
        return new self(
            ErrorCode::validation('FAILED'),
            $message,
            ['fieldCount' => count($fieldErrors)],
            $fieldErrors,
            $traceIdentifier
        );
    }

    /**
     * Add context to this error detail.
     *
     * @param array<string, mixed> $contextData Additional context
     */
    public function withAddedContext(array $contextData): self
    {
        return new self(
            $this->code,
            $this->message,
            array_merge($this->context, $contextData),
            $this->fieldErrors,
            $this->traceId,
            $this->correlationId
        );
    }

    /**
     * Add trace identifiers to this error detail.
     */
    public function withTraceIdentifiers(string $traceIdentifier, ?string $correlationIdentifier = null): self
    {
        return new self(
            $this->code,
            $this->message,
            $this->context,
            $this->fieldErrors,
            $traceIdentifier,
            $correlationIdentifier ?? $this->correlationId
        );
    }

    /**
     * Get the error code.
     */
    public function code(): ErrorCode
    {
        return $this->code;
    }

    /**
     * Get the human-readable message.
     *
     * @return non-empty-string
     */
    public function message(): string
    {
        return $this->message;
    }

    /**
     * Get the contextual data.
     *
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return $this->context;
    }

    /**
     * Get field-specific validation errors, if any.
     *
     * @return array<string, array<int, string>>
     */
    public function fieldErrors(): array
    {
        return $this->fieldErrors;
    }

    /**
     * Check if this error has field-level errors.
     */
    public function hasFieldErrors(): bool
    {
        return count($this->fieldErrors) > 0;
    }

    /**
     * Get the trace identifier.
     */
    public function traceId(): ?string
    {
        return $this->traceId;
    }

    /**
     * Get the correlation identifier.
     */
    public function correlationId(): ?string
    {
        return $this->correlationId;
    }

    /**
     * Convert to an associative array for serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            'code' => $this->code->code(),
            'message' => $this->message,
        ];

        if (!empty($this->context)) {
            $result['context'] = $this->context;
        }

        if (!empty($this->fieldErrors)) {
            $result['fieldErrors'] = $this->fieldErrors;
        }

        if ($this->traceId !== null) {
            $result['traceId'] = $this->traceId;
        }

        if ($this->correlationId !== null) {
            $result['correlationId'] = $this->correlationId;
        }

        return $result;
    }
}