<?php

declare(strict_types=1);

namespace Spiral\Kernel\Support\Exception;

/**
 * Exception thrown when input validation fails.
 *
 * This exception is thrown when command/query input fails validation rules.
 * It carries structured validation errors that can be transformed into
 * user-facing error messages.
 *
 * Validation failures are NOT programming errors - they represent invalid
 * user input that must be corrected before the operation can proceed.
 *
 * @package Spiral\Kernel\Support\Exception
 */
final class ValidationException extends DomainException
{
    /**
     * @param array<string, array<int, string>> $errors Map of field name to list of error messages
     */
    public function __construct(
        private readonly array $errors,
        string $message = 'Validation failed',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getErrorCode(): string
    {
        return 'VALIDATION_FAILED';
    }

    public function getContext(): array
    {
        return [
            'errors' => $this->errors,
            'fieldCount' => count($this->errors),
        ];
    }

    /**
     * Check if a specific field has validation errors.
     */
    public function hasFieldError(string $field): bool
    {
        return isset($this->errors[$field]) && count($this->errors[$field]) > 0;
    }

    /**
     * Get all error messages for a specific field.
     *
     * @return array<int, string>
     */
    public function getFieldErrors(string $field): array
    {
        return $this->errors[$field] ?? [];
    }
}