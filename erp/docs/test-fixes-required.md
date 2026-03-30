# Test Suite Field Mismatches - Fix Guide

## Overview

This document lists the field mismatches between the generated tests and the actual database schema that need to be fixed.

---

## Critical Fixes Needed

### 1. **ProjectionVersionTest.php**

**Issue**: Test assumes `aggregate_type` and `aggregate_id` columns exist
**Actual Schema**: Table has `projector_name` (PK) and `version` columns only

**Fix Required**:

```php
// Change from:
ProjectionVersion::create([
    'aggregate_type' => 'customer',
    'aggregate_id' => 1,
    'version' => 1,
]);

// To:
ProjectionVersion::create([
    'projector_name' => 'CustomerBalanceProjector',
    'version' => 1,
]);
```

Also update primary key assertion:

```php
// Change from:
$this->assertFalse($version->incrementing);

// To:
$this->assertEquals('projector_name', $version->getKeyName());
```

---

### 2. **ProjectionSnapshotTest.php**

**Issue**: Test assumes `incrementing = false`
**Actual Model**: Uses default `incrementing = true`

**Fix Required**:

```php
// Change from:
public function it_does_not_use_incrementing_key(): void
{
    $snapshot = new ProjectionSnapshot();
    $this->assertFalse($snapshot->incrementing);
}

// To:
public function it_uses_incrementing_key(): void
{
    $snapshot = new ProjectionSnapshot();
    $this->assertTrue($snapshot->incrementing);
}
```

---

### 3. **SalesDashboardProjectorTest.php**

**Issue**: `$event` is an array but accessed as object with `->payload`
**Error**: `Cannot access property payload on array`

**Fix Required - Test Method 1 (line 33-40)**:

```php
// Change from:
$event = $this->createMovementValidatedEvent([...]);
$this->projector->handleMovementValidated((array) $event->payload, $event);

// To:
$payload = $this->createMovementValidatedEvent([...]);
$event = $this->createDomainOutbox('MovementValidated', $payload, 1);
$this->projector->handleMovementValidated($payload, $event);
```

**Fix Required - Test Method 2 (line 67-73)**:

```php
// Change from:
$event = $this->createDomainOutbox('MovementValidated', array_merge($basePayload, [...]), $i);
$this->projector->handleMovementValidated((array) $event->payload, $event);

// To:
$payload = array_merge($basePayload, [...]);
$event = $this->createDomainOutbox('MovementValidated', $payload, $i);
$this->projector->handleMovementValidated($payload, $event);
```

---

### 4. **StockServiceTest.php**

**Issue**: Missing sequence row in database for `mvmt_seq`
**Error**: `No sequence found for name: mvmt_seq`

**Fix Required**:
Add to `setUp()` method:

```php
protected function setUp(): void
{
    parent::setUp();

    // Seed sequence for movement IDs
    DB::table('sequences')->insert([
        'name' => 'mvmt_seq',
        'next_value' => 1,
    ]);
}
```

---

### 5. **SagaStepTest.php**

**Issue**: Missing `command_payload` in some test data
**Error**: `Field 'command_payload' doesn't have a default value`

**Fix Required**:
Add `command_payload => []` to all SagaStep::create() calls that are missing it.

**Also**: Foreign key constraint requires Saga to exist first

```php
// Create parent saga before creating steps
$saga = Saga::create([...]);

SagaStep::create([
    'saga_id' => $saga->id,  // Use actual saga ID
    ...
]);
```

---

### 6. **Model Test Fillable Field Checks**

Several model tests check for fillable fields that may not exist in the actual models. These tests may fail if the model's `$fillable` array doesn't include all expected fields.

**Models to verify**:

- ArticleTest - checks 8 fillable fields
- ArticleMovementTest - checks 9 fillable fields
- DepotTest - checks 11 fillable fields
- All other model tests with fillable field assertions

**Fix**: Update tests to only check fields that are actually in the model's `$fillable` array.

---

## Summary of Generated Tests

### Model Tests Created (25 files)

- ✅ ArticleTest.php
- ✅ ArticleMovementTest.php
- ✅ DepotTest.php
- ✅ EntrepriseTest.php
- ✅ ArticleUniteTest.php
- ✅ ArticleFamilleTest.php
- ✅ ArticleMarqueTest.php
- ✅ ArticleGroupePrixTest.php
- ✅ ArticleUniteArticleGroupePrixTest.php
- ✅ DomainOutboxTest.php
- ✅ EventStoreTest.php
- ✅ EventSchemaTest.php
- ✅ ContractTest.php
- ✅ IntentTest.php
- ✅ AnomalyTest.php
- ✅ SagaTest.php
- ✅ SagaStepTest.php
- ✅ AuditLogTest.php
- ✅ CreditReservationTest.php
- ✅ StockReservationTest.php
- ✅ ProjectionVersionTest.php (needs fix)
- ✅ ProjectionSnapshotTest.php (needs fix)
- ✅ And 4 more...

### Service Tests Created (16 files)

- ✅ MetricsServiceTest.php
- ✅ AggregateRootTest.php
- ✅ TaxeAggregateTest.php
- ✅ Plus existing service tests

---

## How to Fix

1. Run tests to see failures:

    ```bash
    php artisan test
    ```

2. Fix each test file using the guidance above

3. Re-run tests to verify fixes:
    ```bash
    php artisan test --filter=ProjectionVersionTest
    php artisan test --filter=ProjectionSnapshotTest
    php artisan test --filter=SalesDashboardProjectorTest
    php artisan test --filter=StockServiceTest
    ```

---

## Current Test Count

- **Total Test Files**: 40+
- **Total Test Methods**: 180+
- **Passing**: ~140
- **Failing**: ~39 (mostly due to schema mismatches above)

---

---

## Prochaines Sections

- [01-architecture-generale.md](./01-architecture-generale.md) - Documentation technique générale
- [02-infrastructure-core.md](./02-infrastructure-core.md) - Services et composants techniques
- [03-domaines-metiers.md](./03-domaines-metiers.md) - Implémentation des 13 macro-domaines
- [04-projections-analytics.md](./04-projections-analytics.md) - Phase C : Dashboards et analytics
- [05-temps-reel-crdt.md](./05-temps-reel-crdt.md) - Phase D : Temps réel et sync mobile
- [06-api-graphql.md](./06-api-graphql.md) - Phase D : API de requête GraphQL
- [07-alerting-observabilite.md](./07-alerting-observabilite.md) - Phase D : Monitoring et alertes
- [08-deployment-operations.md](./08-deployment-operations.md) - Guide de déploiement et maintenance

_Generated: March 25, 2026_
