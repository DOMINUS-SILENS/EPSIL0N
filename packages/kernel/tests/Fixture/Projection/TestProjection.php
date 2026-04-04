<?php

declare(strict_types=1);

namespace Spiral\Kernel\Tests\Fixture\Projection;

/**
 * Test fixture projection for unit testing.
 * This is a placeholder that will be replaced when projections are implemented (Phase 5+).
 *
 * @package Spiral\Kernel\Tests\Fixture\Projection
 */
final class TestProjection
{
    private string $id;
    /** @var array<string, mixed> */
    private array $data;
    private int $lastEventVersion;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(string $id, array $data = [], int $lastEventVersion = 0)
    {
        $this->id = $id;
        $this->data = $data;
        $this->lastEventVersion = $lastEventVersion;
    }

    public function id(): string
    {
        return $this->id;
    }

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return $this->data;
    }

    public function lastEventVersion(): int
    {
        return $this->lastEventVersion;
    }

    public function apply(object $event): void
    {
        // TODO: Implement when projection infrastructure is available
        $this->lastEventVersion++;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'data' => $this->data,
            'lastEventVersion' => $this->lastEventVersion,
        ];
    }
}
