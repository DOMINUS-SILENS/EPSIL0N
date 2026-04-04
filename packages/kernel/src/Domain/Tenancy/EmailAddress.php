<?php

declare(strict_types=1);

namespace Spiral\Kernel\Domain\Tenancy;

use Spiral\Kernel\Domain\Shared\ValueObject\ValueObject;

/**
 * Value object representing an email address.
 *
 * EmailAddress encapsulates email validation and normalization.
 * All emails are stored and compared in normalized form:
 * - Lowercase domain part
 * - Trimmed whitespace
 * - Validated against RFC 5322 (simplified)
 *
 * This is a governance value object used for:
 * - User accounts
 * - Contact information
 * - Notification targets
 * - Audit trail attribution
 *
 * @package Spiral\Kernel\Domain\Tenancy
 */
final class EmailAddress extends ValueObject
{
    /** @var non-empty-string */
    private readonly string $email;

    /** @var non-empty-string */
    private readonly string $localPart;

    /** @var non-empty-string */
    private readonly string $domain;

    private function __construct(string $email, string $localPart, string $domain)
    {
        if ($email === '' || $localPart === '' || $domain === '') {
            throw new \InvalidArgumentException('EmailAddress components cannot be empty');
        }
        /** @var non-empty-string $email */
        /** @var non-empty-string $localPart */
        /** @var non-empty-string $domain */
        $this->email = $email;
        $this->localPart = $localPart;
        $this->domain = $domain;
    }

    /**
     * Create an email address from a string.
     *
     * @param string $email The email address to validate
     * @throws \InvalidArgumentException If the email is invalid
     */
    public static function fromString(string $email): self
    {
        if ($email === '') {
            throw new \InvalidArgumentException('EmailAddress cannot be empty');
        }

        // Normalize: trim and lowercase domain
        $email = trim($email);

        // Split and validate
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            throw new \InvalidArgumentException(
                sprintf('Invalid email format: "%s"', $email)
            );
        }

        [$localPart, $domain] = $parts;

        if ($localPart === '') {
            throw new \InvalidArgumentException('Email local part cannot be empty');
        }

        if ($domain === '') {
            throw new \InvalidArgumentException('Email domain cannot be empty');
        }

        // Validate local part (simplified)
        if (!self::isValidLocalPart($localPart)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid email local part: "%s"', $localPart)
            );
        }

        // Validate domain
        if (!self::isValidDomain($domain)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid email domain: "%s"', $domain)
            );
        }

        // Normalize domain to lowercase
        $domain = strtolower($domain);
        $normalized = $localPart . '@' . $domain;

        return new self($normalized, $localPart, $domain);
    }

    /**
     * Attempt to create an email address, returning null on failure.
     */
    public static function tryFromString(string $email): ?self
    {
        try {
            return self::fromString($email);
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    /**
     * Get the full email address.
     *
     * @return non-empty-string
     */
    public function toString(): string
    {
        return $this->email;
    }

    /**
     * Get the local part (before @).
     *
     * @return non-empty-string
     */
    public function localPart(): string
    {
        return $this->localPart;
    }

    /**
     * Get the domain part (after @).
     *
     * @return non-empty-string
     */
    public function domain(): string
    {
        return $this->domain;
    }

    /**
     * Check if this email matches a domain pattern.
     *
     * @param non-empty-string $domainPattern Domain or wildcard pattern (e.g., "*.example.com")
     */
    public function matchesDomain(string $domainPattern): bool
    {
        if (str_starts_with($domainPattern, '*.')) {
            $baseDomain = substr($domainPattern, 2);
            return $baseDomain !== false && str_ends_with($this->domain, $baseDomain);
        }

        return $this->domain === strtolower($domainPattern);
    }

    /**
     * Validate email local part (simplified RFC 5322).
     */
    private static function isValidLocalPart(string $localPart): bool
    {
        // Simplified: alphanumeric, dots, hyphens, underscores, plus signs
        // Does not handle quoted strings or all special characters
        $pattern = '/^[a-zA-Z0-9][a-zA-Z0-9._+\-]*[a-zA-Z0-9]$|^[a-zA-Z0-9]$/';
        return preg_match($pattern, $localPart) === 1;
    }

    /**
     * Validate email domain.
     */
    private static function isValidDomain(string $domain): bool
    {
        // Domain must have at least one dot and valid characters
        if (!str_contains($domain, '.')) {
            return false;
        }

        // Each label must be alphanumeric with hyphens
        $labels = explode('.', $domain);
        foreach ($labels as $label) {
            if ($label === '' || strlen($label) > 63) {
                return false;
            }
            if (!preg_match('/^[a-zA-Z0-9]([a-zA-Z0-9\-]*[a-zA-Z0-9])?$|^[a-zA-Z0-9]$/', $label)) {
                return false;
            }
        }

        return true;
    }

    protected function valueEquals(ValueObject $other): bool
    {
        \assert($other instanceof self);
        return $this->email === $other->email;
    }

    public function __toString(): string
    {
        return $this->email;
    }
}