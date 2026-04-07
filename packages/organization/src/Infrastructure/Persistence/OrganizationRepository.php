<?php

declare(strict_types=1);

namespace Spiral\Organization\Infrastructure\Persistence;

use Spiral\Kernel\Domain\Identity\TenantId;
use Spiral\Kernel\Domain\Shared\Error\ErrorCode;
use Spiral\Kernel\Domain\Shared\Error\ErrorDetail;
use Spiral\Kernel\Domain\Shared\Event\ExpectedVersion;
use Spiral\Kernel\Domain\Shared\Result\Result;
use Spiral\Kernel\Infrastructure\Contract\EventStore\IEventStore;
use Spiral\Organization\Domain\Aggregate\Organization;
use Spiral\Organization\Domain\Repository\IOrganizationRepository;
use Spiral\Organization\Domain\ValueObject\OrganizationId;

/**
 * Organization repository implementation using the kernel's event store.
 *
 * This repository demonstrates clean consumption of kernel primitives:
 * - Uses IEventStore for persistence
 * - Uses EventHydrator for event reconstitution
 * - Implements tenant-scoped stream naming
 * - Handles optimistic concurrency via ExpectedVersion
 * - Returns Result monad for explicit error handling
 */
final class OrganizationRepository implements IOrganizationRepository
{
    public function __construct(
        private readonly IEventStore $eventStore,
        private readonly IOrganizationEventHydrator $eventHydrator
    ) {
    }

    /**
     * Build the stream ID for an organization.
     *
     * Format: Organization:{organizationId}
     */
    private function buildStreamId(OrganizationId $organizationId): string
    {
        return sprintf('Organization:%s', $organizationId->toString());
    }

    public function load(TenantId $tenantId, OrganizationId $organizationId): Result
    {
        $streamId = $this->buildStreamId($organizationId);

        // Check if stream exists
        if (!$this->eventStore->streamExists($tenantId, $streamId)) {
            return Result::failure(
                ErrorDetail::withContextData(
                    code: ErrorCode::fromString(ErrorCode::NOT_FOUND),
                    message: sprintf(
                        'Organization not found: %s for tenant: %s',
                        $organizationId->toString(),
                        $tenantId->toString()
                    ),
                    contextData: [
                        'organizationId' => $organizationId->toString(),
                        'tenantId' => $tenantId->toString(),
                    ]
                )
            );
        }

        // Load all events from the stream
        $storedEvents = $this->eventStore->load(
            tenantId: $tenantId,
            streamId: $streamId,
            fromVersion: 0
        );

        // Hydrate stored events to domain events
        $domainEvents = $this->eventHydrator->hydrateAll($storedEvents);

        // Reconstitute the aggregate
        $organization = new Organization($organizationId->toString(), $tenantId);
        $organization->reconstituteFromEvents(
            $domainEvents,
            \count($domainEvents)
        );

        return Result::success($organization);
    }

    public function save(Organization $organization): Result
    {
        $organizationId = OrganizationId::fromString($organization->getId());
        $streamId = $this->buildStreamId($organizationId);
        $tenantId = $organization->getTenantId();

        // Get uncommitted events
        $uncommittedEvents = $organization->popUncommittedEvents();

        if (\count($uncommittedEvents) === 0) {
            // Nothing to save
            return Result::success($organization);
        }

        // Determine expected version for optimistic concurrency
        $expectedVersion = $organization->getStreamVersion() === -1
            ? ExpectedVersion::noStream()
            : ExpectedVersion::exact($organization->getStreamVersion());

        try {
            // Append events to the stream
            $newStreamVersion = $this->eventStore->append(
                tenantId: $tenantId,
                streamId: $streamId,
                expectedVersion: $expectedVersion,
                events: $uncommittedEvents
            );

            // Mark aggregate as committed
            $organization->markCommitted($newStreamVersion);

            return Result::success($organization);
        } catch (\Spiral\Kernel\Support\Exception\ConcurrencyConflictException $e) {
            return Result::failure(
                ErrorDetail::withContextData(
                    code: ErrorCode::kernel('CONCURRENCY_CONFLICT'),
                    message: 'Organization was modified by another process. Please retry.',
                    contextData: [
                        'organizationId' => $organizationId->toString(),
                        'expectedVersion' => $organization->getStreamVersion(),
                    ]
                )
            );
        } catch (\Spiral\Kernel\Support\Exception\DomainException $e) {
            return Result::failure(
                ErrorDetail::withContextData(
                    code: ErrorCode::kernel('PERSISTENCE_FAILED'),
                    message: 'Failed to save organization: ' . $e->getMessage(),
                    contextData: [
                        'organizationId' => $organizationId->toString(),
                    ]
                )
            );
        }
    }

    public function exists(TenantId $tenantId, OrganizationId $organizationId): bool
    {
        $streamId = $this->buildStreamId($organizationId);
        return $this->eventStore->streamExists($tenantId, $streamId);
    }

    public function loadAll(TenantId $tenantId): Result
    {
        // This would typically scan for all Organization:* streams for the tenant
        // For now, return empty list - this requires event store to support stream listing
        return Result::success([]);
    }
}
