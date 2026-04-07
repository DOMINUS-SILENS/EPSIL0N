<?php

declare(strict_types=1);

namespace Spiral\Kernel\Application\Command\Customer;

use Spiral\Kernel\Application\Contract\Command\ICommand;

/**
 * @implements ICommand<string>
 */
final readonly class RegisterCustomer implements ICommand
{
    public function __construct(
        public string $aggregateId,
        public string $name,
        public string $email,
        public string $correlationId,
        public string $causationId,
    ) {}
}
