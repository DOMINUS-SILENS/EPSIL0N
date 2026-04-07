---
status: resolved
trigger: "Resolve identity equality leaks and PHPUnit test discovery failures."
created: 2026-04-06T00:00:00Z
updated: 2026-04-06T00:00:00Z
---

## Current Focus

hypothesis: ValueObject::equals() does not check if the compared object is of the same class before accessing properties, and tests have namespace/filename mismatches.
test: Read ValueObject.php and check test file paths/namespaces.
expecting: ValueObject::equals() to have a strict class check; tests to have matching namespaces and filenames.
next_action: Resolved.

## Symptoms

expected: 
- ValueObject::equals() should return false if the other object is of a different class.
- PHPUnit should discover all test classes in the kernel.
actual: 
- TenantId::equals() attempts to access a private property of UserId, causing a fatal error.
- TenantSlug::equals() attempts to access a property on an EmailAddress object, causing a warning.
- 3 test classes (ErrorDetailTest, ResultTest, ValueObjectTest) are not found by PHPUnit.
errors: 
- Error: Cannot access private property Spiral\Kernel\Domain\Identity\UserId::$uuid
- Warning: Undefined property: Spiral\Kernel\Domain\Tenancy\EmailAddress::$slug
- PHPUnit Warnings: "Class {ClassName} cannot be found in {FilePath}"
reproduction: Run `./vendor/bin/phpunit` in `packages/kernel`.
started: Occurred after the implementation of Phase 2.5 (Runtime Spine).

## Eliminated

## Evidence

- timestamp: 2026-04-06T00:00:00Z
  checked: /home/dominus/Project/EPSILON/packages/kernel/src/Domain/Shared/ValueObject/ValueObject.php
  found: ValueObject::equals() only checked if $other was an instance of ValueObject, allowing subclasses of different types (e.g., TenantId vs UserId) to pass the initial check and enter valueEquals().
  implication: Root cause of fatal errors when accessing private properties of mismatched ValueObject subclasses.

- timestamp: 2026-04-06T00:00:00Z
  checked: /home/dominus/Project/EPSILON/packages/kernel/tests/Unit/Domain/Shared/
  found: Test files existed but the class names within them (e.g., ErrorDetailCreationTest) did not match the expected filenames (e.g., ErrorDetailTest.php).
  implication: Root cause of PHPUnit test discovery warnings.

## Resolution

root_cause: 
1. ValueObject::equals() lacked a strict class comparison (get_class($this) === get_class($other)), leading to property access violations in subclasses.
2. Test class names in Unit/Domain/Shared/ did not match their filenames, causing PHPUnit discovery failures.
fix: 
1. Updated ValueObject::equals() to use strict class comparison.
2. Renamed test classes in ErrorDetailTest.php, ResultTest.php, and ValueObjectTest.php to match their filenames.
verification: 
Ran `./vendor/bin/phpunit` in `packages/kernel`. All 329 tests passed (excluding skipped), and the 3 "Class not found" warnings were resolved.
files_changed: 
- /home/dominus/Project/EPSILON/packages/kernel/src/Domain/Shared/ValueObject/ValueObject.php
- /home/dominus/Project/EPSILON/packages/kernel/tests/Unit/Domain/Shared/Error/ErrorDetailTest.php
- /home/dominus/Project/EPSILON/packages/kernel/tests/Unit/Domain/Shared/Result/ResultTest.php
- /home/dominus/Project/EPSILON/packages/kernel/tests/Unit/Domain/Shared/ValueObject/ValueObjectTest.php
