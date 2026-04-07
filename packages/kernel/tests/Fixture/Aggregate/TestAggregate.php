<?php

declare(strict_types=1);

namespace Spiral\Kernel\Tests\Fixture\Aggregate;

// Test event classes are defined in TestAggregateEvents.php
require_once __DIR__ . '/TestAggregateEvents.php';

use Spiral\Kernel\Domain\Identity\TenantId;
use Spiral\Kernel\Domain\Identity\ActorId;
use Spiral\Kernel\Domain\Identity\EventId;
use Spiral\Kernel\Domain\Identity\CorrelationId;
use Spiral\Kernel\Domain\Identity\CausationId;
use Spiral\Kernel\Support\Exception\BusinessRuleViolationException;
use DateTimeImmutable;

/**
 * Enhanced test fixture aggregate for comprehensive unit testing.
 * Demonstrates state management, invariant enforcement, and event emission.
 *
 * @package Spiral\Kernel\Tests\Fixture\Aggregate
 */
final class TestAggregate
{
    private string $id;
    private TenantId $tenantId;
    private int $version = 0;

    /** @var list<object> */
    private array $recordedEvents = [];

    // State properties
    private string $name = '';
    private bool $active = false;
    private bool $created = false;
    private int $nameChangeCount = 0;

    public function __construct(string $id, TenantId $tenantId)
    {
        $this->id = $id;
        $this->tenantId = $tenantId;
    }

    // ========== Factory Methods ==========

    public static function create(
        string $id,
        TenantId $tenantId,
        string $name,
        ActorId $createdBy
    ): self {
        if ($name === '') {
            throw new BusinessRuleViolationException(
                'EMPTY_NAME',
                'Aggregate name cannot be empty'
            );
        }

        if (strlen($name) < 3) {
            throw new BusinessRuleViolationException(
                'NAME_TOO_SHORT',
                'Aggregate name must be at least 3 characters'
            );
        }

        $aggregate = new self($id, $tenantId);

        $event = new TestAggregateCreated(
            EventId::generate(),
            $tenantId,
            CorrelationId::generate(),
            CausationId::generate(),
            new DateTimeImmutable(),
            $id,
            $name,
            new DateTimeImmutable(),
            $createdBy->toString()
        );

        $aggregate->recordEvent($event);
        $aggregate->applyTestAggregateCreated($event);

        return $aggregate;
    }

    // ========== Domain Methods ==========

    public function changeName(string $newName, ActorId $changedBy): void
    {
        $this->assertCreated();
        $this->assertNotTooManyNameChanges();

        if ($newName === '') {
            throw new BusinessRuleViolationException(
                'EMPTY_NAME',
                'Aggregate name cannot be empty'
            );
        }

        if ($newName === $this->name) {
            // No change needed, but we don't throw - it's idempotent
            return;
        }

        $event = new TestAggregateNameChanged(
            EventId::generate(),
            $this->tenantId,
            CorrelationId::generate(),
            CausationId::generate(),
            new DateTimeImmutable(),
            $this->id,
            $newName,
            new DateTimeImmutable(),
            $changedBy->toString()
        );

        $this->recordEvent($event);
        $this->applyTestAggregateNameChanged($event);
    }

    public function activate(ActorId $activatedBy): void
    {
        $this->assertCreated();

        if ($this->active) {
            // Already active, idempotent
            return;
        }

        $event = new TestAggregateActivated(
            EventId::generate(),
            $this->tenantId,
            CorrelationId::generate(),
            CausationId::generate(),
            new DateTimeImmutable(),
            $this->id,
            new DateTimeImmutable(),
            $activatedBy->toString()
        );

        $this->recordEvent($event);
        $this->applyTestAggregateActivated($event);
    }

    public function deactivate(string $reason, ActorId $deactivatedBy): void
    {
        $this->assertCreated();

        if (!$this->active) {
            throw new BusinessRuleViolationException(
                'ALREADY_INACTIVE',
                'Cannot deactivate an already inactive aggregate'
            );
        }

        if ($reason === '') {
            throw new BusinessRuleViolationException(
                'EMPTY_REASON',
                'Deactivation reason cannot be empty'
            );
        }

        $event = new TestAggregateDeactivated(
            EventId::generate(),
            $this->tenantId,
            CorrelationId::generate(),
            CausationId::generate(),
            new DateTimeImmutable(),
            $this->id,
            $reason,
            new DateTimeImmutable(),
            $deactivatedBy->toString()
        );

        $this->recordEvent($event);
        $this->applyTestAggregateDeactivated($event);
    }

    // ========== Event Handlers ==========

    private function applyTestAggregateCreated(TestAggregateCreated $event): void
    {
        $this->name = $event->name;
        $this->active = true;
        $this->created = true;
    }

    private function applyTestAggregateNameChanged(TestAggregateNameChanged $event): void
    {
        $this->name = $event->newName;
        $this->nameChangeCount++;
    }

    private function applyTestAggregateActivated(TestAggregateActivated $event): void
    {
        $this->active = true;
    }

    private function applyTestAggregateDeactivated(TestAggregateDeactivated $event): void
    {
        $this->active = false;
    }

    // ========== Invariant Checks ==========

    private function assertCreated(): void
    {
        if (!$this->created) {
            throw new BusinessRuleViolationException(
                'NOT_CREATED',
                'Operation not allowed on non-created aggregate'
            );
        }
    }

    private function assertNotTooManyNameChanges(): void
    {
        if ($this->nameChangeCount >= 10) {
            throw new BusinessRuleViolationException(
                'TOO_MANY_NAME_CHANGES',
                'Maximum number of name changes exceeded'
            );
        }
    }

    // ========== Event Management ==========

    public function recordEvent(object $event): void
    {
        $this->recordedEvents[] = $event;
        $this->version++;
    }

    /**
     * @return list<object>
     */
    public function releaseEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = [];
        return $events;
    }

    /**
     * @return list<object>
     */
    public function peekEvents(): array
    {
        return $this->recordedEvents;
    }

    public function hasUncommittedEvents(): bool
    {
        return count($this->recordedEvents) > 0;
    }

    // ========== Accessors ==========

    public function id(): string
    {
        return $this->id;
    }

    public function tenantId(): TenantId
    {
        return $this->tenantId;
    }

    public function version(): int
    {
        return $this->version;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function isCreated(): bool
    {
        return $this->created;
    }

    public function nameChangeCount(): int
    {
        return $this->nameChangeCount;
    }

    // ========== Reconstitution ==========

    /**
     * @param list<object> $events
     */
    public static function reconstitute(string $id, TenantId $tenantId, array $events): self
    {
        $aggregate = new self($id, $tenantId);

        foreach ($events as $event) {
            $aggregate->applyEvent($event);
            $aggregate->version++;
        }

        return $aggregate;
    }

    private function applyEvent(object $event): void
    {
        match (get_class($event)) {
            TestAggregateCreated::class => $this->applyTestAggregateCreated($event),
            TestAggregateNameChanged::class => $this->applyTestAggregateNameChanged($event),
            TestAggregateActivated::class => $this->applyTestAggregateActivated($event),
            TestAggregateDeactivated::class => $this->applyTestAggregateDeactivated($event),
            default => throw new \RuntimeException('Unknown event type: ' . get_class($event)),
        };
    }
}
