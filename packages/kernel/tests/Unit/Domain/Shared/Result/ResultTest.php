<?php

declare(strict_types=1);

namespace Spiral\Kernel\Tests\Unit\Domain\Shared\Result;

use Spiral\Kernel\Domain\Shared\Error\ErrorCode;
use Spiral\Kernel\Domain\Shared\Error\ErrorDetail;
use Spiral\Kernel\Domain\Shared\Result\Result;
use Spiral\Kernel\Domain\Shared\Result\Success;
use Spiral\Kernel\Domain\Shared\Result\Failure;
use Spiral\Kernel\Tests\KernelTestCase;

/**
 * Comprehensive tests for Result monad.
 * Tests cover: creation, success/failure paths, transformations, and monad laws.
 *
 * @package Spiral\Kernel\Tests\Unit\Domain\Shared\Result
 */
final class ResultCreationTest extends KernelTestCase
{
    // ========== Success Creation Tests ==========

    public function testSuccessCreatesSuccessInstance(): void
    {
        $result = Result::success('test value');

        $this->assertInstanceOf(Success::class, $result);
        $this->assertTrue($result->isSuccess());
        $this->assertFalse($result->isFailure());
    }

    public function testSuccessWithStringValue(): void
    {
        $result = Result::success('hello world');

        $this->assertSame('hello world', $result->unwrap());
    }

    public function testSuccessWithArrayValue(): void
    {
        $data = ['id' => 1, 'name' => 'Test'];
        $result = Result::success($data);

        $this->assertSame($data, $result->unwrap());
    }

    public function testSuccessWithObjectValue(): void
    {
        $object = new \stdClass();
        $object->name = 'Test';
        $result = Result::success($object);

        $this->assertSame($object, $result->unwrap());
    }

    public function testSuccessWithNullValue(): void
    {
        $result = Result::success(null);

        $this->assertNull($result->unwrap());
    }

    public function testSuccessWithIntegerValue(): void
    {
        $result = Result::success(42);

        $this->assertSame(42, $result->unwrap());
    }

    public function testSuccessWithBooleanValue(): void
    {
        $result = Result::success(true);

        $this->assertTrue($result->unwrap());
    }

    // ========== Failure Creation Tests ==========

    public function testFailureCreatesFailureInstance(): void
    {
        $error = ErrorDetail::create(ErrorCode::kernel('TEST_ERROR'), 'Test error');
        $result = Result::failure($error);

        $this->assertInstanceOf(Failure::class, $result);
        $this->assertFalse($result->isSuccess());
        $this->assertTrue($result->isFailure());
    }

    public function testFailureWithErrorDetail(): void
    {
        $error = ErrorDetail::create(ErrorCode::kernel('NOT_FOUND'), 'Resource not found');
        $result = Result::failure($error);

        $this->assertSame($error, $result->error());
    }

    public function testFailurePreservesErrorCode(): void
    {
        $error = ErrorDetail::create(ErrorCode::domainError('ORDER', 'CREDIT_LIMIT'), 'Credit limit exceeded');
        $result = Result::failure($error);

        $this->assertSame('DOMAIN.ORDER.CREDIT_LIMIT', $result->error()->code()->code());
    }

    public function testFailureWithValidationError(): void
    {
        $error = ErrorDetail::validationFailed('Validation failed', ['email' => ['Invalid format']]);
        $result = Result::failure($error);

        $this->assertTrue($result->error()->hasFieldErrors());
        $this->assertSame(['email' => ['Invalid format']], $result->error()->fieldErrors());
    }
}

final class ResultSuccessOperationsTest extends KernelTestCase
{
    // ========== Unwrap Tests ==========

    public function testUnwrapReturnsValueOnSuccess(): void
    {
        $result = Result::success('test value');

        $this->assertSame('test value', $result->unwrap());
    }

    public function testUnwrapOrReturnsValueOnSuccess(): void
    {
        $result = Result::success('actual value');

        $this->assertSame('actual value', $result->unwrapOr('default'));
    }

    public function testUnwrapOrIgnoresDefaultOnSuccess(): void
    {
        $result = Result::success(100);

        // Default should be ignored
        $this->assertSame(100, $result->unwrapOr(999));
    }

    // ========== Error Access Tests ==========

    public function testErrorThrowsOnSuccess(): void
    {
        $result = Result::success('value');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot get error from a successful result');

        $result->error();
    }

    // ========== Map Tests ==========

    public function testMapTransformsValueOnSuccess(): void
    {
        $result = Result::success(5);
        $mapped = $result->map(fn (int $x): int => $x * 2);

        $this->assertTrue($mapped->isSuccess());
        $this->assertSame(10, $mapped->unwrap());
    }

    public function testMapChainsMultipleTransforms(): void
    {
        $result = Result::success(10);
        $mapped = $result
            ->map(fn (int $x): int => $x + 5)
            ->map(fn (int $x): int => $x * 2);

        $this->assertSame(30, $mapped->unwrap());
    }

    public function testMapChangesTypeOnSuccess(): void
    {
        $result = Result::success(42);
        $mapped = $result->map(fn (int $x): string => (string) $x);

        $this->assertSame('42', $mapped->unwrap());
    }

    public function testMapReturnsSuccessForObjectTransformation(): void
    {
        $result = Result::success(['name' => 'Test']);
        $mapped = $result->map(fn (array $data): object => (object) $data);

        $this->assertInstanceOf(\stdClass::class, $mapped->unwrap());
    }

    // ========== FlatMap Tests ==========

    public function testFlatMapWithSuccessResult(): void
    {
        $result = Result::success(5);
        $flatMapped = $result->flatMap(fn (int $x): Result => Result::success($x * 2));

        $this->assertTrue($flatMapped->isSuccess());
        $this->assertSame(10, $flatMapped->unwrap());
    }

    public function testFlatMapCanTransformToFailure(): void
    {
        $result = Result::success(5);
        $flatMapped = $result->flatMap(fn (int $x): Result => Result::failure(
            ErrorDetail::create(ErrorCode::kernel('TOO_LARGE'), 'Value too large')
        ));

        $this->assertTrue($flatMapped->isFailure());
    }

    public function testFlatMapChainsMultipleOperations(): void
    {
        $result = Result::success(10);
        $final = $result
            ->flatMap(fn (int $x): Result => Result::success($x + 5))
            ->flatMap(fn (int $x): Result => Result::success($x * 2));

        $this->assertSame(30, $final->unwrap());
    }

    // ========== Side Effect Tests ==========

    public function testOnSuccessExecutesSideEffect(): void
    {
        $result = Result::success('value');
        $sideEffectCalled = false;
        $capturedValue = null;

        $result->onSuccess(function (string $value) use (&$sideEffectCalled, &$capturedValue): void {
            $sideEffectCalled = true;
            $capturedValue = $value;
        });

        $this->assertTrue($sideEffectCalled);
        $this->assertSame('value', $capturedValue);
    }

    public function testOnSuccessReturnsSameResult(): void
    {
        $result = Result::success('value');

        $returned = $result->onSuccess(fn (): void => null);

        $this->assertSame($result, $returned);
    }

    public function testOnFailureDoesNotExecuteOnSuccess(): void
    {
        $result = Result::success('value');
        $sideEffectCalled = false;

        $result->onFailure(fn () => $sideEffectCalled = true);

        $this->assertFalse($sideEffectCalled);
    }

    // ========== Match Tests ==========

    public function testMatchCallsSuccessHandler(): void
    {
        $result = Result::success(10);

        $matched = $result->match(
            fn (int $x): int => $x * 2,
            fn (ErrorDetail $e): int => 0
        );

        $this->assertSame(20, $matched);
    }

    public function testMatchPassesCorrectValueToHandler(): void
    {
        $result = Result::success(['id' => 1, 'name' => 'Test']);

        $matched = $result->match(
            fn (array $data): string => $data['name'],
            fn (ErrorDetail $e): string => 'error'
        );

        $this->assertSame('Test', $matched);
    }
}

final class ResultFailureOperationsTest extends KernelTestCase
{
    private function createError(string $code, string $message): ErrorDetail
    {
        return ErrorDetail::create(ErrorCode::kernel($code), $message);
    }

    // ========== Unwrap Tests ==========

    public function testUnwrapThrowsOnFailure(): void
    {
        $error = $this->createError('NOT_FOUND', 'Resource not found');
        $result = Result::failure($error);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot unwrap a failed result');

        $result->unwrap();
    }

    public function testUnwrapOrReturnsDefaultOnFailure(): void
    {
        $error = $this->createError('NOT_FOUND', 'Not found');
        $result = Result::failure($error);

        $this->assertSame('default value', $result->unwrapOr('default value'));
    }

    public function testUnwrapOrReturnsDifferentDefaultTypes(): void
    {
        $error = $this->createError('ERROR', 'Error');
        $result = Result::failure($error);

        $this->assertSame(0, $result->unwrapOr(0));
        $this->assertSame([], $result->unwrapOr([]));
        $this->assertNull($result->unwrapOr(null));
    }

    // ========== Error Access Tests ==========

    public function testErrorReturnsErrorDetailOnFailure(): void
    {
        $error = $this->createError('VALIDATION', 'Validation failed');
        $result = Result::failure($error);

        $this->assertSame($error, $result->error());
    }

    public function testErrorPreservesMessage(): void
    {
        $error = $this->createError('CUSTOM_ERROR', 'Custom error message');
        $result = Result::failure($error);

        $this->assertSame('Custom error message', $result->error()->message());
    }

    public function testErrorPreservesErrorCode(): void
    {
        $error = ErrorDetail::create(ErrorCode::domainError('ORDER', 'INVALID_STATUS'), 'Invalid status');
        $result = Result::failure($error);

        $this->assertTrue($result->error()->code()->isDomainError());
        $this->assertSame('DOMAIN.ORDER.INVALID_STATUS', $result->error()->code()->code());
    }

    // ========== Map Tests ==========

    public function testMapIsNoOpOnFailure(): void
    {
        $error = $this->createError('ERROR', 'Error');
        $result = Result::failure($error);
        $mapCalled = false;

        $mapped = $result->map(function () use (&$mapCalled) {
            $mapCalled = true;
            return 'transformed';
        });

        $this->assertFalse($mapCalled);
        $this->assertTrue($mapped->isFailure());
        $this->assertSame($result, $mapped);
    }

    public function testMapPreservesErrorOnFailure(): void
    {
        $error = $this->createError('NOT_FOUND', 'Not found');
        $result = Result::failure($error);

        $mapped = $result->map(fn (): string => 'transformed');

        $this->assertSame($error, $mapped->error());
    }

    // ========== FlatMap Tests ==========

    public function testFlatMapIsNoOpOnFailure(): void
    {
        $error = $this->createError('ERROR', 'Error');
        $result = Result::failure($error);
        $flatMapCalled = false;

        $flatMapped = $result->flatMap(function () use (&$flatMapCalled): Result {
            $flatMapCalled = true;
            return Result::success('transformed');
        });

        $this->assertFalse($flatMapCalled);
        $this->assertTrue($flatMapped->isFailure());
    }

    // ========== Side Effect Tests ==========

    public function testOnFailureExecutesSideEffect(): void
    {
        $error = $this->createError('ERROR', 'Error message');
        $result = Result::failure($error);
        $sideEffectCalled = false;
        $capturedError = null;

        $result->onFailure(function (ErrorDetail $e) use (&$sideEffectCalled, &$capturedError): void {
            $sideEffectCalled = true;
            $capturedError = $e;
        });

        $this->assertTrue($sideEffectCalled);
        $this->assertSame($error, $capturedError);
    }

    public function testOnFailureReturnsSameResult(): void
    {
        $error = $this->createError('ERROR', 'Error');
        $result = Result::failure($error);

        $returned = $result->onFailure(fn (): void => null);

        $this->assertSame($result, $returned);
    }

    public function testOnSuccessDoesNotExecuteOnFailure(): void
    {
        $error = $this->createError('ERROR', 'Error');
        $result = Result::failure($error);
        $sideEffectCalled = false;

        $result->onSuccess(fn () => $sideEffectCalled = true);

        $this->assertFalse($sideEffectCalled);
    }

    // ========== Match Tests ==========

    public function testMatchCallsFailureHandler(): void
    {
        $error = $this->createError('NOT_FOUND', 'Not found');
        $result = Result::failure($error);

        $matched = $result->match(
            fn ($x): string => 'success',
            fn (ErrorDetail $e): string => 'failure: ' . $e->message()
        );

        $this->assertSame('failure: Not found', $matched);
    }

    public function testMatchPassesErrorToHandler(): void
    {
        $error = $this->createError('CUSTOM', 'Custom error');
        $result = Result::failure($error);

        $matched = $result->match(
            fn ($x): string => 'success',
            fn (ErrorDetail $e): string => $e->code()->code()
        );

        $this->assertSame('KERNEL.CUSTOM', $matched);
    }
}

final class ResultMonadLawsTest extends KernelTestCase
{
    /**
     * Left identity: return a >>= f ≡ f a
     * Wrapping a value and flatMapping is the same as applying f directly
     */
    public function testLeftIdentityLaw(): void
    {
        $value = 5;
        $f = fn (int $x): Result => Result::success($x * 2);

        // return a >>= f
        $left = Result::success($value)->flatMap($f);

        // f a
        $right = $f($value);

        $this->assertEquals($left->unwrap(), $right->unwrap());
    }

    /**
     * Right identity: m >>= return ≡ m
     * FlatMapping with return is a no-op
     */
    public function testRightIdentityLaw(): void
    {
        $m = Result::success(10);
        $return = fn ($x): Result => Result::success($x);

        // m >>= return
        $result = $m->flatMap($return);

        $this->assertSame($m->unwrap(), $result->unwrap());
    }

    /**
     * Associativity: (m >>= f) >>= g ≡ m >>= (\x -> f x >>= g)
     * The order of flatMapping doesn't matter
     */
    public function testAssociativityLaw(): void
    {
        $m = Result::success(2);
        $f = fn (int $x): Result => Result::success($x + 10);
        $g = fn (int $x): Result => Result::success($x * 3);

        // (m >>= f) >>= g
        $left = $m->flatMap($f)->flatMap($g);

        // m >>= (\x -> f x >>= g)
        $right = $m->flatMap(fn (int $x): Result => $f($x)->flatMap($g));

        $this->assertEquals($left->unwrap(), $right->unwrap());
        $this->assertSame(36, $left->unwrap()); // (2 + 10) * 3 = 36
    }
}

final class ResultEdgeCasesTest extends KernelTestCase
{
    // ========== Chaining Edge Cases ==========

    public function testChainingSuccessThenFailureThenSuccess(): void
    {
        $result = Result::success(10)
            ->flatMap(fn (int $x): Result => Result::failure(
                ErrorDetail::create(ErrorCode::kernel('ERROR'), 'Error')
            ))
            ->flatMap(fn (int $x): Result => Result::success($x * 2)); // Should not execute

        $this->assertTrue($result->isFailure());
    }

    public function testMultipleMapOperationsPreserveSuccess(): void
    {
        $result = Result::success(1)
            ->map(fn (int $x): int => $x + 1)
            ->map(fn (int $x): int => $x + 1)
            ->map(fn (int $x): int => $x + 1);

        $this->assertSame(4, $result->unwrap());
    }

    public function testMapAfterFlatMap(): void
    {
        $result = Result::success(5)
            ->flatMap(fn (int $x): Result => Result::success($x * 2))
            ->map(fn (int $x): int => $x + 1);

        $this->assertSame(11, $result->unwrap());
    }

    // ========== Mixed Type Tests ==========

    public function testSuccessWithCallable(): void
    {
        $fn = fn (): string => 'hello';
        $result = Result::success($fn);

        $this->assertIsCallable($result->unwrap());
    }

    public function testSuccessWithResource(): void
    {
        $resource = fopen('php://memory', 'r+');
        $result = Result::success($resource);

        $this->assertIsResource($result->unwrap());
        fclose($resource);
    }

    // ========== Error Context Tests ==========

    public function testFailureWithContextData(): void
    {
        $error = ErrorDetail::withContextData(
            ErrorCode::kernel('VALIDATION'),
            'Validation failed',
            ['field' => 'email', 'constraint' => 'required']
        );
        $result = Result::failure($error);

        $this->assertSame(['field' => 'email', 'constraint' => 'required'], $result->error()->context());
    }

    public function testFailureWithTraceIdentifiers(): void
    {
        $error = ErrorDetail::create(ErrorCode::kernel('ERROR'), 'Error')
            ->withTraceIdentifiers('trace-123', 'correlation-456');
        $result = Result::failure($error);

        $this->assertSame('trace-123', $result->error()->traceId());
        $this->assertSame('correlation-456', $result->error()->correlationId());
    }
}
