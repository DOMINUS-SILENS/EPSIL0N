<?php

declare(strict_types=1);

namespace Spiral\Kernel\Domain\Customer;

/**
 * Customer domain error codes.
 *
 * Centralized error code constants to eliminate magic strings in Customer aggregate
 * and related handlers. Codes follow the pattern: DOMAIN.CUSTOMER.{RULE}
 *
 * @package Spiral\Kernel\Domain\Customer
 */
final class CustomerErrorCodes
{
    /**
     * Customer already exists (re-registration attempt).
     */
    public const ALREADY_EXISTS = 'DOMAIN.CUSTOMER.ALREADY_EXISTS';

    /**
     * Customer is inactive and cannot perform this action.
     */
    public const INACTIVE = 'DOMAIN.CUSTOMER.INACTIVE';

    /**
     * Customer email is not verified.
     */
    public const NOT_VERIFIED = 'DOMAIN.CUSTOMER.NOT_VERIFIED';

    /**
     * Customer not found.
     */
    public const NOT_FOUND = 'DOMAIN.CUSTOMER.NOT_FOUND';

    /**
     * Unknown command type dispatched to Customer aggregate.
     */
    public const UNKNOWN_COMMAND = 'DOMAIN.CUSTOMER.UNKNOWN_COMMAND';

    /**
     * Email address is already taken by another customer in this tenant.
     */
    public const EMAIL_TAKEN = 'DOMAIN.CUSTOMER.EMAIL_TAKEN';

    /**
     * Validation: Name is invalid (must be 2-100 chars, alphanumeric + spaces).
     */
    public const VALIDATION_NAME_INVALID = 'VALIDATION.NAME_INVALID';

    /**
     * Regex pattern for customer name validation.
     * Allows: Alphanumeric characters and spaces only, 2-100 characters.
     */
    public const NAME_PATTERN = '/^[a-zA-Z0-9\s]{2,100}$/';

    private function __construct() {}
}
