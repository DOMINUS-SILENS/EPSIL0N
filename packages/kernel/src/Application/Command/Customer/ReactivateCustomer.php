<?php

declare(strict_types=1);

namespace Spiral\Kernel\Application\Command\Customer;

use Spiral\Kernel\Application\Contract\Command\ICommand;

/**
 * @implements ICommand<null>
 */
final class ReactivateCustomer implements ICommand
{
    public function __construct(
        public readonly string $aggregateId,
        public readonly string $correlationId,
        public readonly string $causationId,
    ) {}
}
