<?php

declare(strict_types=1);

namespace Spiral\Kernel\Domain\Customer;

final class RenameCustomer
{
    public function __construct(
        public readonly string $command_id,
        public readonly string $aggregate_id,
        public readonly string $newName,
    ) {}
}
