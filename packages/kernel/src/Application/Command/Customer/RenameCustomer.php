<?php

declare(strict_types=1);

namespace Spiral\Kernel\Application\Command\Customer;

use Spiral\Kernel\Application\Contract\Command\ICommand;

/**
 * @implements ICommand<null>
 */
final readonly class RenameCustomer implements ICommand
{
    public function __construct(
        public string $aggregateId,
        public string $newName,
        public string $correlationId,
        public string $causationId,
    ) {}
}
