<?php

declare(strict_types=1);

namespace Spiral\Kernel\Domain\Tenancy;

use Spiral\Kernel\Domain\Shared\ValueObject\ValueObject;

/**
 * Value object representing a reference to a domain resource.
 *
 * ResourceReference provides a standardized way to reference any domain
 * entity or aggregate across the system. It captures:
 * - The resource type (e.g., "Customer", "Order", "Product")
 * - The resource identifier
 * - The tenant context (optional, for cross-tenant references)
 *
 * Use cases:
 * - Audit trail references
 * - Document line item references
 * - Relationship modeling
 * - Event metadata for affected resources
 *
 * ResourceReference is serializable and can be stored in events,
 * audit logs, and other persistence contexts.
 *
 * @package Spiral\Kernel\Domain\Tenancy
 */
final class ResourceReference extends ValueObject
{
    /**
     * @param non-empty-string $resourceType The type of resource (e.g., "Customer", "Order")
     * @param non-empty-string $resourceId The unique identifier of the resource
     * @param non-empty-string|null $tenantId The tenant ID if this is a cross-tenant reference
     */
    private function __construct(
        private readonly string $resourceType,
        private readonly string $resourceId,
        private readonly ?string $tenantId = null
    ) {
    }

    /**
     * Create a resource reference.
     *
     * @param string $resourceType The type of resource
     * @param string $resourceId The resource identifier
     */
    public static function create(string $resourceType, string $resourceId): self
    {
        if ($resourceType === '') {
            throw new \InvalidArgumentException('Resource type cannot be empty');
        }

        if ($resourceId === '') {
            throw new \InvalidArgumentException('Resource ID cannot be empty');
        }

        return new self($resourceType, $resourceId);
    }

    /**
     * Create a cross-tenant resource reference.
     *
     * @param string $resourceType The type of resource
     * @param string $resourceId The resource identifier
     * @param string $tenantId The tenant ID of the resource
     */
    public static function crossTenant(string $resourceType, string $resourceId, string $tenantId): self
    {
        if ($resourceType === '') {
            throw new \InvalidArgumentException('Resource type cannot be empty');
        }

        if ($resourceId === '') {
            throw new \InvalidArgumentException('Resource ID cannot be empty');
        }

        if ($tenantId === '') {
            throw new \InvalidArgumentException('Tenant ID cannot be empty for cross-tenant reference');
        }

        return new self($resourceType, $resourceId, $tenantId);
    }

    /**
     * Parse a resource reference from string format.
     *
     * Format: "Type:Id" or "Type:Id@TenantId"
     *
     * @param string $reference The reference string
     */
    public static function fromString(string $reference): self
    {
        if ($reference === '') {
            throw new \InvalidArgumentException('Resource reference cannot be empty');
        }

        // Check for tenant suffix
        $atPosition = strrpos($reference, '@');
        $tenantId = null;
        $mainPart = $reference;

        if ($atPosition !== false) {
            $tenantId = substr($reference, $atPosition + 1);
            $mainPart = substr($reference, 0, $atPosition);

            if (!is_string($tenantId) || $tenantId === '') {
                throw new \InvalidArgumentException('Tenant ID cannot be empty in cross-tenant reference');
            }
        }

        // Parse type:id
        $colonPosition = strpos($mainPart, ':');

        if ($colonPosition === false || $colonPosition === 0) {
            throw new \InvalidArgumentException(
                sprintf('Invalid resource reference format: "%s"', $reference)
            );
        }

        $resourceType = substr($mainPart, 0, $colonPosition);
        $resourceId = substr($mainPart, $colonPosition + 1);

        if (!is_string($resourceType) || $resourceType === '') {
            throw new \InvalidArgumentException('Resource type cannot be empty');
        }

        if (!is_string($resourceId) || $resourceId === '') {
            throw new \InvalidArgumentException('Resource ID cannot be empty');
        }

        return new self($resourceType, $resourceId, $tenantId);
    }

    /**
     * Get the resource type.
     *
     * @return non-empty-string
     */
    public function resourceType(): string
    {
        return $this->resourceType;
    }

    /**
     * Get the resource identifier.
     *
     * @return non-empty-string
     */
    public function resourceId(): string
    {
        return $this->resourceId;
    }

    /**
     * Get the tenant ID, if this is a cross-tenant reference.
     */
    public function tenantId(): ?string
    {
        return $this->tenantId;
    }

    /**
     * Check if this is a cross-tenant reference.
     */
    public function isCrossTenant(): bool
    {
        return $this->tenantId !== null;
    }

    /**
     * Convert to string format for serialization.
     *
     * Format: "Type:Id" or "Type:Id@TenantId"
     *
     * @return non-empty-string
     */
    public function toString(): string
    {
        $reference = $this->resourceType . ':' . $this->resourceId;

        if ($this->tenantId !== null) {
            $reference .= '@' . $this->tenantId;
        }

        return $reference;
    }

    /**
     * Convert to array for serialization.
     *
     * @return array<string, string>
     */
    public function toArray(): array
    {
        $result = [
            'resourceType' => $this->resourceType,
            'resourceId' => $this->resourceId,
        ];

        if ($this->tenantId !== null) {
            $result['tenantId'] = $this->tenantId;
        }

        return $result;
    }

    protected function valueEquals(ValueObject $other): bool
    {
        \assert($other instanceof self);
        return $this->resourceType === $other->resourceType
            && $this->resourceId === $other->resourceId
            && $this->tenantId === $other->tenantId;
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}