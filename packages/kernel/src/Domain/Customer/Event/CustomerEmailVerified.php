<?php

declare(strict_types=1);

namespace Spiral\Kernel\Domain\Customer\Event;

use Spiral\Kernel\Domain\Shared\Event\DomainEvent;
use Spiral\Kernel\Domain\Identity\EventId;
use Spiral\Kernel\Domain\Identity\TenantId;
use Spiral\Kernel\Domain\Identity\CorrelationId;
use Spiral\Kernel\Domain\Identity\CausationId;
use Spiral\Kernel\Domain\Sync\SyncMetadata;
use DateTimeImmutable;

final readonly class CustomerEmailVerified implements DomainEvent
{
    public function __construct(
        private EventId $eventId,
        private TenantId $tenantId,
        private CorrelationId $correlationId,
        private CausationId $causationId,
        private DateTimeImmutable $occurredAt,
        public string $customerId,
        private string $schemaVersion = '1.0'
    ) {}

    public function getEventId(): EventId { return $this->eventId; }
    public function getTenantId(): TenantId { return $this->tenantId; }
    public function getCorrelationId(): CorrelationId { return $this->correlationId; }
    public function getCausationId(): CausationId { return $this->causationId; }
    public function getOccurredAt(): DateTimeImmutable { return $this->occurredAt; }
    public function getSchemaVersion(): string { return $this->schemaVersion; }
    public function getEventType(): string { return 'CustomerEmailVerified'; }
    public function getClassName(): string { return self::class; }
    public function getSyncMetadata(): ?SyncMetadata { return null; }

    public function toArray(): array
    {
        return [
            'eventId' => $this->eventId->toString(),
            'tenantId' => $this->tenantId->toString(),
            'correlationId' => $this->correlationId->toString(),
            'causationId' => $this->causationId->toString(),
            'occurredAt' => $this->occurredAt->format(DateTimeImmutable::ATOM),
            'schemaVersion' => $this->schemaVersion,
            'customerId' => $this->customerId,
        ];
    }
}
