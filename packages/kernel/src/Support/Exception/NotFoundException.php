<?php

declare(strict_types=1);

namespace Spiral\Kernel\Support\Exception;

/**
 * Exception thrown when a requested resource cannot be found.
 *
 * This exception is thrown when attempting to load an aggregate, entity,
 * or other resource that does not exist. It's a common failure mode
 * that should be handled gracefully by the application layer.
 *
 * NotFoundException should be thrown by repositories when the requested
 * resource does not exist, NOT when there's a database error (that would
 * be a KernelException subclass).
 *
 * @package Spiral\Kernel\Support\Exception
 */
final class NotFoundException extends DomainException
{
    /**
     * @param string $resourceType The type of resource that was not found
     * @param string $resourceId The ID of the resource that was not found
     */
    public function __construct(
        private readonly string $resourceType,
        private readonly string $resourceId,
        ?\Throwable $previous = null
    ) {
        $message = sprintf(
            '%s with ID "%s" not found',
            $resourceType,
            $resourceId
        );
        parent::__construct($message, 0, $previous);
    }

    public function getErrorCode(): string
    {
        return 'RESOURCE_NOT_FOUND';
    }

    public function getContext(): array
    {
        return [
            'resourceType' => $this->resourceType,
            'resourceId' => $this->resourceId,
        ];
    }

    public function getResourceType(): string
    {
        return $this->resourceType;
    }

    public function getResourceId(): string
    {
        return $this->resourceId;
    }
}