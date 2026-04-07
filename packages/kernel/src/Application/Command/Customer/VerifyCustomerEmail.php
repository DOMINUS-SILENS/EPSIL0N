<?php

declare(strict_types=1);

namespace Spiral\Kernel\Application\Command\Customer;

use Spiral\Kernel\Application\Contract\Command\ICommand;

/**
 * @implements ICommand<null>
 */
final readonly class VerifyCustomerEmail implements ICommand
{
    public function __construct(
        public string $aggregateId,
        public string $verificationToken,
        public string $correlationId,
        public string $causationId,
    ) {}
}
