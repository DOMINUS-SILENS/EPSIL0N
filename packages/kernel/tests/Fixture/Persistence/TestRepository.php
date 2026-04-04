<?php

declare(strict_types=1);

namespace Spiral\Kernel\Tests\Fixture\Persistence;

/**
 * Test fixture repository for unit testing.
 * This is a placeholder that will be replaced when IRepository is implemented (Phase 5+).
 *
 * @package Spiral\Kernel\Tests\Fixture\Persistence
 */
final class TestRepository
{
    /** @var list<object> */
    private array $aggregates = [];

    public function save(object $aggregate): void
    {
        // TODO: Implement when IRepository is available
        $this->aggregates[] = $aggregate;
    }

    public function find(string $id): ?object
    {
        // TODO: Implement when IRepository is available
        foreach ($this->aggregates as $aggregate) {
            if (!is_object($aggregate)) {
                continue;
            }
            if (method_exists($aggregate, 'id') && $aggregate->id() === $id) {
                return $aggregate;
            }
        }
        return null;
    }

    public function count(): int
    {
        return count($this->aggregates);
    }

    public function clear(): void
    {
        $this->aggregates = [];
    }
}
