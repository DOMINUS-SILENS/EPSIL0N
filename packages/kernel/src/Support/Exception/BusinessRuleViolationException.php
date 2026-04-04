<?php

declare(strict_types=1);

namespace Spiral\Kernel\Support\Exception;

/**
 * Exception thrown when a business rule is violated.
 *
 * This exception represents a domain invariant violation - a rule that
 * MUST always hold true within the business context. Unlike validation
 * errors (which are about input correctness), business rule violations
 * indicate that the operation would result in an invalid business state.
 *
 * Examples:
 * - Cannot delete a customer with outstanding orders
 * - Cannot approve an already approved document
 * - Cannot exceed credit limit
 * - Cannot ship to blocked region
 *
 * Business rule violations should be caught by the application layer and
 * converted to appropriate user-facing error messages.
 *
 * @package Spiral\Kernel\Support\Exception
 */
final class BusinessRuleViolationException extends DomainException
{
    /**
     * @param string $ruleName The name/identifier of the violated rule
     * @param string $message Human-readable description of the violation
     * @param array<string, mixed> $context Additional context about the violation
     */
    public function __construct(
        private readonly string $ruleName,
        string $message,
        private readonly array $context = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getErrorCode(): string
    {
        return 'BUSINESS_RULE_VIOLATION';
    }

    public function getContext(): array
    {
        return array_merge($this->context, [
            'ruleName' => $this->ruleName,
        ]);
    }

    public function getRuleName(): string
    {
        return $this->ruleName;
    }
}