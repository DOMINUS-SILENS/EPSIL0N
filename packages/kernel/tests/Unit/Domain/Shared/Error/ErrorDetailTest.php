<?php

declare(strict_types=1);

namespace Spiral\Kernel\Tests\Unit\Domain\Shared\Error;

use Spiral\Kernel\Application\Service\ErrorDetailFactory;
use Spiral\Kernel\Domain\Shared\Error\ErrorCode;
use Spiral\Kernel\Domain\Shared\Error\ErrorDetail;
use Spiral\Kernel\Support\Exception\ValidationException;
use Spiral\Kernel\Support\Exception\NotFoundException;
use Spiral\Kernel\Tests\KernelTestCase;

/**
 * Comprehensive tests for ErrorDetail value object.
 * Tests cover: creation, context management, serialization, and fromException factory.
 *
 * @package Spiral\Kernel\Tests\Unit\Domain\Shared\Error
 */
final class ErrorDetailTest extends KernelTestCase
{
    // ========== Basic Creation Tests ==========

    public function testCreateWithCodeAndMessage(): void
    {
        $error = ErrorDetail::create(ErrorCode::kernel('TEST_ERROR'), 'Test error message');

        $this->assertInstanceOf(ErrorDetail::class, $error);
        $this->assertSame('KERNEL.TEST_ERROR', $error->code()->code());
        $this->assertSame('Test error message', $error->message());
    }

    public function testCreateWithDomainErrorCode(): void
    {
        $error = ErrorDetail::create(ErrorCode::domainError('ORDER', 'CREDIT_LIMIT'), 'Credit limit exceeded');

        $this->assertSame('DOMAIN.ORDER.CREDIT_LIMIT', $error->code()->code());
        $this->assertTrue($error->code()->isDomainError());
    }

    public function testCreateWithValidationErrorCode(): void
    {
        $error = ErrorDetail::create(ErrorCode::validation('REQUIRED'), 'Field is required');

        $this->assertSame('VALIDATION.REQUIRED', $error->code()->code());
        $this->assertTrue($error->code()->isValidationError());
    }

    public function testCreateWithAuthErrorCode(): void
    {
        $error = ErrorDetail::create(ErrorCode::auth('ACCESS_DENIED'), 'Access denied');

        $this->assertSame('AUTH.ACCESS_DENIED', $error->code()->code());
        $this->assertTrue($error->code()->isAuthError());
    }

    // ========== Creation With Context Tests ==========

    public function testWithContextDataAddsContext(): void
    {
        $error = ErrorDetail::withContextData(
            ErrorCode::kernel('NOT_FOUND'),
            'Resource not found',
            ['resourceId' => '123', 'resourceType' => 'Order']
        );

        $this->assertSame(['resourceId' => '123', 'resourceType' => 'Order'], $error->context());
    }

    public function testWithContextDataWithEmptyContext(): void
    {
        $error = ErrorDetail::withContextData(
            ErrorCode::kernel('ERROR'),
            'Error',
            []
        );

        $this->assertSame([], $error->context());
    }

    // ========== Validation Failed Factory Tests ==========

    public function testValidationFailedCreatesValidationError(): void
    {
        $error = ErrorDetail::validationFailed(
            'Validation failed',
            ['email' => ['Invalid format'], 'password' => ['Too short']]
        );

        $this->assertTrue($error->code()->isValidationError());
        $this->assertSame('Validation failed', $error->message());
    }

    public function testValidationFailedIncludesFieldErrors(): void
    {
        $fieldErrors = [
            'email' => ['Invalid format', 'Already exists'],
            'password' => ['Too short'],
        ];

        $error = ErrorDetail::validationFailed('Validation failed', $fieldErrors);

        $this->assertSame($fieldErrors, $error->fieldErrors());
        $this->assertTrue($error->hasFieldErrors());
    }

    public function testValidationFailedIncludesFieldCountInContext(): void
    {
        $error = ErrorDetail::validationFailed(
            'Validation failed',
            ['field1' => ['Error'], 'field2' => ['Error']]
        );

        $this->assertSame(['fieldCount' => 2], $error->context());
    }

    public function testValidationFailedWithTraceIdentifier(): void
    {
        $error = ErrorDetail::validationFailed(
            'Validation failed',
            ['email' => ['Invalid']],
            'trace-123'
        );

        $this->assertSame('trace-123', $error->traceId());
    }

    public function testValidationFailedWithEmptyFieldErrors(): void
    {
        $error = ErrorDetail::validationFailed('Validation failed', []);

        $this->assertSame([], $error->fieldErrors());
        $this->assertFalse($error->hasFieldErrors());
        $this->assertSame(['fieldCount' => 0], $error->context());
    }
}

final class ErrorDetailFromExceptionTest extends KernelTestCase
{
    // ========== From ValidationException Tests ==========

    public function testFromExceptionWithValidationException(): void
    {
        $exception = new ValidationException(['email' => ['Invalid format']], 'Validation failed');
        $error = ErrorDetailFactory::fromException($exception);

        $this->assertSame('VALIDATION_FAILED', $error->code()->code());
        $this->assertSame('Validation failed', $error->message());
    }

    public function testFromExceptionPreservesContext(): void
    {
        $exception = new ValidationException(['email' => ['Invalid']], 'Validation failed');
        $error = ErrorDetailFactory::fromException($exception);

        $context = $error->context();
        $this->assertArrayHasKey('errors', $context);
        $this->assertArrayHasKey('fieldCount', $context);
    }

    // ========== From NotFoundException Tests ==========

    public function testFromExceptionWithNotFoundException(): void
    {
        $exception = new NotFoundException('Order', 'ORD-12345');
        $error = ErrorDetailFactory::fromException($exception);

        $this->assertSame('RESOURCE_NOT_FOUND', $error->code()->code());
        $this->assertStringContainsString('ORD-12345', $error->message());
    }

    public function testFromExceptionPreservesNotFoundContext(): void
    {
        $exception = new NotFoundException('Order', 'ORD-12345');
        $error = ErrorDetailFactory::fromException($exception);

        $this->assertSame('Order', $error->context()['resourceType']);
        $this->assertSame('ORD-12345', $error->context()['resourceId']);
    }

    // ========== FromException With Trace Identifiers Tests ==========

    public function testFromExceptionWithTraceId(): void
    {
        $exception = new NotFoundException('Order', 'ORD-12345');
        $error = ErrorDetailFactory::fromException($exception, 'trace-123');

        $this->assertSame('trace-123', $error->traceId());
    }

    public function testFromExceptionWithTraceAndCorrelationId(): void
    {
        $exception = new NotFoundException('Order', 'ORD-12345');
        $error = ErrorDetailFactory::fromException($exception, 'trace-123', 'corr-456');

        $this->assertSame('trace-123', $error->traceId());
        $this->assertSame('corr-456', $error->correlationId());
    }
}

final class ErrorDetailContextTest extends KernelTestCase
{
    // ========== Context Access Tests ==========

    public function testContextReturnsEmptyArrayByDefault(): void
    {
        $error = ErrorDetail::create(ErrorCode::kernel('ERROR'), 'Error');

        $this->assertSame([], $error->context());
    }

    public function testContextReturnsAddedContextData(): void
    {
        $error = ErrorDetail::withContextData(
            ErrorCode::kernel('NOT_FOUND'),
            'Not found',
            ['id' => '123', 'type' => 'Order']
        );

        $this->assertSame(['id' => '123', 'type' => 'Order'], $error->context());
    }

    // ========== WithAddedContext Tests ==========

    public function testWithAddedContextMergesNewData(): void
    {
        $error = ErrorDetail::withContextData(
            ErrorCode::kernel('ERROR'),
            'Error',
            ['key1' => 'value1']
        );

        $newError = $error->withAddedContext(['key2' => 'value2']);

        $this->assertSame(['key1' => 'value1', 'key2' => 'value2'], $newError->context());
    }

    public function testWithAddedContextOverwritesExistingKeys(): void
    {
        $error = ErrorDetail::withContextData(
            ErrorCode::kernel('ERROR'),
            'Error',
            ['key' => 'oldValue']
        );

        $newError = $error->withAddedContext(['key' => 'newValue']);

        $this->assertSame(['key' => 'newValue'], $newError->context());
    }

    public function testWithAddedContextPreservesOriginal(): void
    {
        $error = ErrorDetail::withContextData(
            ErrorCode::kernel('ERROR'),
            'Error',
            ['key' => 'value']
        );

        $newError = $error->withAddedContext(['newKey' => 'newValue']);

        // Original should be unchanged
        $this->assertSame(['key' => 'value'], $error->context());
        $this->assertSame(['key' => 'value', 'newKey' => 'newValue'], $newError->context());
    }

    public function testWithAddedContextReturnsNewInstance(): void
    {
        $error = ErrorDetail::create(ErrorCode::kernel('ERROR'), 'Error');
        $newError = $error->withAddedContext(['key' => 'value']);

        $this->assertNotSame($error, $newError);
    }

    // ========== Trace Identifiers Tests ==========

    public function testTraceIdIsNullByDefault(): void
    {
        $error = ErrorDetail::create(ErrorCode::kernel('ERROR'), 'Error');

        $this->assertNull($error->traceId());
    }

    public function testCorrelationIdIsNullByDefault(): void
    {
        $error = ErrorDetail::create(ErrorCode::kernel('ERROR'), 'Error');

        $this->assertNull($error->correlationId());
    }

    public function testWithTraceIdentifiersAddsBothIds(): void
    {
        $error = ErrorDetail::create(ErrorCode::kernel('ERROR'), 'Error');
        $newError = $error->withTraceIdentifiers('trace-123', 'corr-456');

        $this->assertSame('trace-123', $newError->traceId());
        $this->assertSame('corr-456', $newError->correlationId());
    }

    public function testWithTraceIdentifiersPreservesExistingCorrelation(): void
    {
        $error = ErrorDetail::create(ErrorCode::kernel('ERROR'), 'Error')
            ->withTraceIdentifiers('trace-1', 'corr-1');
        $newError = $error->withTraceIdentifiers('trace-2', null);

        $this->assertSame('trace-2', $newError->traceId());
        $this->assertSame('corr-1', $newError->correlationId()); // Preserved from original
    }

    public function testWithTraceIdentifiersOverwritesBoth(): void
    {
        $error = ErrorDetail::create(ErrorCode::kernel('ERROR'), 'Error')
            ->withTraceIdentifiers('trace-1', 'corr-1');
        $newError = $error->withTraceIdentifiers('trace-2', 'corr-2');

        $this->assertSame('trace-2', $newError->traceId());
        $this->assertSame('corr-2', $newError->correlationId());
    }

    public function testWithTraceIdentifiersReturnsNewInstance(): void
    {
        $error = ErrorDetail::create(ErrorCode::kernel('ERROR'), 'Error');
        $newError = $error->withTraceIdentifiers('trace-123', 'corr-456');

        $this->assertNotSame($error, $newError);
    }
}

final class ErrorDetailFieldErrorsTest extends KernelTestCase
{
    // ========== Field Errors Access Tests ==========

    public function testFieldErrorsReturnsEmptyArrayByDefault(): void
    {
        $error = ErrorDetail::create(ErrorCode::kernel('ERROR'), 'Error');

        $this->assertSame([], $error->fieldErrors());
    }

    public function testFieldErrorsReturnsValidationErrors(): void
    {
        $fieldErrors = [
            'email' => ['Invalid format'],
            'password' => ['Too short', 'Must contain number'],
        ];
        $error = ErrorDetail::validationFailed('Validation failed', $fieldErrors);

        $this->assertSame($fieldErrors, $error->fieldErrors());
    }

    // ========== HasFieldErrors Tests ==========

    public function testHasFieldErrorsReturnsFalseWhenNoFieldErrors(): void
    {
        $error = ErrorDetail::create(ErrorCode::kernel('ERROR'), 'Error');

        $this->assertFalse($error->hasFieldErrors());
    }

    public function testHasFieldErrorsReturnsTrueWhenFieldErrorsExist(): void
    {
        $error = ErrorDetail::validationFailed('Validation failed', ['email' => ['Invalid']]);

        $this->assertTrue($error->hasFieldErrors());
    }

    public function testHasFieldErrorsReturnsFalseForEmptyFieldErrorsArray(): void
    {
        $error = ErrorDetail::validationFailed('Validation failed', []);

        $this->assertFalse($error->hasFieldErrors());
    }
}

final class ErrorDetailSerializationTest extends KernelTestCase
{
    // ========== ToArray Basic Tests ==========

    public function testToArrayContainsCodeAndMessage(): void
    {
        $error = ErrorDetail::create(ErrorCode::kernel('NOT_FOUND'), 'Resource not found');
        $array = $error->toArray();

        $this->assertArrayHasKey('code', $array);
        $this->assertArrayHasKey('message', $array);
        $this->assertSame('KERNEL.NOT_FOUND', $array['code']);
        $this->assertSame('Resource not found', $array['message']);
    }

    public function testToArrayOmitsEmptyContext(): void
    {
        $error = ErrorDetail::create(ErrorCode::kernel('ERROR'), 'Error');
        $array = $error->toArray();

        $this->assertArrayNotHasKey('context', $array);
    }

    public function testToArrayIncludesContextWhenPresent(): void
    {
        $error = ErrorDetail::withContextData(
            ErrorCode::kernel('NOT_FOUND'),
            'Not found',
            ['id' => '123']
        );
        $array = $error->toArray();

        $this->assertArrayHasKey('context', $array);
        $this->assertSame(['id' => '123'], $array['context']);
    }

    public function testToArrayOmitsEmptyFieldErrors(): void
    {
        $error = ErrorDetail::create(ErrorCode::kernel('ERROR'), 'Error');
        $array = $error->toArray();

        $this->assertArrayNotHasKey('fieldErrors', $array);
    }

    public function testToArrayIncludesFieldErrorsWhenPresent(): void
    {
        $error = ErrorDetail::validationFailed(
            'Validation failed',
            ['email' => ['Invalid']]
        );
        $array = $error->toArray();

        $this->assertArrayHasKey('fieldErrors', $array);
        $this->assertSame(['email' => ['Invalid']], $array['fieldErrors']);
    }

    public function testToArrayOmitsNullTraceId(): void
    {
        $error = ErrorDetail::create(ErrorCode::kernel('ERROR'), 'Error');
        $array = $error->toArray();

        $this->assertArrayNotHasKey('traceId', $array);
    }

    public function testToArrayIncludesTraceIdWhenPresent(): void
    {
        $error = ErrorDetail::create(ErrorCode::kernel('ERROR'), 'Error')
            ->withTraceIdentifiers('trace-123', null);
        $array = $error->toArray();

        $this->assertArrayHasKey('traceId', $array);
        $this->assertSame('trace-123', $array['traceId']);
    }

    public function testToArrayOmitsNullCorrelationId(): void
    {
        $error = ErrorDetail::create(ErrorCode::kernel('ERROR'), 'Error');
        $array = $error->toArray();

        $this->assertArrayNotHasKey('correlationId', $array);
    }

    public function testToArrayIncludesCorrelationIdWhenPresent(): void
    {
        $error = ErrorDetail::create(ErrorCode::kernel('ERROR'), 'Error')
            ->withTraceIdentifiers('trace-123', 'corr-456');
        $array = $error->toArray();

        $this->assertArrayHasKey('correlationId', $array);
        $this->assertSame('corr-456', $array['correlationId']);
    }

    // ========== Complex Serialization Tests ==========

    public function testToArrayWithAllFields(): void
    {
        $error = ErrorDetail::validationFailed(
            'Validation failed',
            ['email' => ['Invalid format']],
            'trace-123'
        )->withAddedContext(['requestId' => 'req-456'])
         ->withTraceIdentifiers('trace-123', 'corr-789');

        $array = $error->toArray();

        $this->assertSame('VALIDATION.FAILED', $array['code']);
        $this->assertSame('Validation failed', $array['message']);
        $this->assertSame(['fieldCount' => 1, 'requestId' => 'req-456'], $array['context']);
        $this->assertSame(['email' => ['Invalid format']], $array['fieldErrors']);
        $this->assertSame('trace-123', $array['traceId']);
        $this->assertSame('corr-789', $array['correlationId']);
    }
}

final class ErrorDetailImmutabilityTest extends KernelTestCase
{
    // ========== Immutability Tests ==========

    public function testErrorDetailIsImmutable(): void
    {
        $reflection = new \ReflectionClass(ErrorDetail::class);
        $constructor = $reflection->getConstructor();

        $this->assertNotNull($constructor);
        $this->assertTrue($constructor->isPrivate());
    }

    public function testWithAddedContextReturnsNewInstance(): void
    {
        $error = ErrorDetail::create(ErrorCode::kernel('ERROR'), 'Error');
        $newError = $error->withAddedContext(['key' => 'value']);

        $this->assertNotSame($error, $newError);
        $this->assertSame([], $error->context());
        $this->assertSame(['key' => 'value'], $newError->context());
    }

    public function testWithTraceIdentifiersReturnsNewInstance(): void
    {
        $error = ErrorDetail::create(ErrorCode::kernel('ERROR'), 'Error');
        $newError = $error->withTraceIdentifiers('trace-123', 'corr-456');

        $this->assertNotSame($error, $newError);
        $this->assertNull($error->traceId());
        $this->assertSame('trace-123', $newError->traceId());
    }

    public function testPropertiesAreReadonly(): void
    {
        $error = ErrorDetail::create(ErrorCode::kernel('ERROR'), 'Error');
        $reflection = new \ReflectionClass(ErrorDetail::class);

        foreach ($reflection->getProperties() as $property) {
            $this->assertTrue($property->isReadOnly());
        }
    }
}
