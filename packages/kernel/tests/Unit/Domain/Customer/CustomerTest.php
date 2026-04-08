<?php

declare(strict_types=1);

namespace Spiral\Kernel\Tests\Unit\Domain\Customer;

use PHPUnit\Framework\TestCase;
use Spiral\Kernel\Domain\Customer\Customer;
use Spiral\Kernel\Domain\Customer\Event\CustomerRegistered;
use Spiral\Kernel\Domain\Customer\Event\CustomerRenamed;
use Spiral\Kernel\Domain\Identity\TenantId;
use Spiral\Kernel\Domain\Identity\CorrelationId;
use Spiral\Kernel\Domain\Identity\CausationId;
use Spiral\Kernel\Domain\Tenancy\EmailAddress;

final class CustomerTest extends TestCase
{
    public function test_determinism(): void
    {
        $tenantId = TenantId::generate();
        $correlationId = CorrelationId::generate();
        $causationId = CausationId::generate();
        $email = EmailAddress::fromString('alice@example.com');

        // Run 1
        $customer1 = new Customer('c1', $tenantId);
        $customer1->register('c1', 'Alice', $email, $correlationId, $causationId);
        $nameAfterRun1 = $customer1->getName();

        // Run 2
        $customer2 = new Customer('c1', $tenantId);
        $customer2->register('c1', 'Alice', $email, $correlationId, $causationId);
        $nameAfterRun2 = $customer2->getName();

        // Same operations produce same state
        $this->assertSame($nameAfterRun1, $nameAfterRun2);
        $this->assertSame('Alice', $customer1->getName());
        $this->assertSame('Alice', $customer2->getName());
    }

    public function test_register_produces_event(): void
    {
        $tenantId = TenantId::generate();
        $correlationId = CorrelationId::generate();
        $causationId = CausationId::generate();
        $email = EmailAddress::fromString('alice@example.com');

        $customer = new Customer('c1', $tenantId);
        $result = $customer->register('c1', 'Alice', $email, $correlationId, $causationId);

        $this->assertTrue($result->isSuccess());
        $this->assertTrue($customer->hasUncommittedEvents());
        $this->assertSame('Alice', $customer->getName());
    }

    public function test_register_twice_fails(): void
    {
        $tenantId = TenantId::generate();
        $correlationId = CorrelationId::generate();
        $causationId = CausationId::generate();
        $email = EmailAddress::fromString('alice@example.com');

        $customer = new Customer('c1', $tenantId);
        $result1 = $customer->register('c1', 'Alice', $email, $correlationId, $causationId);
        $this->assertTrue($result1->isSuccess());

        // Second registration should fail
        $result2 = $customer->register('c1', 'Bob', $email, $correlationId, $causationId);
        $this->assertTrue($result2->isFailure());
    }

    public function test_rename_before_register_fails(): void
    {
        $tenantId = TenantId::generate();
        $correlationId = CorrelationId::generate();
        $causationId = CausationId::generate();

        $customer = new Customer('c1', $tenantId);
        // Rename without register should fail (customer doesn't exist)
        $result = $customer->rename('c1', 'Alice Revised', $correlationId, $causationId);
        $this->assertTrue($result->isFailure());
    }

    public function test_replay_correctness(): void
    {
        $tenantId = TenantId::generate();
        $correlationId = CorrelationId::generate();
        $causationId = CausationId::generate();
        $email = EmailAddress::fromString('alice@example.com');

        // Create customer and register
        $customer = new Customer('a1', $tenantId);
        $customer->register('a1', 'Alice', $email, $correlationId, $causationId);
        $customer->verifyEmail('a1', $correlationId, $causationId);
        $customer->rename('a1', 'Alice Revised', $correlationId, $causationId);

        // Extract events
        $events = $customer->getUncommittedEvents();

        // Reconstitute new customer from events
        $customer2 = new Customer('a1', $tenantId);
        $customer2->reconstituteFromEvents($events, count($events));

        $this->assertSame('Alice Revised', $customer2->getName());
        $this->assertTrue($customer2->isVerified());
    }
}
