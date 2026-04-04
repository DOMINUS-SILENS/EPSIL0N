<?php

declare(strict_types=1);

namespace Spiral\Kernel\Tests\Unit\Domain\Shared;

use Spiral\Kernel\Application\Service\ErrorDetailFactory;
use Spiral\Kernel\Domain\Shared\Result\Result;
use Spiral\Kernel\Domain\Shared\Result\Success;
use Spiral\Kernel\Domain\Shared\Result\Failure;
use Spiral\Kernel\Domain\Shared\Error\ErrorCode;
use Spiral\Kernel\Domain\Shared\Error\ErrorDetail;
use Spiral\Kernel\Support\Exception\ValidationException;
use Spiral\Kernel\Support\Exception\ConcurrencyConflictException;
use Spiral\Kernel\Tests\KernelTestCase;

/**
 * Comprehensive tests for Result monad and Error types.
 * Tests cover: creation, transformations, pattern matching, immutability, and error handling.
 */
final class ErrorCodeTest extends KernelTestCase
{
    // ========== Factory Method Tests ==========

    public function testKernelFactoryCreatesKernelErrorCode(): void
    {
        $code = ErrorCode::kernel('TEST_ERROR');

        $this->assertSame('KERNEL.TEST_ERROR', $code->code());
        $this->assertSame('KERNEL', $code->domain());
        $this->assertTrue($code->isKernelError());
    }

    public function testDomainErrorFactoryCreatesDomainErrorCode(): void
    {
        $code = ErrorCode::domainError('CUSTOMER', 'CREDIT_LIMIT_EXCEEDED');

        $this->assertSame('DOMAIN.CUSTOMER.CREDIT_LIMIT_EXCEEDED', $code->code());
        $this->assertSame('DOMAIN', $code->domain());
        $this->assertTrue($code->isDomainError());
    }

    public function testValidationFactoryCreatesValidationErrorCode(): void
    {
        $code = ErrorCode::validation('REQUIRED');

        $this->assertSame('VALIDATION.REQUIRED', $code->code());
        $this->assertSame('VALIDATION', $code->domain());
        $this->assertTrue($code->isValidationError());
    }

    public function testAuthFactoryCreatesAuthErrorCode(): void
    {
        $code = ErrorCode::auth('ACCESS_DENIED');

        $this->assertSame('AUTH.ACCESS_DENIED', $code->code());
        $this->assertSame('AUTH', $code->domain());
        $this->assertTrue($code->isAuthError());
    }

    public function testFromStringCreatesErrorCode(): void
    {
        $code = ErrorCode::fromString('DOMAIN.ORDER.INVALID_STATUS');

        $this->assertSame('DOMAIN.ORDER.INVALID_STATUS', $code->code());
        $this->assertSame('DOMAIN', $code->domain());
        $this->assertTrue($code->isDomainError());
    }

    public function testFromStringParsesSinglePartCode(): void
    {
        $code = ErrorCode::fromString('KERNEL');

        $this->assertSame('KERNEL', $code->code());
        $this->assertSame('KERNEL', $code->domain());
    }

    // ========== Predefined Constant Tests ==========

    public function testConcurrencyConflictConstant(): void
    {
        $this->assertSame('KERNEL.CONCURRENCY_CONFLICT', ErrorCode::CONCURRENCY_CONFLICT);
    }

    public function testNotFoundConstant(): void
    {
        $this->assertSame('KERNEL.NOT_FOUND', ErrorCode::NOT_FOUND);
    }

    public function testInvalidStateConstant(): void
    {
        $this->assertSame('KERNEL.INVALID_STATE', ErrorCode::INVALID_STATE);
    }

    public function testOperationFailedConstant(): void
    {
        $this->assertSame('KERNEL.OPERATION_FAILED', ErrorCode::OPERATION_FAILED);
    }

    // ========== Domain Classification Tests ==========

    public function testIsKernelErrorReturnsFalseForOtherDomains(): void
    {
        $this->assertFalse(ErrorCode::validation('TEST')->isKernelError());
        $this->assertFalse(ErrorCode::auth('TEST')->isKernelError());
        $this->assertFalse(ErrorCode::domainError('CTX', 'TEST')->isKernelError());
    }

    public function testIsDomainErrorReturnsFalseForOtherDomains(): void
    {
        $this->assertFalse(ErrorCode::kernel('TEST')->isDomainError());
        $this->assertFalse(ErrorCode::validation('TEST')->isDomainError());
        $this->assertFalse(ErrorCode::auth('TEST')->isDomainError());
    }

    public function testIsValidationErrorReturnsFalseForOtherDomains(): void
    {
        $this->assertFalse(ErrorCode::kernel('TEST')->isValidationError());
        $this->assertFalse(ErrorCode::auth('TEST')->isValidationError());
        $this->assertFalse(ErrorCode::domainError('CTX', 'TEST')->isValidationError());
    }

    public function testIsAuthErrorReturnsFalseForOtherDomains(): void
    {
        $this->assertFalse(ErrorCode::kernel('TEST')->isAuthError());
        $this->assertFalse(ErrorCode::validation('TEST')->isAuthError());
        $this->assertFalse(ErrorCode::domainError('CTX', 'TEST')->isAuthError());
    }

    // ========== String Representation Tests ==========

    public function testToStringReturnsCode(): void
    {
        $code = ErrorCode::kernel('TEST');

        $this->assertSame('KERNEL.TEST', (string) $code);
    }

    // ========== Equality Tests ==========

    public function testEqualsReturnsTrueForSameCode(): void
    {
        $code1 = ErrorCode::kernel('TEST');
        $code2 = ErrorCode::kernel('TEST');

        $this->assertTrue($code1->equals($code2));
    }

    public function testEqualsReturnsFalseForDifferentCode(): void
    {
        $code1 = ErrorCode::kernel('TEST1');
        $code2 = ErrorCode::kernel('TEST2');

        $this->assertFalse($code1->equals($code2));
    }

    public function testEqualsReturnsFalseForDifferentDomains(): void
    {
        $code1 = ErrorCode::kernel('TEST');
        $code2 = ErrorCode::validation('TEST');

        $this->assertFalse($code1->equals($code2));
    }
}

final class ErrorDetailTest extends KernelTestCase
{
    // ========== Factory Method Tests ==========

    public function testCreateBasicErrorDetail(): void
    {
        $code = ErrorCode::kernel('TEST_ERROR');
        $detail = ErrorDetail::create($code, 'Test error message');

        $this->assertSame($code, $detail->code());
        $this->assertSame('Test error message', $detail->message());
        $this->assertSame([], $detail->context());
        $this->assertSame([], $detail->fieldErrors());
        $this->assertFalse($detail->hasFieldErrors());
        $this->assertNull($detail->traceId());
        $this->assertNull($detail->correlationId());
    }

    public function testWithContextDataCreatesErrorDetailWithContext(): void
    {
        $context = ['userId' => '123', 'action' => 'delete'];
        $detail = ErrorDetail::withContextData(
            ErrorCode::kernel('TEST'),
            'Test error',
            $context
        );

        $this->assertSame($context, $detail->context());
    }

    public function testValidationFailedCreatesValidationError(): void
    {
        $fieldErrors = [
            'email' => ['Email is required', 'Email must be valid'],
            'password' => ['Password is too short'],
        ];

        $detail = ErrorDetail::validationFailed(
            'Validation failed',
            $fieldErrors,
            'trace-123'
        );

        $this->assertTrue($detail->hasFieldErrors());
        $this->assertSame($fieldErrors, $detail->fieldErrors());
        $this->assertSame('trace-123', $detail->traceId());
        $this->assertTrue($detail->code()->isValidationError());
    }

    public function testFromExceptionCreatesErrorDetail(): void
    {
        $exception = new ValidationException(['email' => ['Invalid email']]);
        $detail = ErrorDetailFactory::fromException($exception, 'trace-456', 'correlation-789');

        $this->assertSame('VALIDATION_FAILED', $detail->code()->code());
        $this->assertSame('Validation failed', $detail->message());
        $this->assertSame('trace-456', $detail->traceId());
        $this->assertSame('correlation-789', $detail->correlationId());
    }

    public function testFromExceptionPreservesContext(): void
    {
        $exception = new ConcurrencyConflictException('Order', 'ORD-123', 5, 8);
        $detail = ErrorDetailFactory::fromException($exception);

        $context = $detail->context();

        $this->assertSame('Order', $context['aggregateType']);
        $this->assertSame('ORD-123', $context['aggregateId']);
        $this->assertSame(5, $context['expectedVersion']);
        $this->assertSame(8, $context['actualVersion']);
    }

    // ========== Immutability Tests ==========

    public function testWithAddedContextReturnsNewInstance(): void
    {
        $original = ErrorDetail::create(ErrorCode::kernel('TEST'), 'Test');
        $enhanced = $original->withAddedContext(['userId' => '123']);

        $this->assertNotSame($original, $enhanced);
        $this->assertSame([], $original->context());
        $this->assertSame(['userId' => '123'], $enhanced->context());
    }

    public function testWithAddedContextMergesContext(): void
    {
        $original = ErrorDetail::withContextData(
            ErrorCode::kernel('TEST'),
            'Test',
            ['action' => 'delete']
        );
        $enhanced = $original->withAddedContext(['userId' => '123']);

        $this->assertSame(['action' => 'delete', 'userId' => '123'], $enhanced->context());
    }

    public function testWithTraceIdentifiersReturnsNewInstance(): void
    {
        $original = ErrorDetail::create(ErrorCode::kernel('TEST'), 'Test');
        $enhanced = $original->withTraceIdentifiers('trace-123', 'correlation-456');

        $this->assertNotSame($original, $enhanced);
        $this->assertNull($original->traceId());
        $this->assertNull($original->correlationId());
        $this->assertSame('trace-123', $enhanced->traceId());
        $this->assertSame('correlation-456', $enhanced->correlationId());
    }

    public function testWithTraceIdentifiersPreservesCorrelationId(): void
    {
        $original = ErrorDetail::create(ErrorCode::kernel('TEST'), 'Test')
            ->withTraceIdentifiers('trace-1', 'correlation-1');

        $enhanced = $original->withTraceIdentifiers('trace-2');

        $this->assertSame('trace-2', $enhanced->traceId());
        $this->assertSame('correlation-1', $enhanced->correlationId());
    }

    // ========== Serialization Tests ==========

    public function testToArrayReturnsBasicStructure(): void
    {
        $detail = ErrorDetail::create(ErrorCode::kernel('TEST'), 'Test error');
        $array = $detail->toArray();

        $this->assertArrayHasKey('code', $array);
        $this->assertArrayHasKey('message', $array);
        $this->assertSame('KERNEL.TEST', $array['code']);
        $this->assertSame('Test error', $array['message']);
        $this->assertArrayNotHasKey('context', $array);
        $this->assertArrayNotHasKey('fieldErrors', $array);
        $this->assertArrayNotHasKey('traceId', $array);
        $this->assertArrayNotHasKey('correlationId', $array);
    }

    public function testToArrayIncludesContext(): void
    {
        $detail = ErrorDetail::withContextData(
            ErrorCode::kernel('TEST'),
            'Test',
            ['userId' => '123']
        );
        $array = $detail->toArray();

        $this->assertArrayHasKey('context', $array);
        $this->assertSame(['userId' => '123'], $array['context']);
    }

    public function testToArrayIncludesFieldErrors(): void
    {
        $detail = ErrorDetail::validationFailed('Test', ['email' => ['Invalid']]);
        $array = $detail->toArray();

        $this->assertArrayHasKey('fieldErrors', $array);
        $this->assertSame(['email' => ['Invalid']], $array['fieldErrors']);
    }

    public function testToArrayIncludesTraceIdentifiers(): void
    {
        $detail = ErrorDetail::create(ErrorCode::kernel('TEST'), 'Test')
            ->withTraceIdentifiers('trace-123', 'correlation-456');
        $array = $detail->toArray();

        $this->assertArrayHasKey('traceId', $array);
        $this->assertArrayHasKey('correlationId', $array);
        $this->assertSame('trace-123', $array['traceId']);
        $this->assertSame('correlation-456', $array['correlationId']);
    }

    // ========== Accessor Tests ==========

    public function testCodeReturnsErrorCode(): void
    {
        $code = ErrorCode::domainError('ORDER', 'INVALID_STATUS');
        $detail = ErrorDetail::create($code, 'Test');

        $this->assertSame($code, $detail->code());
    }

    public function testMessageReturnsMessage(): void
    {
        $detail = ErrorDetail::create(ErrorCode::kernel('TEST'), 'Error message here');

        $this->assertSame('Error message here', $detail->message());
    }

    public function testContextReturnsContext(): void
    {
        $context = ['key1' => 'value1', 'key2' => 'value2'];
        $detail = ErrorDetail::withContextData(ErrorCode::kernel('TEST'), 'Test', $context);

        $this->assertSame($context, $detail->context());
    }

    public function testFieldErrorsReturnsFieldErrors(): void
    {
        $fieldErrors = ['field1' => ['error1', 'error2']];
        $detail = ErrorDetail::validationFailed('Test', $fieldErrors);

        $this->assertSame($fieldErrors, $detail->fieldErrors());
    }

    public function testHasFieldErrorsReturnsTrueWhenPresent(): void
    {
        $detail = ErrorDetail::validationFailed('Test', ['email' => ['Invalid']]);

        $this->assertTrue($detail->hasFieldErrors());
    }

    public function testHasFieldErrorsReturnsFalseWhenEmpty(): void
    {
        $detail = ErrorDetail::create(ErrorCode::kernel('TEST'), 'Test');

        $this->assertFalse($detail->hasFieldErrors());
    }

    public function testTraceIdReturnsTraceId(): void
    {
        $detail = ErrorDetail::create(ErrorCode::kernel('TEST'), 'Test')
            ->withTraceIdentifiers('trace-123');

        $this->assertSame('trace-123', $detail->traceId());
    }

    public function testCorrelationIdReturnsCorrelationId(): void
    {
        $detail = ErrorDetail::create(ErrorCode::kernel('TEST'), 'Test')
            ->withTraceIdentifiers('trace-123', 'correlation-456');

        $this->assertSame('correlation-456', $detail->correlationId());
    }
}

final class ResultMonadTest extends KernelTestCase
{
    // ========== Success Tests ==========

    public function testSuccessFactoryCreatesSuccessResult(): void
    {
        $result = Result::success(42);

        $this->assertInstanceOf(Success::class, $result);
        $this->assertTrue($result->isSuccess());
        $this->assertFalse($result->isFailure());
    }

    public function testSuccessUnwrapReturnsValue(): void
    {
        $result = Result::success('test value');

        $this->assertSame('test value', $result->unwrap());
    }

    public function testSuccessUnwrapOrReturnsValue(): void
    {
        $result = Result::success('test value');

        $this->assertSame('test value', $result->unwrapOr('default'));
    }

    public function testSuccessErrorThrowsLogicException(): void
    {
        $result = Result::success(42);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot get error from a successful result');

        $result->error();
    }

    // ========== Failure Tests ==========

    public function testFailureFactoryCreatesFailureResult(): void
    {
        $error = ErrorDetail::create(ErrorCode::kernel('TEST'), 'Test error');
        $result = Result::failure($error);

        $this->assertInstanceOf(Failure::class, $result);
        $this->assertTrue($result->isFailure());
        $this->assertFalse($result->isSuccess());
    }

    public function testFailureErrorReturnsErrorDetail(): void
    {
        $error = ErrorDetail::create(ErrorCode::kernel('TEST'), 'Test error');
        $result = Result::failure($error);

        $this->assertSame($error, $result->error());
    }

    public function testFailureUnwrapThrowsLogicException(): void
    {
        $error = ErrorDetail::create(ErrorCode::kernel('TEST'), 'Test error message');
        $result = Result::failure($error);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot unwrap a failed result');

        $result->unwrap();
    }

    public function testFailureUnwrapOrReturnsDefault(): void
    {
        $error = ErrorDetail::create(ErrorCode::kernel('TEST'), 'Test error');
        $result = Result::failure($error);

        $this->assertSame('default value', $result->unwrapOr('default value'));
    }

    // ========== Map Tests ==========

    public function testMapTransformsSuccessValue(): void
    {
        $result = Result::success(5)
            ->map(fn(int $x): int => $x * 2);

        $this->assertTrue($result->isSuccess());
        $this->assertSame(10, $result->unwrap());
    }

    public function testMapPreservesFailure(): void
    {
        $error = ErrorDetail::create(ErrorCode::kernel('TEST'), 'Test error');
        $result = Result::failure($error)
            ->map(fn($x) => $x * 2);

        $this->assertTrue($result->isFailure());
        $this->assertSame($error, $result->error());
    }

    public function testMapCanChangeType(): void
    {
        $result = Result::success(42)
            ->map(fn(int $x): string => "value: $x");

        $this->assertTrue($result->isSuccess());
        $this->assertSame('value: 42', $result->unwrap());
    }

    // ========== FlatMap Tests ==========

    public function testFlatMapChainsSuccessResults(): void
    {
        $result = Result::success(5)
            ->flatMap(fn(int $x): Result => Result::success($x * 2))
            ->flatMap(fn(int $x): Result => Result::success($x + 3));

        $this->assertTrue($result->isSuccess());
        $this->assertSame(13, $result->unwrap());
    }

    public function testFlatMapShortCircuitsOnFailure(): void
    {
        $error = ErrorDetail::create(ErrorCode::kernel('TEST'), 'Test error');
        $called = false;

        $result = Result::failure($error)
            ->flatMap(function($x) use (&$called): Result {
                $called = true;
                return Result::success($x * 2);
            });

        $this->assertFalse($called);
        $this->assertTrue($result->isFailure());
        $this->assertSame($error, $result->error());
    }

    public function testFlatMapCanReturnFailure(): void
    {
        $error = ErrorDetail::create(ErrorCode::kernel('NEGATIVE'), 'Value is negative');

        $result = Result::success(-5)
            /** @phpstan-ignore-next-line Template covariance issue */
            ->flatMap(fn(int $x): Result => $x < 0
                ? Result::failure($error)
                : Result::success($x * 2)
            );

        $this->assertTrue($result->isFailure());
        $this->assertSame($error, $result->error());
    }

    // ========== OnSuccess Tests ==========

    public function testOnSuccessExecutesCallbackOnSuccess(): void
    {
        $called = false;
        $receivedValue = null;

        $result = Result::success(42)
            ->onSuccess(function($value) use (&$called, &$receivedValue): void {
                $called = true;
                $receivedValue = $value;
            });

        $this->assertTrue($called);
        $this->assertSame(42, $receivedValue);
        $this->assertTrue($result->isSuccess());
    }

    public function testOnSuccessDoesNotExecuteOnFailure(): void
    {
        $called = false;
        $error = ErrorDetail::create(ErrorCode::kernel('TEST'), 'Test error');

        $result = Result::failure($error)
            ->onSuccess(function() use (&$called): void {
                $called = true;
            });

        $this->assertFalse($called);
        $this->assertTrue($result->isFailure());
    }

    public function testOnSuccessReturnsSameResult(): void
    {
        $original = Result::success(42);
        $returned = $original->onSuccess(fn($v) => null);

        $this->assertSame($original, $returned);
    }

    // ========== OnFailure Tests ==========

    public function testOnFailureExecutesCallbackOnFailure(): void
    {
        $called = false;
        $receivedError = null;
        $error = ErrorDetail::create(ErrorCode::kernel('TEST'), 'Test error');

        $result = Result::failure($error)
            ->onFailure(function($err) use (&$called, &$receivedError): void {
                $called = true;
                $receivedError = $err;
            });

        $this->assertTrue($called);
        $this->assertSame($error, $receivedError);
        $this->assertTrue($result->isFailure());
    }

    public function testOnFailureDoesNotExecuteOnSuccess(): void
    {
        $called = false;

        $result = Result::success(42)
            ->onFailure(function() use (&$called): void {
                $called = true;
            });

        $this->assertFalse($called);
        $this->assertTrue($result->isSuccess());
    }

    public function testOnFailureReturnsSameResult(): void
    {
        $error = ErrorDetail::create(ErrorCode::kernel('TEST'), 'Test error');
        $original = Result::failure($error);
        $returned = $original->onFailure(fn($e) => null);

        $this->assertSame($original, $returned);
    }

    // ========== Match Tests ==========

    public function testMatchExecutesSuccessHandlerOnSuccess(): void
    {
        $result = Result::success(42);

        $value = $result->match(
            fn($v) => "Success: $v",
            fn($e) => "Error: " . $e->message()
        );

        $this->assertSame('Success: 42', $value);
    }

    public function testMatchExecutesFailureHandlerOnFailure(): void
    {
        $error = ErrorDetail::create(ErrorCode::kernel('TEST'), 'Test error message');
        $result = Result::failure($error);

        $value = $result->match(
            /** @phpstan-ignore-next-line */
            fn($v) => "Success: $v",
            fn($e) => "Error: " . $e->message()
        );

        $this->assertSame('Error: Test error message', $value);
    }

    public function testMatchCanReturnDifferentTypes(): void
    {
        $result = Result::success(42);

        $value = $result->match(
            fn($v) => ['status' => 'ok', 'data' => $v],
            fn($e) => ['status' => 'error', 'message' => $e->message()]
        );

        $this->assertSame(['status' => 'ok', 'data' => 42], $value);
    }

    // ========== Chaining Tests ==========

    public function testChainingMultipleOperations(): void
    {
        $log = [];

        $result = Result::success(5)
            ->onSuccess(function ($v) use (&$log): void {
                $log[] = "Starting with $v";
            })
            ->map(fn($v) => $v * 2)
            ->onSuccess(function ($v) use (&$log): void {
                $log[] = "After map: $v";
            })
            ->flatMap(fn($v) => Result::success($v + 10))
            ->onSuccess(function ($v) use (&$log): void {
                $log[] = "After flatMap: $v";
            })
            ->onFailure(function ($e) use (&$log): void {
                $log[] = "Error: " . $e->message();
            });

        $this->assertTrue($result->isSuccess());
        $this->assertSame(20, $result->unwrap());
        $this->assertSame([
            'Starting with 5',
            'After map: 10',
            'After flatMap: 20',
        ], $log);
    }

    public function testChainingStopsOnFailure(): void
    {
        $log = [];
        $error = ErrorDetail::create(ErrorCode::kernel('TEST'), 'Test error');

        $result = Result::failure($error)
            ->onSuccess(function ($v) use (&$log): void {
                /** @phpstan-ignore-next-line */
                $log[] = "Success: $v";
            })
            ->map(fn($v) => $v * 2)
            ->onSuccess(function ($v) use (&$log): void {
                $log[] = "After map: $v";
            })
            ->onFailure(function ($e) use (&$log): void {
                $log[] = "Error: " . $e->message();
            });

        $this->assertTrue($result->isFailure());
        $this->assertSame(['Error: Test error'], $log);
    }

    // ========== Type Safety Tests ==========

    public function testSuccessCanHoldNull(): void
    {
        $result = Result::success(null);

        $this->assertTrue($result->isSuccess());
        $this->assertNull($result->unwrap());
    }

    public function testSuccessCanHoldArray(): void
    {
        $result = Result::success(['key' => 'value']);

        $this->assertTrue($result->isSuccess());
        $this->assertSame(['key' => 'value'], $result->unwrap());
    }

    public function testSuccessCanHoldObject(): void
    {
        $obj = new \stdClass();
        $obj->property = 'value';

        $result = Result::success($obj);

        $this->assertTrue($result->isSuccess());
        $this->assertSame($obj, $result->unwrap());
    }

    // ========== Immutability Tests ==========

    public function testSuccessIsImmutable(): void
    {
        $result = Result::success(42);

        // Operations should return new instances
        $mapped = $result->map(fn($v) => $v * 2);

        $this->assertNotSame($result, $mapped);
        $this->assertSame(42, $result->unwrap());
        $this->assertSame(84, $mapped->unwrap());
    }

    public function testFailureIsImmutable(): void
    {
        $error = ErrorDetail::create(ErrorCode::kernel('TEST'), 'Test');
        $result = Result::failure($error);

        // Operations should return same instance (failure short-circuits)
        $mapped = $result->map(fn($v) => $v * 2);

        $this->assertSame($result, $mapped);
    }
}
