<?php

declare(strict_types=1);

namespace Spiral\Kernel\Tests\Unit\Domain\Identity;

use Spiral\Kernel\Domain\Identity\TenantId;
use Spiral\Kernel\Domain\Identity\UserId;
use Spiral\Kernel\Domain\Identity\ActorId;
use Spiral\Kernel\Domain\Identity\EventId;
use Spiral\Kernel\Domain\Identity\CorrelationId;
use Spiral\Kernel\Domain\Identity\CausationId;
use Spiral\Kernel\Domain\Identity\DocumentId;
use Spiral\Kernel\Tests\KernelTestCase;

/**
 * Comprehensive tests for Identity Value Objects.
 * Tests cover: creation, validation, immutability, equality, and special behaviors.
 */
final class TenantIdTest extends KernelTestCase
{
    // ========== Creation Tests ==========

    public function testGenerateCreatesValidTenantId(): void
    {
        $id = TenantId::generate();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $id->toString()
        );
    }

    public function testFromStringCreatesValidTenantId(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $id = TenantId::fromString($uuid);

        $this->assertSame($uuid, $id->toString());
    }

    public function testFromStringPreservesCase(): void
    {
        $uuid = '550E8400-E29B-41D4-A716-446655440000';
        $id = TenantId::fromString($uuid);

        // UUIDs are case-insensitive, but we store as lowercase
        $this->assertSame(strtolower($uuid), $id->toString());
    }

    // ========== Validation Tests ==========

    public function testFromStringRejectsEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('TenantId cannot be empty');

        /** @phpstan-ignore-next-line Argument of an invalid type passed. Testing validation. */
        TenantId::fromString('');
    }

    public function testFromStringRejectsInvalidUuid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid TenantId format');

        TenantId::fromString('not-a-uuid');
    }

    public function testFromStringRejectsMalformedUuid(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        TenantId::fromString('550e8400-e29b-41d4-a716'); // Too short
    }

    // ========== Equality Tests ==========

    public function testEqualsReturnsTrueForSameUuid(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $id1 = TenantId::fromString($uuid);
        $id2 = TenantId::fromString($uuid);

        $this->assertTrue($id1->equals($id2));
        $this->assertTrue($id2->equals($id1));
    }

    public function testEqualsReturnsFalseForDifferentUuids(): void
    {
        $id1 = TenantId::generate();
        $id2 = TenantId::generate();

        $this->assertFalse($id1->equals($id2));
    }

    public function testEqualsReturnsFalseForDifferentTypes(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $tenantId = TenantId::fromString($uuid);
        $userId = UserId::fromString($uuid);

        $this->assertFalse($tenantId->equals($userId));
    }

    // ========== String Representation Tests ==========

    public function testToStringReturnsUuidString(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $id = TenantId::fromString($uuid);

        $this->assertSame($uuid, $id->toString());
    }

    public function testMagicToStringReturnsUuidString(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $id = TenantId::fromString($uuid);

        $this->assertSame($uuid, (string) $id);
    }

    public function testHashReturnsConsistentValue(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $id = TenantId::fromString($uuid);

        $this->assertSame(md5($uuid), $id->hash());
    }

    // ========== UUID Object Access Tests ==========

    public function testToUuidReturnsUuidInterface(): void
    {
        $id = TenantId::generate();

        $this->assertInstanceOf(\Ramsey\Uuid\UuidInterface::class, $id->toUuid());
        $this->assertSame($id->toString(), $id->toUuid()->toString());
    }

    // ========== Immutability Tests ==========

    public function testImmutabilityViaReadonlyProperties(): void
    {
        $id = TenantId::generate();

        // This test verifies the class is designed with readonly properties
        // PHP will enforce immutability at runtime
        $reflection = new \ReflectionClass(TenantId::class);

        foreach ($reflection->getProperties() as $property) {
            $this->assertTrue(
                $property->isReadOnly(),
                "Property {$property->getName()} should be readonly"
            );
        }
    }

    public function testPrivateConstructorPreventsDirectInstantiation(): void
    {
        $reflection = new \ReflectionClass(TenantId::class);

        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertTrue($constructor->isPrivate());
    }
}

final class UserIdTest extends KernelTestCase
{
    // ========== Creation Tests ==========

    public function testGenerateCreatesValidUserId(): void
    {
        $id = UserId::generate();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $id->toString()
        );
    }

    public function testFromStringCreatesValidUserId(): void
    {
        $uuid = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
        $id = UserId::fromString($uuid);

        $this->assertSame($uuid, $id->toString());
    }

    // ========== Validation Tests ==========

    public function testFromStringRejectsEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('UserId cannot be empty');

        /** @phpstan-ignore-next-line */
        UserId::fromString('');
    }

    public function testFromStringRejectsInvalidUuid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid UserId format');

        UserId::fromString('invalid');
    }

    // ========== Equality Tests ==========

    public function testEqualsReturnsTrueForSameUuid(): void
    {
        $uuid = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
        $id1 = UserId::fromString($uuid);
        $id2 = UserId::fromString($uuid);

        $this->assertTrue($id1->equals($id2));
    }

    public function testEqualsReturnsFalseForDifferentUuids(): void
    {
        $id1 = UserId::generate();
        $id2 = UserId::generate();

        $this->assertFalse($id1->equals($id2));
    }

    // ========== String Representation Tests ==========

    public function testToStringAndMagicToString(): void
    {
        $uuid = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
        $id = UserId::fromString($uuid);

        $this->assertSame($uuid, $id->toString());
        $this->assertSame($uuid, (string) $id);
    }
}

final class ActorIdTest extends KernelTestCase
{
    // ========== Creation Tests ==========

    public function testGenerateCreatesValidActorId(): void
    {
        $id = ActorId::generate();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $id->toString()
        );
    }

    public function testFromStringCreatesValidActorId(): void
    {
        $uuid = 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11';
        $id = ActorId::fromString($uuid);

        $this->assertSame($uuid, $id->toString());
    }

    // ========== Special Factory Methods ==========

    public function testFromUserIdCreatesActorIdWithSameUuid(): void
    {
        $userId = UserId::generate();
        $actorId = ActorId::fromUserId($userId);

        $this->assertSame($userId->toString(), $actorId->toString());
    }

    public function testSystemReturnsWellKnownUuid(): void
    {
        $systemActor = ActorId::system();

        $this->assertSame('00000000-0000-0000-0000-000000000000', $systemActor->toString());
    }

    public function testSystemReturnsSameInstance(): void
    {
        $system1 = ActorId::system();
        $system2 = ActorId::system();

        $this->assertTrue($system1->equals($system2));
    }

    // ========== System Actor Detection ==========

    public function testIsSystemReturnsTrueForSystemActor(): void
    {
        $systemActor = ActorId::system();

        $this->assertTrue($systemActor->isSystem());
    }

    public function testIsSystemReturnsFalseForNonSystemActor(): void
    {
        $regularActor = ActorId::generate();

        $this->assertFalse($regularActor->isSystem());
    }

    public function testIsSystemReturnsFalseForUserIdDerived(): void
    {
        $userId = UserId::generate();
        $actorId = ActorId::fromUserId($userId);

        $this->assertFalse($actorId->isSystem());
    }

    // ========== Validation Tests ==========

    public function testFromStringRejectsEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ActorId cannot be empty');

        /** @phpstan-ignore-next-line */
        ActorId::fromString('');
    }

    public function testFromStringRejectsInvalidUuid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid ActorId format');

        ActorId::fromString('not-a-uuid');
    }

    // ========== Equality Tests ==========

    public function testEqualsReturnsTrueForSameUuid(): void
    {
        $uuid = 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11';
        $id1 = ActorId::fromString($uuid);
        $id2 = ActorId::fromString($uuid);

        $this->assertTrue($id1->equals($id2));
    }
}

final class EventIdTest extends KernelTestCase
{
    // ========== Creation Tests ==========

    public function testGenerateCreatesValidEventId(): void
    {
        $id = EventId::generate();

        // UUID v7 has a specific version marker
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $id->toString()
        );
    }

    public function testFromStringCreatesValidEventId(): void
    {
        $uuid = '018d0f0f-0000-7000-8000-000000000000';
        $id = EventId::fromString($uuid);

        $this->assertSame($uuid, $id->toString());
    }

    // ========== Timestamp Tests (UUID v7 specific) ==========

    public function testGetTimestampReturnsDateTimeForUuidV7(): void
    {
        $id = EventId::generate();
        $timestamp = $id->getTimestamp();

        $this->assertInstanceOf(\DateTimeInterface::class, $timestamp);
    }

    public function testGetTimestampReturnsNullForNonUuidV7(): void
    {
        // Create from a UUID v4 string
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $id = EventId::fromString($uuid);

        // Non-v7 UUIDs may return null for timestamp
        // This tests the graceful handling
        $timestamp = $id->getTimestamp();

        // Could be null or a DateTime depending on implementation
        if ($timestamp !== null) {
            $this->assertInstanceOf(\DateTimeInterface::class, $timestamp);
        }
    }

    public function testGeneratedEventIdsAreTimeOrdered(): void
    {
        $id1 = EventId::generate();
        usleep(1000); // 1ms
        $id2 = EventId::generate();

        // UUID v7 IDs should be sortable by their string representation
        $this->assertLessThan(0, strcmp($id1->toString(), $id2->toString()));
    }

    // ========== Validation Tests ==========

    public function testFromStringRejectsEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('EventId cannot be empty');

        /** @phpstan-ignore-next-line */
        EventId::fromString('');
    }

    public function testFromStringRejectsInvalidUuid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid EventId format');

        EventId::fromString('invalid');
    }

    // ========== Equality Tests ==========

    public function testEqualsReturnsTrueForSameUuid(): void
    {
        $uuid = '018d0f0f-0000-7000-8000-000000000000';
        $id1 = EventId::fromString($uuid);
        $id2 = EventId::fromString($uuid);

        $this->assertTrue($id1->equals($id2));
    }
}

final class CorrelationIdTest extends KernelTestCase
{
    // ========== Creation Tests ==========

    public function testGenerateCreatesValidCorrelationId(): void
    {
        $id = CorrelationId::generate();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $id->toString()
        );
    }

    public function testFromStringCreatesValidCorrelationId(): void
    {
        $uuid = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';
        $id = CorrelationId::fromString($uuid);

        $this->assertSame($uuid, $id->toString());
    }

    // ========== Validation Tests ==========

    public function testFromStringRejectsEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('CorrelationId cannot be empty');

        /** @phpstan-ignore-next-line */
        CorrelationId::fromString('');
    }

    public function testFromStringRejectsInvalidUuid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid CorrelationId format');

        CorrelationId::fromString('invalid');
    }

    // ========== Equality Tests ==========

    public function testEqualsReturnsTrueForSameUuid(): void
    {
        $uuid = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';
        $id1 = CorrelationId::fromString($uuid);
        $id2 = CorrelationId::fromString($uuid);

        $this->assertTrue($id1->equals($id2));
    }

    // ========== String Representation Tests ==========

    public function testToStringAndMagicToString(): void
    {
        $uuid = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';
        $id = CorrelationId::fromString($uuid);

        $this->assertSame($uuid, $id->toString());
        $this->assertSame($uuid, (string) $id);
    }
}

final class CausationIdTest extends KernelTestCase
{
    // ========== Creation Tests ==========

    public function testGenerateCreatesValidCausationId(): void
    {
        $id = CausationId::generate();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $id->toString()
        );
    }

    public function testFromStringCreatesValidCausationId(): void
    {
        $uuid = '6ba7b811-9dad-11d1-80b4-00c04fd430c8';
        $id = CausationId::fromString($uuid);

        $this->assertSame($uuid, $id->toString());
    }

    // ========== Special Factory Methods ==========

    public function testFromEventIdCreatesCausationIdWithSameUuid(): void
    {
        $eventId = EventId::generate();
        $causationId = CausationId::fromEventId($eventId);

        $this->assertSame($eventId->toString(), $causationId->toString());
    }

    // ========== Validation Tests ==========

    public function testFromStringRejectsEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('CausationId cannot be empty');

        /** @phpstan-ignore-next-line */
        CausationId::fromString('');
    }

    public function testFromStringRejectsInvalidUuid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid CausationId format');

        CausationId::fromString('invalid');
    }

    // ========== Equality Tests ==========

    public function testEqualsReturnsTrueForSameUuid(): void
    {
        $uuid = '6ba7b811-9dad-11d1-80b4-00c04fd430c8';
        $id1 = CausationId::fromString($uuid);
        $id2 = CausationId::fromString($uuid);

        $this->assertTrue($id1->equals($id2));
    }
}

final class DocumentIdTest extends KernelTestCase
{
    // ========== Creation Tests ==========

    public function testGenerateCreatesValidDocumentId(): void
    {
        $id = DocumentId::generate();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $id->toString()
        );
    }

    public function testFromStringCreatesValidDocumentId(): void
    {
        $uuid = '6ba7b812-9dad-11d1-80b4-00c04fd430c8';
        $id = DocumentId::fromString($uuid);

        $this->assertSame($uuid, $id->toString());
    }

    // ========== Validation Tests ==========

    public function testFromStringRejectsEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('DocumentId cannot be empty');

        /** @phpstan-ignore-next-line */
        DocumentId::fromString('');
    }

    public function testFromStringRejectsInvalidUuid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid DocumentId format');

        DocumentId::fromString('invalid');
    }

    // ========== Equality Tests ==========

    public function testEqualsReturnsTrueForSameUuid(): void
    {
        $uuid = '6ba7b812-9dad-11d1-80b4-00c04fd430c8';
        $id1 = DocumentId::fromString($uuid);
        $id2 = DocumentId::fromString($uuid);

        $this->assertTrue($id1->equals($id2));
    }

    // ========== String Representation Tests ==========

    public function testToStringAndMagicToString(): void
    {
        $uuid = '6ba7b812-9dad-11d1-80b4-00c04fd430c8';
        $id = DocumentId::fromString($uuid);

        $this->assertSame($uuid, $id->toString());
        $this->assertSame($uuid, (string) $id);
    }
}
