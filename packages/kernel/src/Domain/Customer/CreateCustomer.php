<?php

declare(strict_types=1);

namespace Spiral\Kernel\Domain\Customer;

/**
 * Domain command for testing aggregate behavior.
 *
 * CreateCustomer is a simple domain command that returns events without
 * applying them. It supports functional testing of aggregate behavior and
 * internal domain composition.
 *
 * Note: This is NOT used in production. Production customer creation flows
 * through the Application layer (RegisterCustomerHandler) which orchestrates
 * authorization, idempotency, email uniqueness checks, and event persistence.
 *
 * @package Spiral\Kernel\Domain\Customer
 */
final class CreateCustomer
{
    public function __construct(
        public readonly string $command_id,
        public readonly string $aggregate_id,
        public readonly string $name,
    ) {}
}
