<?php

declare(strict_types=1);

namespace Spiral\Organization\Domain\Aggregate;

use Spiral\Kernel\Domain\Identity\CausationId;
use Spiral\Kernel\Domain\Identity\CorrelationId;
use Spiral\Kernel\Domain\Identity\TenantId;
use Spiral\Kernel\Domain\Shared\Aggregate\AggregateRoot;
use Spiral\Kernel\Domain\Shared\Event\DomainEvent;
use Spiral\Kernel\Domain\Shared\Error\ErrorCode;
use Spiral\Kernel\Domain\Shared\Error\ErrorDetail;
use Spiral\Kernel\Domain\Shared\Result\Result;
use Spiral\Kernel\Domain\Shared\ValueObject\Temporal\TimezoneId;
use Spiral\Kernel\Domain\Tenancy\EmailAddress;
use Spiral\Kernel\Domain\Tenancy\TenantSlug;
use Spiral\Organization\Domain\Event\OrganizationActivated;
use Spiral\Organization\Domain\Event\OrganizationDeactivated;
use Spiral\Organization\Domain\Event\OrganizationRegistered;
use Spiral\Organization\Domain\Event\OrganizationRenamed;
use Spiral\Organization\Domain\Event\OrganizationSlugChanged;
use Spiral\Organization\Domain\Event\OrganizationTimezoneChanged;
use Spiral\Organization\Domain\OrganizationErrorCodes;
use Spiral\Organization\Domain\ValueObject\OrganizationId;

/**
 * Organization aggregate root.
 *
 * Represents a tenant organization in the ERP system.
 * All state changes are expressed as domain events.
 *
 * Properties:
 * - id: OrganizationId (aggregate identity)
 * - tenantId: TenantId (isolation boundary - each org is its own tenant)
 * - name: Display name
 * - slug: Human-readable URL identifier
 * - contactEmail: Primary contact email
 * - timezone: Organization's primary timezone
 * - status: Active or inactive
 */
final class Organization extends AggregateRoot
{
    private OrganizationId $organizationId;

    private string $name;

    private TenantSlug $slug;

    private EmailAddress $contactEmail;

    private TimezoneId $timezone;

    private bool $active = true;

    /**
     * Factory method to register a new organization.
     *
     * This is the ONLY way to create an Organization aggregate.
     * It raises the OrganizationRegistered event.
     */
    public static function register(
        TenantId $tenantId,
        CorrelationId $correlationId,
        CausationId $causationId,
        OrganizationId $organizationId,
        string $name,
        TenantSlug $slug,
        EmailAddress $contactEmail,
        TimezoneId $timezone
    ): self {
        $organization = new self($organizationId->toString(), $tenantId);
        $organization->organizationId = $organizationId;
        $organization->markAsNew();

        $event = OrganizationRegistered::create(
            tenantId: $tenantId,
            correlationId: $correlationId,
            causationId: $causationId,
            organizationId: $organizationId,
            name: $name,
            slug: $slug,
            contactEmail: $contactEmail,
            timezone: $timezone
        );

        $organization->raise($event);

        return $organization;
    }

    /**
     * Rename the organization.
     *
     * @return Result<null>
     */
    public function rename(
        CorrelationId $correlationId,
        CausationId $causationId,
        string $newName
    ): Result {
        if ($newName === '') {
            return Result::failure(ErrorDetail::withContextData(
                ErrorCode::fromString(OrganizationErrorCodes::VALIDATION_NAME_EMPTY),
                'Organization name cannot be empty',
                ['field' => 'name']
            ));
        }

        if ($newName === $this->name) {
            return Result::success(null); // No change needed
        }

        $event = OrganizationRenamed::create(
            tenantId: $this->getTenantId(),
            correlationId: $correlationId,
            causationId: $causationId,
            organizationId: $this->organizationId,
            oldName: $this->name,
            newName: $newName
        );

        $this->raise($event);

        return Result::success(null);
    }

    /**
     * Change the organization's slug.
     */
    public function changeSlug(
        CorrelationId $correlationId,
        CausationId $causationId,
        TenantSlug $newSlug
    ): void {
        if ($newSlug->equals($this->slug)) {
            return; // No change needed
        }

        $event = OrganizationSlugChanged::create(
            tenantId: $this->getTenantId(),
            correlationId: $correlationId,
            causationId: $causationId,
            organizationId: $this->organizationId,
            oldSlug: $this->slug,
            newSlug: $newSlug
        );

        $this->raise($event);
    }

    /**
     * Change the organization's timezone.
     */
    public function changeTimezone(
        CorrelationId $correlationId,
        CausationId $causationId,
        TimezoneId $newTimezone
    ): void {
        if ($newTimezone->equals($this->timezone)) {
            return; // No change needed
        }

        $event = OrganizationTimezoneChanged::create(
            tenantId: $this->getTenantId(),
            correlationId: $correlationId,
            causationId: $causationId,
            organizationId: $this->organizationId,
            oldTimezone: $this->timezone,
            newTimezone: $newTimezone
        );

        $this->raise($event);
    }

    /**
     * Activate the organization.
     */
    public function activate(
        CorrelationId $correlationId,
        CausationId $causationId
    ): void {
        if ($this->active) {
            return; // Already active
        }

        $event = OrganizationActivated::create(
            tenantId: $this->getTenantId(),
            correlationId: $correlationId,
            causationId: $causationId,
            organizationId: $this->organizationId
        );

        $this->raise($event);
    }

    /**
     * Deactivate the organization.
     */
    public function deactivate(
        CorrelationId $correlationId,
        CausationId $causationId,
        ?string $reason = null
    ): void {
        if (!$this->active) {
            return; // Already inactive
        }

        $event = OrganizationDeactivated::create(
            tenantId: $this->getTenantId(),
            correlationId: $correlationId,
            causationId: $causationId,
            organizationId: $this->organizationId,
            reason: $reason
        );

        $this->raise($event);
    }

    /**
     * Apply domain events to mutate state.
     *
     * This is called internally by raise() and reconstituteFromEvents().
     * Throws RuntimeException for unknown event types (fail-fast pattern).
     *
     * @throws \RuntimeException When unknown event type is encountered
     */
    protected function apply(DomainEvent $event): void
    {
        match ($event::class) {
            OrganizationRegistered::class => $this->applyRegistered($event),
            OrganizationRenamed::class => $this->applyRenamed($event),
            OrganizationSlugChanged::class => $this->applySlugChanged($event),
            OrganizationTimezoneChanged::class => $this->applyTimezoneChanged($event),
            OrganizationActivated::class => $this->applyActivated($event),
            OrganizationDeactivated::class => $this->applyDeactivated($event),
            default => throw new \RuntimeException(
                sprintf(
                    'Unknown event type: %s (error: %s)',
                    $event::class,
                    OrganizationErrorCodes::UNKNOWN_EVENT
                )
            ),
        };
    }

    private function applyRegistered(OrganizationRegistered $event): void
    {
        $this->organizationId = $event->getOrganizationId();
        $this->name = $event->getName();
        $this->slug = $event->getSlug();
        $this->contactEmail = $event->getContactEmail();
        $this->timezone = $event->getTimezone();
        $this->active = true;
    }

    private function applyRenamed(OrganizationRenamed $event): void
    {
        $this->name = $event->getNewName();
    }

    private function applySlugChanged(OrganizationSlugChanged $event): void
    {
        $this->slug = $event->getNewSlug();
    }

    private function applyTimezoneChanged(OrganizationTimezoneChanged $event): void
    {
        $this->timezone = $event->getNewTimezone();
    }

    private function applyActivated(OrganizationActivated $event): void
    {
        $this->active = true;
    }

    private function applyDeactivated(OrganizationDeactivated $event): void
    {
        $this->active = false;
    }

    /**
     * Get the organization ID.
     */
    public function getOrganizationId(): OrganizationId
    {
        return $this->organizationId;
    }

    /**
     * Get the display name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the slug.
     */
    public function getSlug(): TenantSlug
    {
        return $this->slug;
    }

    /**
     * Get the contact email.
     */
    public function getContactEmail(): EmailAddress
    {
        return $this->contactEmail;
    }

    /**
     * Get the timezone.
     */
    public function getTimezone(): TimezoneId
    {
        return $this->timezone;
    }

    /**
     * Check if the organization is active.
     */
    public function isActive(): bool
    {
        return $this->active;
    }
}
