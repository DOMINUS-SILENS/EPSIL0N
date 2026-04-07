<?php

declare(strict_types=1);

namespace Spiral\Kernel\Domain\Customer;

/**
 * Domain command for testing customer rename behavior.
 *
 * RenameCustomer is a simple domain command that returns events without
 * applying them. It supports functional testing of rename logic and internal
 * domain composition.
 *
 * Domain rename validation is simplified (name pattern only). Production
 * renames flow through the Application layer (RegisterCustomerHandler)
 * which enforces email verification and customer active status checks.
 *
 * @package Spiral\Kernel\Domain\Customer
 */
final class RenameCustomer
{
    public function __construct(
        public readonly string $command_id,
        public readonly string $aggregate_id,
        public readonly string $newName,
    ) {}
}
