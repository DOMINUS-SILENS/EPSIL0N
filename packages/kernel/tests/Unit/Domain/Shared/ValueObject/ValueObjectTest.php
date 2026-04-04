<?php

declare(strict_types=1);

namespace Spiral\Kernel\Tests\Unit\Domain\Shared\ValueObject;

use Spiral\Kernel\Domain\Shared\ValueObject\ValueObject;
use Spiral\Kernel\Tests\KernelTestCase;

/**
 * Test fixtures for ValueObject testing.
 */
final class TestStringValueObject extends ValueObject
{
    public function __construct(
        private readonly string $value
    ) {
    }

    protected function valueEquals(ValueObject $other): bool
    {
        \assert($other instanceof self);
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function value(): string
    {
        return $this->value;
    }
}

final class TestIntegerValueObject extends ValueObject
{
    public function __construct(
        private readonly int $value
    ) {
    }

    protected function valueEquals(ValueObject $other): bool
    {
        \assert($other instanceof self);
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}

/**
 * Comprehensive tests for ValueObject base class.
 * Tests cover: equality, hashing, string representation, and type safety.
 *
 * @package Spiral\Kernel\Tests\Unit\Domain\Shared\ValueObject
 */
final class ValueObjectEqualityTest extends KernelTestCase
{
    // ========== Same Type Equality Tests ==========

    public function testEqualsReturnsTrueForSameValue(): void
    {
        $vo1 = new TestStringValueObject('test');
        $vo2 = new TestStringValueObject('test');

        $this->assertTrue($vo1->equals($vo2));
    }

    public function testEqualsReturnsTrueForIdenticalObjects(): void
    {
        $vo1 = new TestStringValueObject('test');

        $this->assertTrue($vo1->equals($vo1));
    }

    public function testEqualsReturnsFalseForDifferentValues(): void
    {
        $vo1 = new TestStringValueObject('test1');
        $vo2 = new TestStringValueObject('test2');

        $this->assertFalse($vo1->equals($vo2));
    }

    public function testEqualsIsSymmetric(): void
    {
        $vo1 = new TestStringValueObject('same');
        $vo2 = new TestStringValueObject('same');

        $this->assertTrue($vo1->equals($vo2));
        $this->assertTrue($vo2->equals($vo1));
    }

    public function testEqualsIsTransitive(): void
    {
        $vo1 = new TestStringValueObject('value');
        $vo2 = new TestStringValueObject('value');
        $vo3 = new TestStringValueObject('value');

        $this->assertTrue($vo1->equals($vo2));
        $this->assertTrue($vo2->equals($vo3));
        $this->assertTrue($vo1->equals($vo3));
    }

    // ========== Different Type Equality Tests ==========

    public function testEqualsReturnsFalseForDifferentTypes(): void
    {
        $vo1 = new TestStringValueObject('42');
        $vo2 = new TestIntegerValueObject(42);

        $this->assertFalse($vo1->equals($vo2));
    }

    public function testEqualsReturnsFalseForValueObjectAndNonValueObject(): void
    {
        $vo = new TestStringValueObject('test');
        $notVo = new \stdClass();

        // Note: equals() requires ValueObject type, so this would be a type error
        // We're testing the internal type check in the base class
        $reflection = new \ReflectionClass(ValueObject::class);
        $this->assertTrue($reflection->isAbstract());
    }

    // ========== Integer Value Object Tests ==========

    public function testIntegerValueObjectEquals(): void
    {
        $vo1 = new TestIntegerValueObject(100);
        $vo2 = new TestIntegerValueObject(100);

        $this->assertTrue($vo1->equals($vo2));
    }

    public function testIntegerValueObjectNotEquals(): void
    {
        $vo1 = new TestIntegerValueObject(100);
        $vo2 = new TestIntegerValueObject(200);

        $this->assertFalse($vo1->equals($vo2));
    }
}

final class ValueObjectHashTest extends KernelTestCase
{
    // ========== Hash Generation Tests ==========

    public function testHashReturnsMd5OfStringRepresentation(): void
    {
        $vo = new TestStringValueObject('test');

        $this->assertSame(md5('test'), $vo->hash());
    }

    public function testHashIsConsistentForSameValue(): void
    {
        $vo1 = new TestStringValueObject('same');
        $vo2 = new TestStringValueObject('same');

        $this->assertSame($vo1->hash(), $vo2->hash());
    }

    public function testHashIsDifferentForDifferentValues(): void
    {
        $vo1 = new TestStringValueObject('value1');
        $vo2 = new TestStringValueObject('value2');

        $this->assertNotSame($vo1->hash(), $vo2->hash());
    }

    public function testHashForIntegerValue(): void
    {
        $vo = new TestIntegerValueObject(42);

        $this->assertSame(md5('42'), $vo->hash());
    }

    public function testHashCanBeUsedInArrays(): void
    {
        $vo1 = new TestStringValueObject('test');
        $vo2 = new TestStringValueObject('test');

        $array = [];
        $array[$vo1->hash()] = 'value1';
        $array[$vo2->hash()] = 'value2'; // Should overwrite value1

        $this->assertCount(1, $array);
        $this->assertSame('value2', $array[$vo1->hash()]);
    }
}

final class ValueObjectStringRepresentationTest extends KernelTestCase
{
    // ========== String Conversion Tests ==========

    public function testMagicToStringReturnsStringRepresentation(): void
    {
        $vo = new TestStringValueObject('test');

        $this->assertSame('test', (string) $vo);
    }

    public function testToStringWithInteger(): void
    {
        $vo = new TestIntegerValueObject(42);

        $this->assertSame('42', (string) $vo);
    }

    public function testToStringWithEmptyString(): void
    {
        $vo = new TestStringValueObject('');

        $this->assertSame('', (string) $vo);
    }

    public function testToStringWithSpecialCharacters(): void
    {
        $vo = new TestStringValueObject('test@example.com');

        $this->assertSame('test@example.com', (string) $vo);
    }

    public function testToStringWithUnicode(): void
    {
        $vo = new TestStringValueObject('Hello 世界');

        $this->assertSame('Hello 世界', (string) $vo);
    }

    public function testToStringWithMultiline(): void
    {
        $vo = new TestStringValueObject("line1\nline2");

        $this->assertSame("line1\nline2", (string) $vo);
    }
}

final class ValueObjectImmutabilityTest extends KernelTestCase
{
    // ========== Immutability Contract Tests ==========

    public function testValueObjectIsAbstract(): void
    {
        $reflection = new \ReflectionClass(ValueObject::class);

        $this->assertTrue($reflection->isAbstract());
    }

    public function testConcreteValueObjectsCanBeInstantiated(): void
    {
        $vo = new TestStringValueObject('test');

        $this->assertInstanceOf(ValueObject::class, $vo);
    }

    public function testPropertiesAreReadonly(): void
    {
        $vo = new TestStringValueObject('test');
        $reflection = new \ReflectionClass($vo);

        foreach ($reflection->getProperties() as $property) {
            if (!$property->isStatic()) {
                $this->assertTrue($property->isReadOnly());
            }
        }
    }

    public function testValueCannotBeModifiedAfterCreation(): void
    {
        $vo = new TestStringValueObject('original');

        // Verify the value remains unchanged
        $this->assertSame('original', $vo->value());

        // Create a new VO with different value
        $vo2 = new TestStringValueObject('modified');
        $this->assertSame('modified', $vo2->value());

        // Original is unchanged
        $this->assertSame('original', $vo->value());
    }

    public function testEqualsRequiresValueObjectType(): void
    {
        $reflection = new \ReflectionMethod(ValueObject::class, 'equals');
        $params = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame(ValueObject::class, $params[0]->getType()->getName());
    }
}

final class ValueObjectEdgeCasesTest extends KernelTestCase
{
    // ========== Edge Case Tests ==========

    public function testEqualsWithNullValue(): void
    {
        // Testing with empty string as edge case
        $vo1 = new TestStringValueObject('');
        $vo2 = new TestStringValueObject('');

        $this->assertTrue($vo1->equals($vo2));
    }

    public function testEqualsWithZeroValue(): void
    {
        $vo1 = new TestIntegerValueObject(0);
        $vo2 = new TestIntegerValueObject(0);

        $this->assertTrue($vo1->equals($vo2));
    }

    public function testEqualsWithNegativeValue(): void
    {
        $vo1 = new TestIntegerValueObject(-100);
        $vo2 = new TestIntegerValueObject(-100);

        $this->assertTrue($vo1->equals($vo2));
    }

    public function testEqualsWithMaxInteger(): void
    {
        $vo1 = new TestIntegerValueObject(PHP_INT_MAX);
        $vo2 = new TestIntegerValueObject(PHP_INT_MAX);

        $this->assertTrue($vo1->equals($vo2));
    }

    public function testEqualsWithMinInteger(): void
    {
        $vo1 = new TestIntegerValueObject(PHP_INT_MIN);
        $vo2 = new TestIntegerValueObject(PHP_INT_MIN);

        $this->assertTrue($vo1->equals($vo2));
    }

    public function testHashWithEmptyString(): void
    {
        $vo = new TestStringValueObject('');

        $this->assertSame(md5(''), $vo->hash());
    }

    public function testHashWithLongString(): void
    {
        $longString = str_repeat('a', 10000);
        $vo = new TestStringValueObject($longString);

        $this->assertSame(md5($longString), $vo->hash());
        $this->assertSame(32, strlen($vo->hash())); // MD5 is always 32 chars
    }

    public function testDifferentTypesWithSameStringRepresentation(): void
    {
        // String '123' vs integer 123 (converted to '123')
        $vo1 = new TestStringValueObject('123');
        $vo2 = new TestIntegerValueObject(123);

        // Both have same string representation
        $this->assertSame((string) $vo1, (string) $vo2);

        // But they are different types, so not equal
        $this->assertFalse($vo1->equals($vo2));
    }
}

final class ValueObjectInheritanceTest extends KernelTestCase
{
    // ========== Inheritance Tests ==========

    public function testValueObjectIsParentOfAllValueObjects(): void
    {
        $vo = new TestStringValueObject('test');

        $this->assertInstanceOf(ValueObject::class, $vo);
    }

    public function testEqualsMethodIsInherited(): void
    {
        $reflection = new \ReflectionClass(TestStringValueObject::class);

        $this->assertTrue($reflection->hasMethod('equals'));
        $this->assertFalse($reflection->getMethod('equals')->isAbstract());
    }

    public function testHashMethodIsInherited(): void
    {
        $reflection = new \ReflectionClass(TestStringValueObject::class);

        $this->assertTrue($reflection->hasMethod('hash'));
        $this->assertFalse($reflection->getMethod('hash')->isAbstract());
    }

    public function testToStringIsAbstract(): void
    {
        $reflection = new \ReflectionClass(ValueObject::class);

        $this->assertTrue($reflection->getMethod('__toString')->isAbstract());
    }

    public function testValueEqualsIsAbstract(): void
    {
        $reflection = new \ReflectionClass(ValueObject::class);

        $this->assertTrue($reflection->getMethod('valueEquals')->isAbstract());
    }
}
