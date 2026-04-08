<?php

declare(strict_types=1);

namespace Spiral\Organization\Domain;

/**
 * Organization domain error codes.
 *
 * Centralized error code constants to eliminate magic strings in Organization aggregate
 * and related handlers. Codes follow the pattern: DOMAIN.ORGANIZATION.{RULE}
 *
 * @package Spiral\Organization\Domain
 */
final class OrganizationErrorCodes
{
    /**
     * Organization already exists (re-registration attempt).
     */
    public const ALREADY_EXISTS = 'DOMAIN.ORGANIZATION.ALREADY_EXISTS';

    /**
     * Organization is inactive and cannot perform this action.
     */
    public const INACTIVE = 'DOMAIN.ORGANIZATION.INACTIVE';

    /**
     * Organization not found.
     */
    public const NOT_FOUND = 'DOMAIN.ORGANIZATION.NOT_FOUND';

    /**
     * Unknown command type dispatched to Organization aggregate.
     */
    public const UNKNOWN_COMMAND = 'DOMAIN.ORGANIZATION.UNKNOWN_COMMAND';

    /**
     * Unknown event type encountered during apply.
     */
    public const UNKNOWN_EVENT = 'DOMAIN.ORGANIZATION.UNKNOWN_EVENT';

    /**
     * Validation: Name is empty or invalid.
     */
    public const VALIDATION_NAME_EMPTY = 'VALIDATION.NAME_EMPTY';

    /**
     * Validation: Slug is already taken.
     */
    public const VALIDATION_SLUG_TAKEN = 'VALIDATION.SLUG_TAKEN';

    /**
     * Validation: Email is already taken.
     */
    public const VALIDATION_EMAIL_TAKEN = 'VALIDATION.EMAIL_TAKEN';

    private function __construct() {}
}
