<?php

declare(strict_types=1);

namespace Spiral\Kernel\Infrastructure\Contract\Persistence;

use Spiral\Kernel\Domain\Identity\TenantId;
use Spiral\Kernel\Domain\Shared\Aggregate\AggregateRoot;
use Spiral\Kernel\Domain\Shared\ValueObject\ValueObject;

/**
 * Generic repository contract for aggregate persistence.
 *
 * @template T of AggregateRoot
 * @template TId of ValueObject
 */
interface IRepository
{
    /**
     * Retrieves an aggregate by its identifier.
     *
     * @param TId $id
     * @param TenantId $tenantId
     * @return T|null
     */
    public function getById(ValueObject $id, TenantId $tenantId): ?AggregateRoot;

    /**
     * Persists the state of an aggregate.
     *
     * @param T $aggregate
     */
    public function save(AggregateRoot $aggregate): void;
}
