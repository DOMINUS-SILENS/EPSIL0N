<?php

declare(strict_types=1);

namespace Spiral\Kernel\Tests\Unit\Domain\Customer;

use PHPUnit\Framework\TestCase;
use Spiral\Kernel\Domain\Customer\Customer;
use Spiral\Kernel\Domain\Customer\CreateCustomer;
use Spiral\Kernel\Domain\Customer\RenameCustomer;
use Spiral\Kernel\Domain\Customer\CustomerCreated;
use Spiral\Kernel\Domain\Customer\CustomerRenamed;
use RuntimeException;

final class CustomerTest extends TestCase
{
    public function test_determinism(): void
    {
        $tenantId = \Spiral\Kernel\Domain\Identity\TenantId::generate();
        $cmd1 = new CreateCustomer('c1', 'a1', 'Alice');
        $cmd2 = new RenameCustomer('c2', 'a1', 'Alice Revised');

        // Run 1
        $customer1 = new Customer('c1', $tenantId);
        $res1 = $customer1->decide($cmd1);
        $events1 = $res1->unwrap();
        $customer1->apply($events1[0]);
        $res2 = $customer1->decide($cmd2);
        $events2 = $res2->unwrap();
        $customer1->apply($events2[0]);

        // Run 2
        $customer2 = new Customer('c1', $tenantId);
        $res3 = $customer2->decide($cmd1);
        $events3 = $res3->unwrap();
        $customer2->apply($events3[0]);
        $res4 = $customer2->decide($cmd2);
        $events4 = $res4->unwrap();
        $customer2->apply($events4[0]);

        $this->assertEquals($events1, $events3);
        $this->assertEquals($events2, $events4);
        $this->assertSame($customer1->getName(), $customer2->getName());
    }

    public function test_purity(): void
    {
        $tenantId = \Spiral\Kernel\Domain\Identity\TenantId::generate();
        $customer = new Customer('c1', $tenantId);
        $cmd = new CreateCustomer('c1', 'a1', 'Alice');

        $res = $customer->decide($cmd);

        // State must not change during decide()
        $this->assertNull($customer->getName());
        $this->assertCount(1, $res->unwrap());
    }

    public function test_rejection_safety_create_twice(): void
    {
        $tenantId = \Spiral\Kernel\Domain\Identity\TenantId::generate();
        $customer = new Customer('c1', $tenantId);
        $cmd1 = new CreateCustomer('c1', 'a1', 'Alice');
        $res1 = $customer->decide($cmd1);
        $events1 = $res1->unwrap();
        $customer->apply($events1[0]);

        $cmd2 = new CreateCustomer('c2', 'a1', 'Bob');

        $res2 = $customer->decide($cmd2);
        $this->assertTrue($res2->isFailure());
    }

    public function test_rejection_safety_rename_before_create(): void
    {
        $tenantId = \Spiral\Kernel\Domain\Identity\TenantId::generate();
        $customer = new Customer('c1', $tenantId);
        $cmd = new RenameCustomer('c1', 'a1', 'Alice');

        $res = $customer->decide($cmd);
        $this->assertTrue($res->isFailure());
    }

    public function test_replay_correctness(): void
    {
        $tenantId = \Spiral\Kernel\Domain\Identity\TenantId::generate();
        $events = [
            new CustomerCreated('a1', 'Alice'),
            new CustomerRenamed('a1', 'Alice Revised'),
        ];

        $customer = new Customer('a1', $tenantId);

        // Use reflection to call protected apply() for unit testing replay
        $reflection = new \ReflectionClass(Customer::class);
        $method = $reflection->getMethod('apply');
        $method->setAccessible(true);

        foreach ($events as $event) {
            $method->invoke($customer, $event);
        }

        $this->assertSame('Alice Revised', $customer->getName());
    }
}
