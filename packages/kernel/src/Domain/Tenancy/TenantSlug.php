<?php

declare(strict_types=1);

namespace Spiral\Kernel\Domain\Tenancy;

use Spiral\Kernel\Domain\Shared\ValueObject\ValueObject;

/**
 * Value object representing a tenant slug.
 *
 * TenantSlug is a human-readable identifier for tenants used in:
 * - URL paths (e.g., /acme-corp/dashboard)
 * - API keys
 * - Subdomain routing (e.g., acme-corp.app.example.com)
 *
 * A tenant has both a TenantId (internal UUID) and a TenantSlug (human-readable).
 * The slug can change over time, but TenantId is immutable.
 *
 * Slug rules:
 * - Lowercase alphanumeric characters and hyphens only
 * - Must start with a letter
 * - 3-63 characters
 * - No consecutive hyphens
 * - Reserved slugs are forbidden (www, api, admin, etc.)
 *
 * @package Spiral\Kernel\Domain\Tenancy
 */
final class TenantSlug extends ValueObject
{
    /** @var array<int, non-empty-string> Reserved slugs that cannot be used */
    private const RESERVED_SLUGS = [
        'www', 'api', 'admin', 'app', 'mail', 'ftp', 'smtp', 'pop', 'imap',
        'secure', 'login', 'logout', 'register', 'signup', 'signin', 'signout',
        'account', 'dashboard', 'settings', 'config', 'system', 'internal',
        'test', 'staging', 'production', 'localhost', 'example', 'demo',
    ];

    /** @var non-empty-string */
    private readonly string $slug;

    private function __construct(string $slug)
    {
        if ($slug === '') {
            throw new \InvalidArgumentException('TenantSlug cannot be empty');
        }
        /** @var non-empty-string $slug */
        $this->slug = $slug;
    }

    /**
     * Create a tenant slug from a string.
     *
     * @param string $slug The slug to validate and create
     * @throws \InvalidArgumentException If the slug is invalid
     */
    public static function fromString(string $slug): self
    {
        if ($slug === '') {
            throw new \InvalidArgumentException('TenantSlug cannot be empty');
        }

        if (strlen($slug) < 3) {
            throw new \InvalidArgumentException(
                'TenantSlug must be at least 3 characters'
            );
        }

        if (strlen($slug) > 63) {
            throw new \InvalidArgumentException(
                'TenantSlug must be at most 63 characters'
            );
        }

        // Must start with a letter
        if (!preg_match('/^[a-z]/', $slug)) {
            throw new \InvalidArgumentException(
                'TenantSlug must start with a lowercase letter'
            );
        }

        // Must end with a letter or number
        if (!preg_match('/[a-z0-9]$/', $slug)) {
            throw new \InvalidArgumentException(
                'TenantSlug must end with a lowercase letter or number'
            );
        }

        // Only lowercase letters, numbers, and hyphens
        if (!preg_match('/^[a-z][a-z0-9\-]*[a-z0-9]$/', $slug)) {
            throw new \InvalidArgumentException(
                'TenantSlug can only contain lowercase letters, numbers, and hyphens'
            );
        }

        // No consecutive hyphens
        if (str_contains($slug, '--')) {
            throw new \InvalidArgumentException(
                'TenantSlug cannot contain consecutive hyphens'
            );
        }

        // No reserved slugs
        if (in_array($slug, self::RESERVED_SLUGS, true)) {
            throw new \InvalidArgumentException(
                sprintf('TenantSlug "%s" is reserved and cannot be used', $slug)
            );
        }

        return new self($slug);
    }

    /**
     * Attempt to create a tenant slug, returning null on failure.
     *
     * @param string $slug
     * @return self|null
     */
    public static function tryFromString(string $slug): ?self
    {
        try {
            return self::fromString($slug);
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    /**
     * Get the slug string.
     *
     * @return non-empty-string
     */
    public function toString(): string
    {
        return $this->slug;
    }

    /**
     * Check if a slug is reserved.
     */
    public static function isReserved(string $slug): bool
    {
        return in_array($slug, self::RESERVED_SLUGS, true);
    }

    /**
     * Get all reserved slugs.
     *
     * @return array<int, non-empty-string>
     */
    public static function getReservedSlugs(): array
    {
        return self::RESERVED_SLUGS;
    }

    protected function valueEquals(ValueObject $other): bool
    {
        \assert($other instanceof self);
        return $this->slug === $other->slug;
    }

    public function __toString(): string
    {
        return $this->slug;
    }
}