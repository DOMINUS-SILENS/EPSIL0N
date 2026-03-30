<?php

namespace App\Services\Sagas;

use Illuminate\Support\Facades\DB;

abstract class Saga
{
    protected string $sagaId;
    protected string $status = 'started';
    protected array $state = [];
    protected string $executionMode = 'LIVE';
    protected ?string $correlationId = null;
    protected ?int $lastEventId = null;
    protected ?string $timeoutAt = null;

    public function __construct(
        string $sagaId, 
        array $state = [], 
        string $status = 'started', 
        ?string $correlationId = null,
        ?int $lastEventId = null,
        ?string $timeoutAt = null
    ) {
        $this->sagaId = $sagaId;
        $this->state = $state;
        $this->status = $status;
        $this->correlationId = $correlationId;
        $this->lastEventId = $lastEventId;
        $this->timeoutAt = $timeoutAt;
    }

    public function getLastEventId(): ?int { return $this->lastEventId; }
    public function setLastEventId(int $eventId): void { $this->lastEventId = $eventId; }
    public function setTimeoutAt(?string $timestamp): void { $this->timeoutAt = $timestamp; }

    public function setExecutionMode(string $mode): void
    {
        $this->executionMode = $mode;
    }

    public function isReplay(): bool
    {
        return $this->executionMode === 'REPLAY';
    }

    public function getId(): string
    {
        return $this->sagaId;
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed' || $this->status === 'failed';
    }

    protected function complete(): void
    {
        $this->status = 'completed';
    }

    protected function fail(): void
    {
        $this->status = 'failed';
    }

    /**
     * Entrypoint for the SagaManager.
     */
    public function handle(object $event): void
    {
        $method = 'on' . class_basename($event);
        if (method_exists($this, $method)) {
            $this->$method($event);
        }
    }

    public function toSnapshot(): array
    {
        return [
            'saga_type' => static::class,
            'status' => $this->status,
            'state' => json_encode($this->state),
            'correlation_id' => $this->correlationId,
            'last_event_id' => $this->lastEventId,
            'timeout_at' => $this->timeoutAt,
            'updated_at' => now(),
        ];
    }
}
