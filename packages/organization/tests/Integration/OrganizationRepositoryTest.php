<?php

declare(strict_types=1);

namespace Spiral\Organization\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Spiral\Kernel\Domain\Identity\CausationId;
use Spiral\Kernel\Domain\Identity\CorrelationId;
use Spiral\Kernel\Domain\Identity\TenantId;
use Spiral\Kernel\Domain\Shared\Event\ExpectedVersion;
use Spiral\Kernel\Domain\Shared\ValueObject\Temporal\TimezoneId;
use Spiral\Kernel\Domain\Tenancy\EmailAddress;
use Spiral\Kernel\Domain\Tenancy\TenantSlug;
use Spiral\Kernel\Infrastructure\Contract\EventStore\IEventStore;
use Spiral\Organization\Domain\Aggregate\Organization;
use Spiral\Organization\Domain\Repository\IOrganizationRepository;
use Spiral\Organization\Domain\ValueObject\OrganizationId;
use Spiral\Organization\Infrastructure\Persistence\IOrganizationEventHydrator;
use Spiral\Organization\Infrastructure\Persistence\OrganizationEventHydrator;
use Spiral\Organization\Infrastructure\Persistence\OrganizationRepository;

/**
 * Integration tests for OrganizationRepository.
 *
 * Tests repository behavior with an in-memory event store.
 */
final class OrganizationRepositoryTest extends TestCase
{
    private IEventStore $eventStore;
    private IOrganizationEventHydrator $eventHydrator;
    private IOrganizationRepository $repository;
    private TenantId $tenantId;
    private CorrelationId $correlationId;
    private CausationId $causationId;

    protected function setUp(): void
    {
        $this->eventStore = new InMemoryEventStore();
        $this->eventHydrator = new OrganizationEventHydrator();
        $this->repository = new OrganizationRepository($this->eventStore, $this->eventHydrator);
        $this->tenantId = TenantId::generate();
        $this->correlationId = CorrelationId::generate();
        $this->causationId = CausationId::generate();
    }

    public function test_save_new_organization_persists_to_event_store(): void
    {
        $organization = $this->createOrganization();

        $result = $this->repository->save($organization);

        self::assertTrue($result->isSuccess());
        self::assertSame(1, $organization->getStreamVersion());
    }

    public function test_load_returns_organization_with_correct_state(): void
    {
        $organizationId = OrganizationId::generate();
        $organization = Organization::register(
            tenantId: $this->tenantId,
            correlationId: $this->correlationId,
            causationId: $this->causationId,
            organizationId: $organizationId,
            name: 'Acme Corp',
            slug: TenantSlug::fromString('acme'),
            contactEmail: EmailAddress::fromString('a@b.com'),
            timezone: TimezoneId::utc()
        );

        $this->repository->save($organization);

        // Load the organization
        $loadResult = $this->repository->load($this->tenantId, $organizationId);

        self::assertTrue($loadResult->isSuccess());
        $loaded = $loadResult->unwrap();
        self::assertEquals('Acme Corp', $loaded->getName());
        self::assertEquals($organizationId, $loaded->getOrganizationId());
        self::assertTrue($loaded->isActive());
    }

    public function test_load_returns_failure_for_nonexistent_organization(): void
    {
        $nonExistentId = OrganizationId::generate();

        $result = $this->repository->load($this->tenantId, $nonExistentId);

        self::assertTrue($result->isFailure());
    }

    public function test_exists_returns_true_for_existing_organization(): void
    {
        $organization = $this->createOrganization();
        $this->repository->save($organization);

        $exists = $this->repository->exists($this->tenantId, $organization->getOrganizationId());

        self::assertTrue($exists);
    }

    public function test_exists_returns_false_for_nonexistent_organization(): void
    {
        $nonExistentId = OrganizationId::generate();

        $exists = $this->repository->exists($this->tenantId, $nonExistentId);

        self::assertFalse($exists);
    }

    public function test_concurrent_modification_detected(): void
    {
        // Create and save initial organization
        $organizationId = OrganizationId::generate();
        $organization = Organization::register(
            tenantId: $this->tenantId,
            correlationId: $this->correlationId,
            causationId: $this->causationId,
            organizationId: $organizationId,
            name: 'Original Name',
            slug: TenantSlug::fromString('original'),
            contactEmail: EmailAddress::fromString('original@example.com'),
            timezone: TimezoneId::utc()
        );

        $this->repository->save($organization);

        // Simulate concurrent modification:
        // Process A loads organization
        $loadResultA = $this->repository->load($this->tenantId, $organizationId);
        self::assertTrue($loadResultA->isSuccess());
        $instanceA = $loadResultA->unwrap();

        // Process B loads the same organization (at same version)
        $loadResultB = $this->repository->load($this->tenantId, $organizationId);
        self::assertTrue($loadResultB->isSuccess());
        $instanceB = $loadResultB->unwrap();

        // Process A makes changes and saves successfully
        $instanceA->rename($this->correlationId, $this->causationId, 'Name from A');
        $resultA = $this->repository->save($instanceA);
        self::assertTrue($resultA->isSuccess());

        // Process B (stale) tries to save - should fail with concurrency conflict
        $instanceB->rename($this->correlationId, $this->causationId, 'Name from B');
        $resultB = $this->repository->save($instanceB);

        self::assertTrue($resultB->isFailure());
    }

    public function test_cross_tenant_access_isolated(): void
    {
        $organization = $this->createOrganization();
        $this->repository->save($organization);

        $otherTenant = TenantId::generate();

        $result = $this->repository->load($otherTenant, $organization->getOrganizationId());

        self::assertTrue($result->isFailure());
    }

    public function test_round_trip_preserves_all_events(): void
    {
        $organizationId = OrganizationId::generate();
        $organization = Organization::register(
            tenantId: $this->tenantId,
            correlationId: $this->correlationId,
            causationId: $this->causationId,
            organizationId: $organizationId,
            name: 'Original',
            slug: TenantSlug::fromString('original'),
            contactEmail: EmailAddress::fromString('original@example.com'),
            timezone: TimezoneId::fromString('America/New_York')
        );

        $this->repository->save($organization);

        // Load and make changes
        $loadResult = $this->repository->load($this->tenantId, $organizationId);
        $loaded = $loadResult->unwrap();
        $loaded->rename($this->correlationId, $this->causationId, 'Renamed');
        $loaded->changeTimezone($this->correlationId, $this->causationId, TimezoneId::fromString('Europe/London'));
        $loaded->deactivate($this->correlationId, $this->causationId, 'Test');
        $this->repository->save($loaded);

        // Load again and verify all changes
        $finalLoad = $this->repository->load($this->tenantId, $organizationId);
        $final = $finalLoad->unwrap();

        self::assertEquals('Renamed', $final->getName());
        self::assertEquals('Europe/London', (string) $final->getTimezone());
        self::assertFalse($final->isActive());
    }

    private function createOrganization(): Organization
    {
        return Organization::register(
            tenantId: $this->tenantId,
            correlationId: $this->correlationId,
            causationId: $this->causationId,
            organizationId: OrganizationId::generate(),
            name: 'Test Organization',
            slug: TenantSlug::fromString('test-org'),
            contactEmail: EmailAddress::fromString('test@example.com'),
            timezone: TimezoneId::utc()
        );
    }
}
