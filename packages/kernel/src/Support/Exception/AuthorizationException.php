<?php

declare(strict_types=1);

namespace Spiral\Kernel\Support\Exception;

/**
 * Exception thrown when authorization check fails.
 *
 * This exception is thrown when an actor attempts to perform an action
 * they are not authorized to perform. Authorization checks happen in the
 * application layer BEFORE command handlers invoke aggregates.
 *
 * This is NOT the same as authentication - authentication determines WHO
 * the actor is, authorization determines WHAT they can do.
 *
 * Authorization failures should be logged for security auditing but
 * should NOT expose internal details about permissions or roles to clients.
 *
 * @package Spiral\Kernel\Support\Exception
 */
final class AuthorizationException extends KernelException
{
    /**
     * @param string $actorId The ID of the actor attempting the action
     * @param string $action The action that was attempted
     * @param string|null $resourceType Optional resource type if applicable
     * @param string|null $resourceId Optional resource ID if applicable
     */
    public function __construct(
        private readonly string $actorId,
        private readonly string $action,
        private readonly ?string $resourceType = null,
        private readonly ?string $resourceId = null,
        ?\Throwable $previous = null
    ) {
        $message = sprintf(
            'Actor %s is not authorized to perform action "%s"',
            $actorId,
            $action
        );

        if ($resourceType !== null) {
            $message .= sprintf(' on %s', $resourceType);
            if ($resourceId !== null) {
                $message .= sprintf('(%s)', $resourceId);
            }
        }

        parent::__construct($message, 0, $previous);
    }

    public function getErrorCode(): string
    {
        return 'AUTHORIZATION_DENIED';
    }

    public function getContext(): array
    {
        return [
            'actorId' => $this->actorId,
            'action' => $this->action,
            'resourceType' => $this->resourceType,
            'resourceId' => $this->resourceId,
        ];
    }

    public function getActorId(): string
    {
        return $this->actorId;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getResourceType(): ?string
    {
        return $this->resourceType;
    }

    public function getResourceId(): ?string
    {
        return $this->resourceId;
    }
}