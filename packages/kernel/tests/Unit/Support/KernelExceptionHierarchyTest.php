<?php

declare(strict_types=1);

namespace Spiral\Kernel\Tests\Unit\Support;

use Spiral\Kernel\Support\Exception\KernelException;
use Spiral\Kernel\Support\Exception\DomainException;
use Spiral\Kernel\Support\Exception\ValidationException;
use Spiral\Kernel\Support\Exception\AuthorizationException;
use Spiral\Kernel\Support\Exception\BusinessRuleViolationException;
use Spiral\Kernel\Support\Exception\ConcurrencyConflictException;
use Spiral\Kernel\Support\Exception\NotFoundException;
use Spiral\Kernel\Tests\KernelTestCase;

/**
 * Comprehensive tests for Kernel Exception hierarchy.
 * Tests cover: exception creation, error codes, context, inheritance, and behavior.
 */
final class KernelExceptionHierarchyTest extends KernelTestCase
{
    public function testKernelExceptionIsAbstract(): void
    {
        $reflection = new \ReflectionClass(KernelException::class);

        $this->assertTrue($reflection->isAbstract());
    }

    public function testKernelExceptionExtendsException(): void
    {
        $reflection = new \ReflectionClass(KernelException::class);

        $this->assertTrue($reflection->isSubclassOf(\Exception::class));
    }

    public function testDomainExceptionExtendsKernelException(): void
    {
        $reflection = new \ReflectionClass(DomainException::class);

        $this->assertTrue($reflection->isSubclassOf(KernelException::class));
    }

    public function testValidationExceptionExtendsDomainException(): void
    {
        $reflection = new \ReflectionClass(ValidationException::class);

        $this->assertTrue($reflection->isSubclassOf(DomainException::class));
    }

    public function testBusinessRuleViolationExceptionExtendsDomainException(): void
    {
        $reflection = new \ReflectionClass(BusinessRuleViolationException::class);

        $this->assertTrue($reflection->isSubclassOf(DomainException::class));
    }

    public function testNotFoundExceptionExtendsDomainException(): void
    {
        $reflection = new \ReflectionClass(NotFoundException::class);

        $this->assertTrue($reflection->isSubclassOf(DomainException::class));
    }

    public function testAuthorizationExceptionExtendsKernelException(): void
    {
        $reflection = new \ReflectionClass(AuthorizationException::class);

        $this->assertTrue($reflection->isSubclassOf(KernelException::class));
        /** @phpstan-ignore-next-line Intentionally asserting that AuthorizationException is NOT a subclass of DomainException */
        $this->assertFalse($reflection->isSubclassOf(DomainException::class));
    }

    public function testConcurrencyConflictExceptionExtendsKernelException(): void
    {
        $reflection = new \ReflectionClass(ConcurrencyConflictException::class);

        $this->assertTrue($reflection->isSubclassOf(KernelException::class));
        /** @phpstan-ignore-next-line Intentionally asserting that ConcurrencyConflictException is NOT a subclass of DomainException */
        $this->assertFalse($reflection->isSubclassOf(DomainException::class));
    }
}

final class ValidationExceptionTest extends KernelTestCase
{
    // ========== Creation Tests ==========

    public function testCreationWithErrors(): void
    {
        $errors = [
            'email' => ['Email is required'],
            'password' => ['Password is too short'],
        ];

        $exception = new ValidationException($errors, 'Validation failed');

        $this->assertSame('Validation failed', $exception->getMessage());
        $this->assertSame($errors, $exception->getErrors());
    }

    public function testCreationWithDefaultMessage(): void
    {
        $exception = new ValidationException(['field' => ['error']]);

        $this->assertSame('Validation failed', $exception->getMessage());
    }

    public function testCreationWithPreviousException(): void
    {
        $previous = new \RuntimeException('Previous error');
        $exception = new ValidationException(['field' => ['error']], 'Validation failed', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    // ========== Error Code Tests ==========

    public function testGetErrorCode(): void
    {
        $exception = new ValidationException([]);

        $this->assertSame('VALIDATION_FAILED', $exception->getErrorCode());
    }

    // ========== Context Tests ==========

    public function testGetContext(): void
    {
        $errors = [
            'email' => ['Email is required', 'Email must be valid'],
            'password' => ['Password is too short'],
        ];

        $exception = new ValidationException($errors);
        $context = $exception->getContext();

        $this->assertArrayHasKey('errors', $context);
        $this->assertArrayHasKey('fieldCount', $context);
        $this->assertSame($errors, $context['errors']);
        $this->assertSame(2, $context['fieldCount']);
    }

    public function testGetContextWithEmptyErrors(): void
    {
        $exception = new ValidationException([]);
        $context = $exception->getContext();

        $this->assertArrayHasKey('errors', $context);
        $this->assertArrayHasKey('fieldCount', $context);
        $this->assertSame([], $context['errors']);
        $this->assertSame(0, $context['fieldCount']);
    }

    // ========== Field Error Tests ==========

    public function testHasFieldErrorReturnsTrue(): void
    {
        $exception = new ValidationException(['email' => ['Email is required']]);

        $this->assertTrue($exception->hasFieldError('email'));
    }

    public function testHasFieldErrorReturnsFalse(): void
    {
        $exception = new ValidationException(['email' => ['Email is required']]);

        $this->assertFalse($exception->hasFieldError('username'));
    }

    public function testHasFieldErrorReturnsFalseForEmptyErrors(): void
    {
        $exception = new ValidationException([]);

        $this->assertFalse($exception->hasFieldError('email'));
    }

    public function testGetFieldErrorsReturnsErrors(): void
    {
        $errors = ['email' => ['Email is required', 'Email must be valid']];
        $exception = new ValidationException($errors);

        $this->assertSame(['Email is required', 'Email must be valid'], $exception->getFieldErrors('email'));
    }

    public function testGetFieldErrorsReturnsEmptyArrayForMissingField(): void
    {
        $exception = new ValidationException(['email' => ['Error']]);

        $this->assertSame([], $exception->getFieldErrors('username'));
    }

    // ========== Throwable Behavior Tests ==========

    public function testCanBeThrown(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Validation failed');

        throw new ValidationException(['field' => ['error']], 'Validation failed');
    }

    public function testCanBeCaughtAsKernelException(): void
    {
        $exception = new ValidationException(['field' => ['error']]);

        try {
            throw $exception;
        } catch (KernelException $e) {
            $this->assertSame($exception, $e);
        }
    }

    public function testCanBeCaughtAsDomainException(): void
    {
        $exception = new ValidationException(['field' => ['error']]);

        try {
            throw $exception;
        } catch (DomainException $e) {
            $this->assertSame($exception, $e);
        }
    }
}

final class AuthorizationExceptionTest extends KernelTestCase
{
    // ========== Creation Tests ==========

    public function testCreationWithActorAndAction(): void
    {
        $exception = new AuthorizationException('actor-123', 'delete_order');

        $this->assertStringContainsString('actor-123', $exception->getMessage());
        $this->assertStringContainsString('delete_order', $exception->getMessage());
        $this->assertStringContainsString('not authorized', $exception->getMessage());
    }

    public function testCreationWithResource(): void
    {
        $exception = new AuthorizationException('actor-123', 'edit', 'Order', 'ORD-001');

        $this->assertStringContainsString('Order', $exception->getMessage());
        $this->assertStringContainsString('ORD-001', $exception->getMessage());
    }

    public function testCreationWithResourceTypeOnly(): void
    {
        $exception = new AuthorizationException('actor-123', 'create', 'Order');

        $this->assertStringContainsString('Order', $exception->getMessage());
        $this->assertStringNotContainsString('(', $exception->getMessage());
    }

    public function testCreationWithPreviousException(): void
    {
        $previous = new \RuntimeException('Previous error');
        $exception = new AuthorizationException('actor-123', 'delete', null, null, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    // ========== Error Code Tests ==========

    public function testGetErrorCode(): void
    {
        $exception = new AuthorizationException('actor-123', 'delete');

        $this->assertSame('AUTHORIZATION_DENIED', $exception->getErrorCode());
    }

    // ========== Context Tests ==========

    public function testGetContextWithAllFields(): void
    {
        $exception = new AuthorizationException('actor-123', 'delete', 'Order', 'ORD-001');
        $context = $exception->getContext();

        $this->assertSame('actor-123', $context['actorId']);
        $this->assertSame('delete', $context['action']);
        $this->assertSame('Order', $context['resourceType']);
        $this->assertSame('ORD-001', $context['resourceId']);
    }

    public function testGetContextWithMinimalFields(): void
    {
        $exception = new AuthorizationException('actor-123', 'delete');
        $context = $exception->getContext();

        $this->assertSame('actor-123', $context['actorId']);
        $this->assertSame('delete', $context['action']);
        $this->assertNull($context['resourceType']);
        $this->assertNull($context['resourceId']);
    }

    // ========== Accessor Tests ==========

    public function testGetActorId(): void
    {
        $exception = new AuthorizationException('actor-456', 'delete');

        $this->assertSame('actor-456', $exception->getActorId());
    }

    public function testGetAction(): void
    {
        $exception = new AuthorizationException('actor-123', 'update_status');

        $this->assertSame('update_status', $exception->getAction());
    }

    public function testGetResourceType(): void
    {
        $exception = new AuthorizationException('actor-123', 'edit', 'Customer');

        $this->assertSame('Customer', $exception->getResourceType());
    }

    public function testGetResourceTypeReturnsNullWhenNotSet(): void
    {
        $exception = new AuthorizationException('actor-123', 'delete');

        $this->assertNull($exception->getResourceType());
    }

    public function testGetResourceId(): void
    {
        $exception = new AuthorizationException('actor-123', 'edit', 'Order', 'ORD-12345');

        $this->assertSame('ORD-12345', $exception->getResourceId());
    }

    public function testGetResourceIdReturnsNullWhenNotSet(): void
    {
        $exception = new AuthorizationException('actor-123', 'edit', 'Order');

        $this->assertNull($exception->getResourceId());
    }

    // ========== Throwable Behavior Tests ==========

    public function testCanBeCaughtAsKernelException(): void
    {
        $exception = new AuthorizationException('actor-123', 'delete');

        try {
            throw $exception;
        } catch (KernelException $e) {
            $this->assertSame($exception, $e);
        }
    }
}

final class BusinessRuleViolationExceptionTest extends KernelTestCase
{
    // ========== Creation Tests ==========

    public function testCreationWithRuleNameAndMessage(): void
    {
        $exception = new BusinessRuleViolationException(
            'CREDIT_LIMIT_EXCEEDED',
            'Customer credit limit exceeded'
        );

        $this->assertSame('Customer credit limit exceeded', $exception->getMessage());
    }

    public function testCreationWithContext(): void
    {
        $exception = new BusinessRuleViolationException(
            'CREDIT_LIMIT_EXCEEDED',
            'Customer credit limit exceeded',
            ['customerId' => 'CUST-001', 'limit' => 10000, 'current' => 12000]
        );

        $context = $exception->getContext();

        $this->assertSame('CUST-001', $context['customerId']);
        $this->assertSame(10000, $context['limit']);
        $this->assertSame(12000, $context['current']);
    }

    public function testCreationWithPreviousException(): void
    {
        $previous = new \RuntimeException('Previous error');
        $exception = new BusinessRuleViolationException(
            'RULE_123',
            'Rule violated',
            [],
            $previous
        );

        $this->assertSame($previous, $exception->getPrevious());
    }

    // ========== Error Code Tests ==========

    public function testGetErrorCode(): void
    {
        $exception = new BusinessRuleViolationException('RULE_123', 'test');

        $this->assertSame('BUSINESS_RULE_VIOLATION', $exception->getErrorCode());
    }

    // ========== Context Tests ==========

    public function testGetContextIncludesRuleName(): void
    {
        $exception = new BusinessRuleViolationException(
            'CREDIT_LIMIT_EXCEEDED',
            'Credit limit exceeded',
            ['customerId' => 'CUST-001']
        );

        $context = $exception->getContext();

        $this->assertArrayHasKey('ruleName', $context);
        $this->assertSame('CREDIT_LIMIT_EXCEEDED', $context['ruleName']);
        $this->assertArrayHasKey('customerId', $context);
    }

    public function testGetContextWithEmptyContext(): void
    {
        $exception = new BusinessRuleViolationException('RULE_123', 'test');
        $context = $exception->getContext();

        $this->assertArrayHasKey('ruleName', $context);
        $this->assertSame('RULE_123', $context['ruleName']);
    }

    // ========== Accessor Tests ==========

    public function testGetRuleName(): void
    {
        $exception = new BusinessRuleViolationException('CREDIT_LIMIT_EXCEEDED', 'test');

        $this->assertSame('CREDIT_LIMIT_EXCEEDED', $exception->getRuleName());
    }

    // ========== Throwable Behavior Tests ==========

    public function testCanBeCaughtAsDomainException(): void
    {
        $exception = new BusinessRuleViolationException('RULE_123', 'test');

        try {
            throw $exception;
        } catch (DomainException $e) {
            $this->assertSame($exception, $e);
        }
    }
}

final class ConcurrencyConflictExceptionTest extends KernelTestCase
{
    // ========== Creation Tests ==========

    public function testCreationWithAllParameters(): void
    {
        $exception = new ConcurrencyConflictException(
            'Order',
            'ORD-12345',
            5,
            8
        );

        $this->assertStringContainsString('Order', $exception->getMessage());
        $this->assertStringContainsString('ORD-12345', $exception->getMessage());
        $this->assertStringContainsString('5', $exception->getMessage());
        $this->assertStringContainsString('8', $exception->getMessage());
    }

    public function testCreationWithPreviousException(): void
    {
        $previous = new \RuntimeException('Previous error');
        $exception = new ConcurrencyConflictException(
            'Order',
            'ORD-12345',
            5,
            8,
            $previous
        );

        $this->assertSame($previous, $exception->getPrevious());
    }

    // ========== Error Code Tests ==========

    public function testGetErrorCode(): void
    {
        $exception = new ConcurrencyConflictException('Order', 'ORD-1', 1, 2);

        $this->assertSame('CONCURRENCY_CONFLICT', $exception->getErrorCode());
    }

    // ========== Context Tests ==========

    public function testGetContext(): void
    {
        $exception = new ConcurrencyConflictException('Order', 'ORD-12345', 5, 8);
        $context = $exception->getContext();

        $this->assertSame('Order', $context['aggregateType']);
        $this->assertSame('ORD-12345', $context['aggregateId']);
        $this->assertSame(5, $context['expectedVersion']);
        $this->assertSame(8, $context['actualVersion']);
    }

    // ========== Accessor Tests ==========

    public function testGetAggregateType(): void
    {
        $exception = new ConcurrencyConflictException('Customer', 'CUST-123', 1, 2);

        $this->assertSame('Customer', $exception->getAggregateType());
    }

    public function testGetAggregateId(): void
    {
        $exception = new ConcurrencyConflictException('Order', 'ORD-12345', 1, 2);

        $this->assertSame('ORD-12345', $exception->getAggregateId());
    }

    public function testGetExpectedVersion(): void
    {
        $exception = new ConcurrencyConflictException('Order', 'ORD-1', 10, 15);

        $this->assertSame(10, $exception->getExpectedVersion());
    }

    public function testGetActualVersion(): void
    {
        $exception = new ConcurrencyConflictException('Order', 'ORD-1', 5, 12);

        $this->assertSame(12, $exception->getActualVersion());
    }

    // ========== Message Format Tests ==========

    public function testMessageContainsAllInformation(): void
    {
        $exception = new ConcurrencyConflictException('Order', 'ORD-12345', 5, 8);

        $this->assertStringContainsString('Concurrency conflict', $exception->getMessage());
        $this->assertStringContainsString('Order', $exception->getMessage());
        $this->assertStringContainsString('ORD-12345', $exception->getMessage());
        $this->assertStringContainsString('expected version 5', $exception->getMessage());
        $this->assertStringContainsString('found 8', $exception->getMessage());
    }

    // ========== Throwable Behavior Tests ==========

    public function testCanBeCaughtAsKernelException(): void
    {
        $exception = new ConcurrencyConflictException('Order', 'ORD-1', 1, 2);

        try {
            throw $exception;
        } catch (KernelException $e) {
            $this->assertSame($exception, $e);
        }
    }
}

final class NotFoundExceptionTest extends KernelTestCase
{
    // ========== Creation Tests ==========

    public function testCreationWithResourceTypeAndId(): void
    {
        $exception = new NotFoundException('Order', 'ORD-12345');

        $this->assertStringContainsString('Order', $exception->getMessage());
        $this->assertStringContainsString('ORD-12345', $exception->getMessage());
        $this->assertStringContainsString('not found', $exception->getMessage());
    }

    public function testCreationWithPreviousException(): void
    {
        $previous = new \RuntimeException('Previous error');
        $exception = new NotFoundException('Order', 'ORD-12345', $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    // ========== Error Code Tests ==========

    public function testGetErrorCode(): void
    {
        $exception = new NotFoundException('Order', 'ORD-12345');

        $this->assertSame('RESOURCE_NOT_FOUND', $exception->getErrorCode());
    }

    // ========== Context Tests ==========

    public function testGetContext(): void
    {
        $exception = new NotFoundException('Order', 'ORD-12345');
        $context = $exception->getContext();

        $this->assertSame('Order', $context['resourceType']);
        $this->assertSame('ORD-12345', $context['resourceId']);
    }

    // ========== Accessor Tests ==========

    public function testGetResourceType(): void
    {
        $exception = new NotFoundException('Customer', 'CUST-12345');

        $this->assertSame('Customer', $exception->getResourceType());
    }

    public function testGetResourceId(): void
    {
        $exception = new NotFoundException('Order', 'ORD-67890');

        $this->assertSame('ORD-67890', $exception->getResourceId());
    }

    // ========== Message Format Tests ==========

    public function testMessageFormat(): void
    {
        $exception = new NotFoundException('Invoice', 'INV-2026-001');

        $this->assertSame('Invoice with ID "INV-2026-001" not found', $exception->getMessage());
    }

    // ========== Throwable Behavior Tests ==========

    public function testCanBeCaughtAsDomainException(): void
    {
        $exception = new NotFoundException('Order', 'ORD-12345');

        try {
            throw $exception;
        } catch (DomainException $e) {
            $this->assertSame($exception, $e);
        }
    }

    public function testCanBeCaughtAsKernelException(): void
    {
        $exception = new NotFoundException('Order', 'ORD-12345');

        try {
            throw $exception;
        } catch (KernelException $e) {
            $this->assertSame($exception, $e);
        }
    }
}
