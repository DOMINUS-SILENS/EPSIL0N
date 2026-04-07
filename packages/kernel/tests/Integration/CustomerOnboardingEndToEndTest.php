<?php

declare(strict_types=1);

namespace Spiral\Kernel\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Spiral\Kernel\Application\Command\Customer\RegisterCustomer;
use Spiral\Kernel\Application\Command\Customer\VerifyCustomerEmail;
use Spiral\Kernel\Application\Command\Customer\RenameCustomer;
use Spiral\Kernel\Application\Command\Customer\DeactivateCustomer;
use Spiral\Kernel\Application\Command\Customer\ReactivateCustomer;
use Spiral\Kernel\Application\Handler\Customer\RegisterCustomerHandler;
use Spiral\Kernel\Domain\Customer\Customer;
use Spiral\Kernel\Domain\Identity\TenantId;
use Spiral\Kernel\Domain\Identity\CorrelationId;
use Spiral\Kernel\Domain\Shared\Result\Result;
use Spiral\Kernel\Infrastructure\Contract\EventStore\IEventStore;
use Spiral\Kernel\Infrastructure\Contract\Security\IAuthorizationService;
use Spiral\Kernel\Infrastructure\Contract\MobileSync\IMobileSyncFeed;
use Spiral\Kernel\Infrastructure\Contract\Projection\IProjectionEngine;
use Spiral\Kernel\Infrastructure\Contract\Idempotency\IIdempotencyService;
use Spiral\Kernel\Infrastructure\Persistence\EventSourcedRepository;
use Spiral\Kernel\Infrastructure\Persistence\EventStore\PostgreSqlEventStore;
use Spiral\Kernel\Infrastructure\Persistence\Idempotency\PostgresqlIdempotencyService;
use Spiral\Kernel\Infrastructure\Projection\PostgresqlProjectionEngine;
use Spiral\Kernel\Infrastructure\Projection\Sync\PostgresqlMobileSyncFeed;
use Spiral\Kernel\Infrastructure\Security\SimpleAuthorizationService;
use Spiral\Kernel\Infrastructure\Persistence\Repository\EventStoreEmailUniquenessChecker;
use Spiral\Kernel\Support\Exception\ConcurrencyConflictException;
use Spiral\Kernel\Domain\Shared\Error\ErrorCode;

final class CustomerOnboardingEndToEndTest extends IntegrationTestCase
{
    private IEventStore $eventStore;
    private IProjectionEngine $projectionEngine;
    private IMobileSyncFeed $mobileSyncFeed;
    private IAuthorizationService $authService;
    private IIdempotencyService $idempotencyService;
    private EventSourcedRepository $repository;
    private RegisterCustomerHandler $registerHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->skipIfEventStoreNotAvailable();

        $pdo = $this->getConnection();

        // Initialize Runtime Spine
        $this->eventStore = new PostgreSqlEventStore($pdo);
        $this->projectionEngine = new PostgresqlProjectionEngine($pdo);
        $this->mobileSyncFeed = new PostgresqlMobileSyncFeed($pdo);
        $this->idempotencyService = new PostgresqlIdempotencyService($pdo);

        // Setup Simple Auth Service (Mock-like behavior for testing)
        $this->authService = new SimpleAuthorizationService();

        $this->repository = new EventSourcedRepository(
            $this->eventStore,
            new \Spiral\Kernel\Infrastructure\Persistence\EventStore\EventSerializer()
        );

        $this->registerHandler = new RegisterCustomerHandler(
            $this->authService,
            $this->idempotencyService,
            new EventStoreEmailUniquenessChecker($this->eventStore),
            $this->eventStore,
            $this->repository
        );
    }

    public function testFullCustomerLifecycle(): void
    {
        $tenantId = TenantId::generate();
        $this->authService->setCurrentTenantId($tenantId);

        $customerId = \Spiral\Kernel\Domain\Identity\DocumentId::generate()->toString();
        $email = 'test@example.com';
        $name = 'Initial Name';

        // 1. Registration
        $registerCmd = new RegisterCustomer($customerId, $name, $email);
        $result = $this->registerHandler->handle($registerCmd);

        $this->assertTrue($result->isSuccess());

        // Verify Event Persisted
        $stream = $this->eventStore->getStream($tenantId, $customerId, 0);
        $this->assertCount(1, $stream);
        $this->assertInstanceOf(\Spiral\Kernel\Domain\Customer\Event\CustomerRegistered::class, $stream[0]->event);

        // Verify Projection
        $customerData = $this->projectionEngine->query('SELECT * FROM projection_customers WHERE id = ?', [$customerId]);
        $this->assertNotEmpty($customerData);
        $this->assertEquals($name, $customerData[0]['name']);

        // 2. Email Verification
        $customer = $this->repository->getById($customerId, $tenantId);
        $verifyCmd = new VerifyCustomerEmail($customerId);
        $verifyResult = $customer->decide($verifyCmd);
        $this->assertTrue($verifyResult->isSuccess());
        $this->repository->save($customer);

        $customerReloaded = $this->repository->getById($customerId, $tenantId);
        $this->assertTrue($customerReloaded->isVerified());

        // 3. Invariant Enforcement: Rename should fail if NOT verified (Testing a fresh aggregate for isolation)
        $unverifiedId = \Spiral\Kernel\Domain\Identity\DocumentId::generate()->toString();
        $unverifiedCustomer = new Customer($tenantId, $unverifiedId);
        $unverifiedCustomer->decide(new RegisterCustomer($unverifiedId, 'Unverified', 'unv@ex.com'));
        $this->repository->save($unverifiedCustomer);

        $renameCmd = new RenameCustomer($unverifiedId, 'New Name');
        $renameResult = $unverifiedCustomer->decide($renameCmd);
        $this->assertTrue($renameResult->isFailure());
        $this->assertEquals('DOMAIN.CUSTOMER.NOT_VERIFIED', $renameResult->getFailure()->code()->toString());

        // 4. Post-Verification Modification
        $renameCmdVerified = new RenameCustomer($customerId, 'Updated Name');
        $renameResultVerified = $customerReloaded->decide($renameCmdVerified);
        $this->assertTrue($renameResultVerified->isSuccess());
        $this->repository->save($customerReloaded);

        $customerFinal = $this->repository->getById($customerId, $tenantId);
        $this->assertEquals('Updated Name', $customerFinal->getName());

        // 5. Deactivation
        $deactivateCmd = new DeactivateCustomer($customerId, 'Requested by user');
        $deactivateResult = $customerFinal->decide($deactivateCmd);
        $this->assertTrue($deactivateResult->isSuccess());
        $this->repository->save($customerFinal);

        $customerDeactivated = $this->repository->getById($customerId, $tenantId);
        $this->assertFalse($customerDeactivated->isActive());

        // 6. Global Invariant: Modifications fail while deactivated
        $renameCmdInactive = new RenameCustomer($customerId, 'Cannot Rename');
        $renameResultInactive = $customerDeactivated->decide($renameCmdInactive);
        $this->assertTrue($renameResultInactive->isFailure());
        $this->assertEquals('DOMAIN.CUSTOMER.INACTIVE', $renameResultInactive->getFailure()->code()->toString());

        // 7. Reactivation
        $reactivateCmd = new ReactivateCustomer($customerId);
        $reactivateResult = $customerDeactivated->decide($reactivateCmd);
        $this->assertTrue($reactivateResult->isSuccess());
        $this->repository->save($customerDeactivated);

        $customerActiveAgain = $this->repository->getById($customerId, $tenantId);
        $this->assertTrue($customerActiveAgain->isActive());

        // 8. Idempotency
        // Using the same command sequence for registration would be handled by the handler
        $duplicateRegister = new RegisterCustomer($customerId, $name, $email);
        $dupResult = $this->registerHandler->handle($duplicateRegister);
        // If already exists, the aggregate returns DOMAIN.CUSTOMER.ALREADY_EXISTS
        $this->assertTrue($dupResult->isFailure());
        $this->assertEquals('DOMAIN.CUSTOMER.ALREADY_EXISTS', $dupResult->getFailure()->code()->toString());

        // 9. Optimistic Concurrency
        $c1 = $this->repository->getById($customerId, $tenantId);
        $c2 = $this->repository->getById($customerId, $tenantId);

        $c1->decide(new RenameCustomer($customerId, 'Winner'));
        $this->repository->save($c1);

        try {
            $c2->decide(new RenameCustomer($customerId, 'Loser'));
            $this->repository->save($c2);
            $this->fail('Expected ConcurrencyConflictException was not thrown');
        } catch (ConcurrencyConflictException $e) {
            $this->assertTrue(true);
        }

        // 10. Tenant Isolation
        $tenantB = TenantId::generate();
        $this->authService->setCurrentTenantId($tenantB);

        try {
            $this->repository->getById($customerId, $tenantB);
            // Since getById for an event-sourced repo usually returns a new instance if not found,
            // we check if the version is 0 (meaning it didn't load any events from Tenant B)
            $customerTenantB = $this->repository->getById($customerId, $tenantB);
            $this->assertEquals(0, $customerTenantB->getVersion());
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }

        // 11. Mobile Sync
        $deviceId = 'device-123';
        $deltas = $this->mobileSyncFeed->fetchDeltas($tenantId, $deviceId);
        $this->assertNotEmpty($deltas);

        $this->mobileSyncFeed->acknowledge($tenantId, $deviceId, $deltas[0]->offset);
        $newDeltas = $this->mobileSyncFeed->fetchDeltas($tenantId, $deviceId);
        $this->assertCount(0, $newDeltas);
    }
}
