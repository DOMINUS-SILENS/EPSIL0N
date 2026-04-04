<?php

declare(strict_types=1);

namespace Spiral\Kernel\Tests\Unit\Domain\Tenancy;

use Spiral\Kernel\Domain\Tenancy\TenantSlug;
use Spiral\Kernel\Domain\Tenancy\EmailAddress;
use Spiral\Kernel\Domain\Tenancy\ResourceReference;
use Spiral\Kernel\Domain\Identity\TenantId;
use Spiral\Kernel\Tests\KernelTestCase;

/**
 * Comprehensive tests for Tenancy Value Objects.
 * Tests cover: creation, validation, immutability, equality, and special behaviors.
 */
final class TenantSlugTest extends KernelTestCase
{
    // ========== Creation Tests ==========

    public function testFromStringCreatesValidSlug(): void
    {
        $slug = TenantSlug::fromString('acme-corp');

        $this->assertSame('acme-corp', $slug->toString());
    }

    public function testTryFromStringReturnsSlugOnValidInput(): void
    {
        $slug = TenantSlug::tryFromString('valid-slug');

        $this->assertInstanceOf(TenantSlug::class, $slug);
        $this->assertSame('valid-slug', $slug->toString());
    }

    public function testTryFromStringReturnsNullOnInvalidInput(): void
    {
        $slug = TenantSlug::tryFromString('invalid_slug');

        $this->assertNull($slug);
    }

    // ========== Validation Tests - Length ==========

    public function testRejectsTooShortSlug(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('at least 3 characters');

        TenantSlug::fromString('ab');
    }

    public function testRejectsExactlyTwoCharacters(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        TenantSlug::fromString('xy');
    }

    public function testAcceptsExactlyThreeCharacters(): void
    {
        $slug = TenantSlug::fromString('abc');

        $this->assertSame('abc', $slug->toString());
    }

    public function testRejectsTooLongSlug(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('at most 63 characters');

        TenantSlug::fromString(str_repeat('a', 64));
    }

    public function testAcceptsExactly63Characters(): void
    {
        $slug = TenantSlug::fromString(str_repeat('a', 63));

        $this->assertSame(63, strlen($slug->toString()));
    }

    // ========== Validation Tests - Format ==========

    public function testRejectsEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot be empty');

        TenantSlug::fromString('');
    }

    public function testRejectsStartingWithNumber(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must start with a lowercase letter');

        TenantSlug::fromString('123-acme');
    }

    public function testRejectsStartingWithHyphen(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must start with a lowercase letter');

        TenantSlug::fromString('-acme');
    }

    public function testRejectsStartingWithUppercase(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must start with a lowercase letter');

        TenantSlug::fromString('Acme-corp');
    }

    public function testRejectsEndingWithHyphen(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must end with a lowercase letter or number');

        TenantSlug::fromString('acme-');
    }

    public function testAcceptsEndingWithNumber(): void
    {
        $slug = TenantSlug::fromString('acme-123');

        $this->assertSame('acme-123', $slug->toString());
    }

    public function testRejectsConsecutiveHyphens(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('consecutive hyphens');

        TenantSlug::fromString('acme--corp');
    }

    public function testRejectsUnderscores(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        TenantSlug::fromString('acme_corp');
    }

    public function testRejectsSpaces(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        TenantSlug::fromString('acme corp');
    }

    public function testRejectsSpecialCharacters(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        TenantSlug::fromString('acme@corp');
    }

    // ========== Validation Tests - Reserved Slugs ==========

    public function testRejectsReservedSlugWww(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('reserved');

        TenantSlug::fromString('www');
    }

    public function testRejectsReservedSlugApi(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('reserved');

        TenantSlug::fromString('api');
    }

    public function testRejectsReservedSlugAdmin(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('reserved');

        TenantSlug::fromString('admin');
    }

    public function testRejectsReservedSlugApp(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('reserved');

        TenantSlug::fromString('app');
    }

    public function testRejectsReservedSlugSystem(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('reserved');

        TenantSlug::fromString('system');
    }

    public function testRejectsReservedSlugTest(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('reserved');

        TenantSlug::fromString('test');
    }

    // ========== Reserved Slug Helpers ==========

    public function testIsReservedReturnsTrueForReservedSlugs(): void
    {
        $this->assertTrue(TenantSlug::isReserved('www'));
        $this->assertTrue(TenantSlug::isReserved('api'));
        $this->assertTrue(TenantSlug::isReserved('admin'));
    }

    public function testIsReservedReturnsFalseForNonReservedSlugs(): void
    {
        $this->assertFalse(TenantSlug::isReserved('acme-corp'));
        $this->assertFalse(TenantSlug::isReserved('my-company'));
    }

    public function testGetReservedSlugsReturnsArray(): void
    {
        $reserved = TenantSlug::getReservedSlugs();

        $this->assertIsArray($reserved);
        $this->assertContains('www', $reserved);
        $this->assertContains('api', $reserved);
        $this->assertContains('admin', $reserved);
    }

    // ========== Valid Slugs Tests ==========

    public function testAcceptsValidSlugs(): void
    {
        $validSlugs = [
            'acme-corp',
            'my-tenant',
            'test-123',
            'abc',
            'company-name-here',
            'a1b2c3',
            'tenant123',
        ];

        foreach ($validSlugs as $slugString) {
            $slug = TenantSlug::fromString($slugString);
            $this->assertSame($slugString, $slug->toString());
        }
    }

    // ========== Equality Tests ==========

    public function testEqualsReturnsTrueForSameSlug(): void
    {
        $slug1 = TenantSlug::fromString('acme-corp');
        $slug2 = TenantSlug::fromString('acme-corp');

        $this->assertTrue($slug1->equals($slug2));
    }

    public function testEqualsReturnsFalseForDifferentSlugs(): void
    {
        $slug1 = TenantSlug::fromString('acme-corp');
        $slug2 = TenantSlug::fromString('other-corp');

        $this->assertFalse($slug1->equals($slug2));
    }

    public function testEqualsReturnsFalseForDifferentTypes(): void
    {
        $slug = TenantSlug::fromString('acme');
        $email = EmailAddress::fromString('acme@example.com');

        $this->assertFalse($slug->equals($email));
    }

    // ========== String Representation Tests ==========

    public function testToStringReturnsSlug(): void
    {
        $slug = TenantSlug::fromString('acme-corp');

        $this->assertSame('acme-corp', $slug->toString());
    }

    public function testMagicToStringReturnsSlug(): void
    {
        $slug = TenantSlug::fromString('acme-corp');

        $this->assertSame('acme-corp', (string) $slug);
    }

    public function testHashReturnsConsistentValue(): void
    {
        $slug = TenantSlug::fromString('acme-corp');

        $this->assertSame(md5('acme-corp'), $slug->hash());
    }

    // ========== Immutability Tests ==========

    public function testImmutabilityViaReadonlyProperties(): void
    {
        $reflection = new \ReflectionClass(TenantSlug::class);

        foreach ($reflection->getProperties() as $property) {
            $this->assertTrue(
                $property->isReadOnly(),
                "Property {$property->getName()} should be readonly"
            );
        }
    }
}

final class EmailAddressTest extends KernelTestCase
{
    // ========== Creation Tests ==========

    public function testFromStringCreatesValidEmail(): void
    {
        $email = EmailAddress::fromString('test@example.com');

        $this->assertSame('test@example.com', $email->toString());
    }

    public function testTryFromStringReturnsEmailOnValidInput(): void
    {
        $email = EmailAddress::tryFromString('valid@example.com');

        $this->assertInstanceOf(EmailAddress::class, $email);
    }

    public function testTryFromStringReturnsNullOnInvalidInput(): void
    {
        $email = EmailAddress::tryFromString('invalid-email');

        $this->assertNull($email);
    }

    // ========== Normalization Tests ==========

    public function testDomainIsLowercased(): void
    {
        $email = EmailAddress::fromString('test@EXAMPLE.COM');

        $this->assertSame('test@example.com', $email->toString());
    }

    public function testLocalPartPreservesCase(): void
    {
        $email = EmailAddress::fromString('Test.User@example.com');

        $this->assertSame('Test.User@example.com', $email->toString());
    }

    public function testWhitespaceIsTrimmed(): void
    {
        $email = EmailAddress::fromString('  test@example.com  ');

        $this->assertSame('test@example.com', $email->toString());
    }

    // ========== Component Access Tests ==========

    public function testLocalPartReturnsCorrectValue(): void
    {
        $email = EmailAddress::fromString('user.name@example.com');

        $this->assertSame('user.name', $email->localPart());
    }

    public function testDomainReturnsCorrectValue(): void
    {
        $email = EmailAddress::fromString('user@subdomain.example.com');

        $this->assertSame('subdomain.example.com', $email->domain());
    }

    public function testDomainIsNormalizedToLowercase(): void
    {
        $email = EmailAddress::fromString('user@EXAMPLE.COM');

        $this->assertSame('example.com', $email->domain());
    }

    // ========== Validation Tests - Basic ==========

    public function testRejectsEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot be empty');

        EmailAddress::fromString('');
    }

    public function testRejectsMissingAtSign(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email format');

        EmailAddress::fromString('no-at-sign');
    }

    public function testRejectsMultipleAtSigns(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email format');

        EmailAddress::fromString('test@@example.com');
    }

    public function testRejectsEmptyLocalPart(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('local part cannot be empty');

        EmailAddress::fromString('@example.com');
    }

    public function testRejectsEmptyDomain(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('domain cannot be empty');

        EmailAddress::fromString('test@');
    }

    // ========== Validation Tests - Local Part ==========

    public function testRejectsInvalidLocalPart(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email local part');

        EmailAddress::fromString('.test@example.com');
    }

    public function testAcceptsValidLocalPartCharacters(): void
    {
        $validEmails = [
            'simple@example.com',
            'user.name@example.com',
            'user+tag@example.com',
            'user_name@example.com',
            'user-name@example.com',
            'a@example.com',
        ];

        foreach ($validEmails as $emailString) {
            $email = EmailAddress::fromString($emailString);
            $this->assertSame($emailString, $email->toString());
        }
    }

    // ========== Validation Tests - Domain ==========

    public function testRejectsDomainWithoutDot(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email domain');

        EmailAddress::fromString('test@localhost');
    }

    public function testRejectsDomainWithEmptyLabel(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email domain');

        EmailAddress::fromString('test@.example.com');
    }

    public function testRejectsDomainWithTooLongLabel(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email domain');

        $longLabel = str_repeat('a', 64);
        EmailAddress::fromString("test@{$longLabel}.com");
    }

    public function testAcceptsValidDomains(): void
    {
        $validEmails = [
            'test@example.com',
            'test@subdomain.example.com',
            'test@sub.domain.example.co.uk',
            'test@a-b-c.example.com',
        ];

        foreach ($validEmails as $emailString) {
            $email = EmailAddress::fromString($emailString);
            $this->assertSame($emailString, $email->toString());
        }
    }

    // ========== Domain Matching Tests ==========

    public function testMatchesDomainReturnsTrueForExactMatch(): void
    {
        $email = EmailAddress::fromString('user@example.com');

        $this->assertTrue($email->matchesDomain('example.com'));
    }

    public function testMatchesDomainReturnsFalseForDifferentDomain(): void
    {
        $email = EmailAddress::fromString('user@example.com');

        $this->assertFalse($email->matchesDomain('other.com'));
    }

    public function testMatchesDomainIsCaseInsensitive(): void
    {
        $email = EmailAddress::fromString('user@example.com');

        $this->assertTrue($email->matchesDomain('EXAMPLE.COM'));
    }

    public function testMatchesDomainWithWildcardReturnsTrueForSubdomain(): void
    {
        $email = EmailAddress::fromString('user@sub.example.com');

        $this->assertTrue($email->matchesDomain('*.example.com'));
    }

    public function testMatchesDomainWithWildcardReturnsFalseForDifferentBase(): void
    {
        $email = EmailAddress::fromString('user@sub.example.com');

        $this->assertFalse($email->matchesDomain('*.other.com'));
    }

    public function testMatchesDomainWithWildcardReturnsFalseForExactDomain(): void
    {
        $email = EmailAddress::fromString('user@example.com');

        // Wildcard matches subdomains, not the base domain itself
        $this->assertFalse($email->matchesDomain('*.example.com'));
    }

    // ========== Equality Tests ==========

    public function testEqualsReturnsTrueForSameEmail(): void
    {
        $email1 = EmailAddress::fromString('test@example.com');
        $email2 = EmailAddress::fromString('test@example.com');

        $this->assertTrue($email1->equals($email2));
    }

    public function testEqualsReturnsTrueForSameEmailDifferentCase(): void
    {
        $email1 = EmailAddress::fromString('test@EXAMPLE.COM');
        $email2 = EmailAddress::fromString('test@example.com');

        $this->assertTrue($email1->equals($email2));
    }

    public function testEqualsReturnsFalseForDifferentEmails(): void
    {
        $email1 = EmailAddress::fromString('test1@example.com');
        $email2 = EmailAddress::fromString('test2@example.com');

        $this->assertFalse($email1->equals($email2));
    }

    // ========== String Representation Tests ==========

    public function testToStringReturnsEmail(): void
    {
        $email = EmailAddress::fromString('test@example.com');

        $this->assertSame('test@example.com', $email->toString());
    }

    public function testMagicToStringReturnsEmail(): void
    {
        $email = EmailAddress::fromString('test@example.com');

        $this->assertSame('test@example.com', (string) $email);
    }

    public function testHashReturnsConsistentValue(): void
    {
        $email = EmailAddress::fromString('test@example.com');

        $this->assertSame(md5('test@example.com'), $email->hash());
    }
}

final class ResourceReferenceTest extends KernelTestCase
{
    // ========== Creation Tests ==========

    public function testCreateBasicReference(): void
    {
        $ref = ResourceReference::create('Order', 'ORD-12345');

        $this->assertSame('Order', $ref->resourceType());
        $this->assertSame('ORD-12345', $ref->resourceId());
        $this->assertNull($ref->tenantId());
        $this->assertFalse($ref->isCrossTenant());
    }

    public function testFromStringParsesBasicFormat(): void
    {
        $ref = ResourceReference::fromString('Order:ORD-12345');

        $this->assertSame('Order', $ref->resourceType());
        $this->assertSame('ORD-12345', $ref->resourceId());
        $this->assertNull($ref->tenantId());
    }

    public function testFromStringParsesCrossTenantFormat(): void
    {
        $ref = ResourceReference::fromString('Order:ORD-12345@tenant-uuid-here');

        $this->assertSame('Order', $ref->resourceType());
        $this->assertSame('ORD-12345', $ref->resourceId());
        $this->assertSame('tenant-uuid-here', $ref->tenantId());
        $this->assertTrue($ref->isCrossTenant());
    }

    public function testCrossTenantFactoryMethod(): void
    {
        $ref = ResourceReference::crossTenant('Order', 'ORD-12345', 'tenant-uuid');

        $this->assertTrue($ref->isCrossTenant());
        $this->assertSame('tenant-uuid', $ref->tenantId());
    }

    // ========== Validation Tests ==========

    public function testCreateRejectsEmptyResourceType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Resource type cannot be empty');

        ResourceReference::create('', 'ORD-123');
    }

    public function testCreateRejectsEmptyResourceId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Resource ID cannot be empty');

        ResourceReference::create('Order', '');
    }

    public function testCrossTenantRejectsEmptyTenantId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Tenant ID cannot be empty');

        ResourceReference::crossTenant('Order', 'ORD-123', '');
    }

    public function testFromStringRejectsEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot be empty');

        ResourceReference::fromString('');
    }

    public function testFromStringRejectsMissingColon(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid resource reference format');

        ResourceReference::fromString('OrderORD-12345');
    }

    public function testFromStringRejectsColonAtStart(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid resource reference format');

        ResourceReference::fromString(':ORD-12345');
    }

    public function testFromStringRejectsEmptyResourceId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Resource ID cannot be empty');

        ResourceReference::fromString('Order:');
    }

    public function testFromStringRejectsEmptyTenantId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Tenant ID cannot be empty');

        ResourceReference::fromString('Order:ORD-123@');
    }

    // ========== String Representation Tests ==========

    public function testToStringReturnsBasicFormat(): void
    {
        $ref = ResourceReference::create('Order', 'ORD-12345');

        $this->assertSame('Order:ORD-12345', $ref->toString());
    }

    public function testToStringReturnsCrossTenantFormat(): void
    {
        $ref = ResourceReference::crossTenant('Order', 'ORD-12345', 'tenant-uuid');

        $this->assertSame('Order:ORD-12345@tenant-uuid', $ref->toString());
    }

    public function testMagicToStringReturnsCorrectFormat(): void
    {
        $ref = ResourceReference::create('Order', 'ORD-12345');

        $this->assertSame('Order:ORD-12345', (string) $ref);
    }

    // ========== Array Serialization Tests ==========

    public function testToArrayReturnsBasicFormat(): void
    {
        $ref = ResourceReference::create('Order', 'ORD-12345');
        $array = $ref->toArray();

        $this->assertSame([
            'resourceType' => 'Order',
            'resourceId' => 'ORD-12345',
        ], $array);
    }

    public function testToArrayReturnsCrossTenantFormat(): void
    {
        $ref = ResourceReference::crossTenant('Order', 'ORD-12345', 'tenant-uuid');
        $array = $ref->toArray();

        $this->assertSame([
            'resourceType' => 'Order',
            'resourceId' => 'ORD-12345',
            'tenantId' => 'tenant-uuid',
        ], $array);
    }

    // ========== Equality Tests ==========

    public function testEqualsReturnsTrueForSameReference(): void
    {
        $ref1 = ResourceReference::create('Order', 'ORD-12345');
        $ref2 = ResourceReference::create('Order', 'ORD-12345');

        $this->assertTrue($ref1->equals($ref2));
    }

    public function testEqualsReturnsTrueForCrossTenantSameValues(): void
    {
        $ref1 = ResourceReference::crossTenant('Order', 'ORD-12345', 'tenant-uuid');
        $ref2 = ResourceReference::fromString('Order:ORD-12345@tenant-uuid');

        $this->assertTrue($ref1->equals($ref2));
    }

    public function testEqualsReturnsFalseForDifferentTypes(): void
    {
        $ref1 = ResourceReference::create('Order', 'ORD-12345');
        $ref2 = ResourceReference::create('Customer', 'CUST-12345');

        $this->assertFalse($ref1->equals($ref2));
    }

    public function testEqualsReturnsFalseForDifferentIds(): void
    {
        $ref1 = ResourceReference::create('Order', 'ORD-12345');
        $ref2 = ResourceReference::create('Order', 'ORD-67890');

        $this->assertFalse($ref1->equals($ref2));
    }

    public function testEqualsReturnsFalseForDifferentTenantIds(): void
    {
        $ref1 = ResourceReference::crossTenant('Order', 'ORD-12345', 'tenant-1');
        $ref2 = ResourceReference::crossTenant('Order', 'ORD-12345', 'tenant-2');

        $this->assertFalse($ref1->equals($ref2));
    }

    public function testEqualsReturnsFalseForCrossTenantVsNonCrossTenant(): void
    {
        $ref1 = ResourceReference::create('Order', 'ORD-12345');
        $ref2 = ResourceReference::crossTenant('Order', 'ORD-12345', 'tenant-uuid');

        $this->assertFalse($ref1->equals($ref2));
    }

    // ========== Cross-Tenant Detection Tests ==========

    public function testIsCrossTenantReturnsFalseForBasicReference(): void
    {
        $ref = ResourceReference::create('Order', 'ORD-12345');

        $this->assertFalse($ref->isCrossTenant());
    }

    public function testIsCrossTenantReturnsTrueForCrossTenantReference(): void
    {
        $ref = ResourceReference::crossTenant('Order', 'ORD-12345', 'tenant-uuid');

        $this->assertTrue($ref->isCrossTenant());
    }

    // ========== Valid References Tests ==========

    public function testAcceptsValidResourceReferences(): void
    {
        $validRefs = [
            'Order:ORD-001',
            'Customer:CUST-123-ABC',
            'Invoice:INV-2026-04-001',
            'Payment:PAY-UUID-12345',
            'Product:SKU-12345',
            'Shipment:SHIP-2026-001',
        ];

        foreach ($validRefs as $refString) {
            $ref = ResourceReference::fromString($refString);
            $this->assertSame($refString, $ref->toString());
        }
    }

    // ========== Hash Tests ==========

    public function testHashReturnsConsistentValue(): void
    {
        $ref = ResourceReference::create('Order', 'ORD-12345');

        $this->assertSame(md5('Order:ORD-12345'), $ref->hash());
    }
}
