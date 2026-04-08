<?php

declare(strict_types=1);

namespace Spiral\Organization\Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use Spiral\Kernel\Domain\Identity\CausationId;
use Spiral\Kernel\Domain\Identity\CorrelationId;
use Spiral\Kernel\Domain\Identity\TenantId;
use Spiral\Kernel\Domain\Shared\ValueObject\Temporal\TimezoneId;
use Spiral\Kernel\Domain\Tenancy\EmailAddress;
use Spiral\Kernel\Domain\Tenancy\TenantSlug;
use Spiral\Organization\Domain\Aggregate\Organization;
use Spiral\Organization\Domain\ValueObject\OrganizationId;

/**
 * Unit tests for Organization aggregate.
 *
 * Tests aggregate behavior without infrastructure dependencies.
 */
final class OrganizationTest extends TestCase
{
    private TenantId $tenantId;
    private CorrelationId $correlationId;
    private CausationId $causationId;

    protected function setUp(): void
    {
        $this->tenantId = TenantId::generate();
        $this->correlationId = CorrelationId::generate();
        $this->causationId = CausationId::generate();
    }

    public function test_register_creates_organization_with_correct_state(): void
    {
        $organizationId = OrganizationId::generate();
        $name = 'Acme Corporation';
        $slug = TenantSlug::fromString('acme-corp');
        $email = EmailAddress::fromString('contact@acme.com');
        $timezone = TimezoneId::fromString('America/New_York');

        $organization = Organization::register(
            tenantId: $this->tenantId,
            correlationId: $this->correlationId,
            causationId: $this->causationId,
            organizationId: $organizationId,
            name: $name,
            slug: $slug,
            contactEmail: $email,
            timezone: $timezone
        );

        self::assertEquals($organizationId, $organization->getOrganizationId());
        self::assertEquals($name, $organization->getName());
        self::assertEquals($slug, $organization->getSlug());
        self::assertEquals($email, $organization->getContactEmail());
        self::assertEquals($timezone, $organization->getTimezone());
        self::assertTrue($organization->isActive());
        self::assertSame(0, $organization->getStreamVersion()); // Marked as new (not yet persisted)
    }

    public function test_register_raises_organization_registered_event(): void
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

        self::assertTrue($organization->hasUncommittedEvents());
        self::assertSame(1, $organization->getUncommittedEventCount());

        $events = $organization->popUncommittedEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(\Spiral\Organization\Domain\Event\OrganizationRegistered::class, $events[0]);
    }

    public function test_rename_changes_name_and_raises_event(): void
    {
        $organization = $this->createOrganization();
        $organization->popUncommittedEvents(); // Clear registration event

        $newName = 'Acme Industries';
        $organization->rename($this->correlationId, $this->causationId, $newName);

        self::assertEquals($newName, $organization->getName());
        self::assertTrue($organization->hasUncommittedEvents());
    }

    public function test_rename_with_same_name_does_nothing(): void
    {
        $organization = $this->createOrganization();
        $organization->popUncommittedEvents();

        $currentName = $organization->getName();
        $organization->rename($this->correlationId, $this->causationId, $currentName);

        self::assertFalse($organization->hasUncommittedEvents());
    }

    public function test_rename_throws_on_empty_name(): void
    {
        $organization = $this->createOrganization();

        $result = $organization->rename($this->correlationId, $this->causationId, '');

        self::assertFalse($result->isSuccess());
        self::assertSame('VALIDATION.NAME_EMPTY', $result->error()->code()->code());
    }

    public function test_deactivate_marks_inactive_and_raises_event(): void
    {
        $organization = $this->createOrganization();
        $organization->popUncommittedEvents();

        $organization->deactivate($this->correlationId, $this->causationId, 'Billing issue');

        self::assertFalse($organization->isActive());
        self::assertTrue($organization->hasUncommittedEvents());
    }

    public function test_activate_marks_active_and_raises_event(): void
    {
        $organization = $this->createOrganization();
        $organization->deactivate($this->correlationId, $this->causationId);
        $organization->popUncommittedEvents();

        $organization->activate($this->correlationId, $this->causationId);

        self::assertTrue($organization->isActive());
        self::assertTrue($organization->hasUncommittedEvents());
    }

    public function test_activate_on_already_active_does_nothing(): void
    {
        $organization = $this->createOrganization();
        $organization->popUncommittedEvents();

        $organization->activate($this->correlationId, $this->causationId);

        self::assertFalse($organization->hasUncommittedEvents());
    }

    public function test_deactivate_on_already_inactive_does_nothing(): void
    {
        $organization = $this->createOrganization();
        $organization->deactivate($this->correlationId, $this->causationId);
        $organization->popUncommittedEvents();

        $organization->deactivate($this->correlationId, $this->causationId);

        self::assertFalse($organization->hasUncommittedEvents());
    }

    public function test_change_timezone_updates_timezone(): void
    {
        $organization = $this->createOrganization();
        $organization->popUncommittedEvents();

        $newTimezone = TimezoneId::fromString('Europe/London');
        $organization->changeTimezone($this->correlationId, $this->causationId, $newTimezone);

        self::assertEquals($newTimezone, $organization->getTimezone());
        self::assertTrue($organization->hasUncommittedEvents());
    }

    public function test_change_timezone_with_same_timezone_does_nothing(): void
    {
        $organization = $this->createOrganization();
        $organization->popUncommittedEvents();

        $currentTimezone = $organization->getTimezone();
        $organization->changeTimezone($this->correlationId, $this->causationId, $currentTimezone);

        self::assertFalse($organization->hasUncommittedEvents());
    }

    public function test_reconstitute_from_events_restores_state(): void
    {
        $organizationId = OrganizationId::generate();

        // Create and modify organization
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

        $organization->rename($this->correlationId, $this->causationId, 'Acme Industries');
        $organization->deactivate($this->correlationId, $this->causationId);

        // Collect all events at once (popUncommittedEvents returns all)
        $events = $organization->popUncommittedEvents();

        // Reconstitute new aggregate from events
        $reconstituted = new Organization($organizationId->toString(), $this->tenantId);
        $reconstituted->reconstituteFromEvents($events, \count($events));

        // Verify state is restored
        self::assertEquals('Acme Industries', $reconstituted->getName());
        self::assertFalse($reconstituted->isActive());
        self::assertEquals($organizationId, $reconstituted->getOrganizationId());
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
