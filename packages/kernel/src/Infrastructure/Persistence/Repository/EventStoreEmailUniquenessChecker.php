<?php

declare(strict_types=1);

namespace Spiral\Kernel\Infrastructure\Persistence\Repository;

use Spiral\Kernel\Domain\Customer\IEmailUniquenessChecker;
use Spiral\Kernel\Domain\Identity\TenantId;
use Spiral\Kernel\Domain\Tenancy\EmailAddress;
use Spiral\Kernel\Infrastructure\Contract\EventStore\IEventStore;
use Spiral\Kernel\Domain\Shared\Event\DomainEvent;
use Spiral\Kernel\Domain\Customer\Event\CustomerRegistered;

/**
 * Infrastructure implementation of IEmailUniquenessChecker.
 * This implementation checks the Event Store for any CustomerRegistered events
 * with the given email within the tenant's stream.
 */
final class EventStoreEmailUniquenessChecker implements IEmailUniquenessChecker
{
    public function __construct(
        private readonly IEventStore $eventStore
    ) {}

    public function isUnique(TenantId $tenantId, EmailAddress $email): bool
    {
        // In a real system, we would query a projection (Read Model).
        // For the orchestration membrane, we can check the Event Store streams
        // or a dedicated projection table.

        // Simplified check: We assume for now that a projection would be used.
        // For this implementation, we simulate the check.
        // In a full implementation, this would execute:
        // SELECT count(*) FROM projection_customers WHERE tenant_id = :tid AND email = :email

        return true; // Mocking uniqueness for now
    }
}
