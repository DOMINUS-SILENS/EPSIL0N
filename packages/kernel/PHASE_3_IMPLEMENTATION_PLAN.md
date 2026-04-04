# EPSILON Kernel — Phase 3 Implementation Plan

**Phase Title:** Event-Sourcing Core
**Status:** Ready to implement (pending user approval)
**Estimated Scope:** 8-12 classes, ~2,500-3,000 SLOC
**Type:** Mandatory architectural closure
**Builds On:** Phases 1-2 (all 21 primitives ready to integrate)

---

## Phase 3 Four-Part Structure

```
PART 1: Event Recording Layer (2 classes)
  ├─ DomainEvent (abstract base)
  └─ EventEnvelope (immutable storage wrapper)

PART 2: Aggregate Machinery (2 classes)
  ├─ AggregateRoot<TId> (abstract generic base)
  └─ EventRecorder (trait for uncommitted buffer)

PART 3: Contract Surface (5 interfaces - no implementation)
  ├─ IEventStore<T>
  ├─ IAggregateRepository<T, TId>
  ├─ IClock
  ├─ IUuidGenerator
  └─ IEventSerializer

PART 4: Integration & Testing
  ├─ Example aggregate (Product) as proof
  ├─ Reconstitution test proof
  └─ Semantic test suite
```

---

## Part 1: Event Recording Layer

### Class 1: DomainEvent (abstract)

**Location:** `src/Domain/EventSourcing/DomainEvent.php`

**Purpose:** Base class for all domain events. Immutable, carries metadata, versioned for replay safety.

**Responsibilities:**
- Carry event identity (EventId, time-ordered v7)
- Carry tenant boundary (TenantId, immutable)
- Carry execution context (ActorId, correlationId, causationId)
- Carry schema version for replay
- Be completely immutable (readonly properties)

**Public Interface:**

```php
abstract class DomainEvent {
    // Identity & Timing
    public function eventId(): EventId;
    public function occurredAt(): DateTimeImmutable;

    // Tenant & Actor Context
    public function tenantId(): TenantId;
    public function actorId(): ActorId;

    // Tracing (distributed observability)
    public function correlationId(): CorrelationId;
    public function causationId(): CausationId;

    // Versioning (replay safety)
    public function schemaVersion(): int;

    // Aggregate Context
    public function aggregateId(): mixed;  // Generic - will be string|UUID
    public function aggregateType(): string; // e.g., 'Product', 'Order'

    // Serialization (for event store)
    public function eventName(): string;  // e.g., 'ProductCreated'
    public function toPayload(): array;   // Serialize for storage

    // Internal
    protected function __construct(...);   // Private - use static factory
}
```

**Key Design Points:**
- All properties readonly (immutable)
- Private constructor (use factory methods in subclasses)
- EventId uses UUID v7 for time-ordering
- schemaVersion allows breaking changes while replaying old events
- No business logic, only data carrier
- Carries all metadata needed for audit/tracing

**Example Subclass (from bounded context):**

```php
class ProductCreated extends DomainEvent {
    private readonly ProductId $productId;
    private readonly ProductName $productName;
    private readonly Money $price;

    public static function create(
        ProductId $productId,
        ProductName $productName,
        Money $price,
        TenantId $tenantId,
        ActorId $actorId,
        CorrelationId $correlationId,
        CausationId $causationId,
        IClock $clock,
        IUuidGenerator $uuidGenerator,
    ): self {
        $event = new self();
        $event->eventId = $uuidGenerator->generateEventId();
        $event->occurredAt = $clock->now();
        $event->tenantId = $tenantId;
        $event->actorId = $actorId;
        $event->correlationId = $correlationId;
        $event->causationId = $causationId;
        $event->schemaVersion = 1;
        $event->productId = $productId;
        $event->productName = $productName;
        $event->price = $price;
        return $event;
    }

    public function aggregateId(): mixed {
        return $this->productId;
    }

    public function aggregateType(): string {
        return 'Product';
    }

    public function eventName(): string {
        return 'ProductCreated';
    }

    public function toPayload(): array {
        return [
            'aggregateId' => $this->productId->toString(),
            'name' => $this->productName->toString(),
            'price' => $this->price->toArray(),
        ];
    }
}
```

---

### Class 2: EventEnvelope (immutable VO)

**Location:** `src/Domain/EventSourcing/EventEnvelope.php`

**Purpose:** Immutable wrapper around persisted event. Adds stream metadata for event store.

**Responsibilities:**
- Store the event itself (DomainEvent)
- Track stream name (e.g., "product-{uuid}")
- Track stream version (position in stream)
- Track sequence number (global ordering if needed)
- Be immutable and serializable

**Public Interface:**

```php
final class EventEnvelope {
    public static function record(
        DomainEvent $event,
        StreamName $streamName,
        Version $version,
        int $globalSequence = null,
    ): self;

    // Access
    public function event(): DomainEvent;
    public function streamName(): StreamName;
    public function version(): Version;  // Position in this stream
    public function globalSequence(): ?int;  // Global event number
    public function recordedAt(): DateTimeImmutable;  // When persisted

    // Serialization
    public function toArray(): array;
    public static function fromArray(array $data): self;
}
```

**Design Points:**
- Immutable (readonly properties)
- StreamName is deterministic: "product-{aggregateId}", "order-{aggregateId}"
- Version is 1-based position in stream
- GlobalSequence optional (for total ordering across streams)
- RecordedAt is server timestamp (not event occurred_at)
- Integrates with EventId v7 for time-ordering within stream

---

## Part 2: Aggregate Machinery

### Class 3: AggregateRoot<TId> (abstract generic)

**Location:** `src/Domain/EventSourcing/AggregateRoot.php`

**Purpose:** Base class for all event-sourced aggregates. Manages identity, versioning, event recording.

**Responsibilities:**
- Enforce tenant boundary (immutable TenantId)
- Track version for optimistic concurrency
- Record events via protected raise() method
- Replay events via protected reconstituteFromEvents()
- Provide event inspection for persistence

**Generic Type Parameter:**
- `<TId>` = aggregate identifier type (ProductId, OrderId, etc.)

**Public Interface:**

```php
abstract class AggregateRoot<TId> {
    // Identity
    abstract public function id(): TId;
    abstract public function aggregateType(): string; // e.g., 'Product'

    // Tenancy (immutable structural boundary)
    final public function tenantId(): TenantId;

    // Versioning (optimistic concurrency)
    final public function version(): Version;

    // Event Inspection
    final public function getRecordedEvents(): array; // DomainEvent[]
    final public function clearRecordedEvents(): void;

    // Protected Methods (for subclasses)

    // Record an event (adds to uncommitted buffer, calls apply handler)
    final protected function raise(DomainEvent $event): void;

    // Reconstruct from history (used by repository load)
    final protected function reconstituteFromEvents(
        array $events  // DomainEvent[]
    ): void;

    // Apply event handler routing (subclass implements)
    abstract protected function apply(DomainEvent $event): void;
}
```

**Example Subclass (Product aggregate):**

```php
class Product extends AggregateRoot {
    private ProductId $id;
    private TenantId $tenantId;
    private Version $version;
    private ProductName $name;
    private Money $price;
    private bool $published = false;

    public static function create(
        ProductId $productId,
        ProductName $name,
        Money $price,
        TenantId $tenantId,
        ActorId $actorId,
        CorrelationId $correlationId,
        CausationId $causationId,
        IClock $clock,
        IUuidGenerator $uuidGenerator,
    ): self {
        $product = new self();
        $product->id = $productId;
        $product->tenantId = $tenantId;
        $product->version = Version::initial();

        $product->raise(ProductCreated::create(
            $productId, $name, $price,
            $tenantId, $actorId,
            $correlationId, $causationId,
            $clock, $uuidGenerator,
        ));

        return $product;
    }

    public function publish(
        ActorId $actorId,
        CorrelationId $correlationId,
        CausationId $causationId,
        IClock $clock,
        IUuidGenerator $uuidGenerator,
    ): void {
        if ($this->published) {
            throw new BusinessRuleViolationException(
                'PRODUCT_ALREADY_PUBLISHED',
                'Cannot publish product that is already published'
            );
        }

        $this->raise(ProductPublished::create(
            $this->id, $this->tenantId,
            $actorId, $correlationId, $causationId,
            $clock, $uuidGenerator,
        ));
    }

    // Event handler routing
    protected function apply(DomainEvent $event): void {
        match ($event::class) {
            ProductCreated::class => $this->onProductCreated($event),
            ProductPublished::class => $this->onProductPublished($event),
            default => throw new InvalidArgumentException('Unknown event'),
        };
    }

    private function onProductCreated(ProductCreated $event): void {
        $this->name = $event->productName();
        $this->price = $event->price();
        $this->version = $this->version->increment();
    }

    private function onProductPublished(ProductPublished $event): void {
        $this->published = true;
        $this->version = $this->version->increment();
    }
}
```

**Design Points:**
- Generic <TId> requires bounded contexts to specify aggregate ID type
- TenantId is immutable (set once, never changed)
- Version starts at Version::initial() (0 or 1, TBD)
- raise() adds event to buffer AND calls apply()
- reconstituteFromEvents() replays history via apply()
- apply() uses match expression for handler routing
- Invariants are checked in business methods (publish, etc.)

---

### Class 4: EventRecorder (trait)

**Location:** `src/Domain/EventSourcing/EventRecorder.php`

**Purpose:** Manages uncommitted events buffer. Mixable into AggregateRoot or standalone.

**Responsibilities:**
- Maintain array of recorded events
- Provide recording interface
- Support clearing after persistence

**Public Interface:**

```php
trait EventRecorder {
    protected array $uncommittedEvents = [];

    protected function recordEvent(DomainEvent $event): void {
        $this->uncommittedEvents[] = $event;
    }

    final public function getUncommittedEvents(): array {
        return $this->uncommittedEvents;
    }

    final public function clearUncommittedEvents(): void {
        $this->uncommittedEvents = [];
    }

    final public function hasUncommittedEvents(): bool {
        return count($this->uncommittedEvents) > 0;
    }
}
```

**Alternatively:** Could be embedded directly into AggregateRoot (simpler) or kept separate for reuse in non-aggregate entities.

---

## Part 3: Contract Surface (5 Interfaces)

### Interface 1: IEventStore<T>

**Location:** `src/Domain/Contract/IEventStore.php`

**Purpose:** Persistence contract for events. Implementation deferred to Phase 5 (PostgreSQL).

```php
interface IEventStore {
    /**
     * Append an event to the stream with optimistic concurrency.
     *
     * @throws ConcurrencyConflictException if version mismatch
     */
    public function append(
        EventEnvelope $envelope,
        int $expectedVersion,
    ): void;

    /**
     * Load events from a stream starting at version.
     */
    public function getFromVersion(
        StreamName $streamName,
        Version $fromVersion = null,
    ): array; // EventEnvelope[]

    /**
     * Get the current version of the stream (highest recorded).
     */
    public function getCurrentVersion(
        StreamName $streamName,
    ): Version;
}
```

### Interface 2: IAggregateRepository<T, TId>

**Location:** `src/Domain/Contract/IAggregateRepository.php`

```php
interface IAggregateRepository {
    /**
     * Save aggregate (persists recorded events).
     *
     * @throws ConcurrencyConflictException on version conflict
     */
    public function save(AggregateRoot $aggregate): void;

    /**
     * Load aggregate from history.
     *
     * @throws NotFoundException if no events found
     */
    public function load($aggregateId): AggregateRoot;
}
```

### Interface 3: IClock

**Location:** `src/Domain/Contract/IClock.php`

```php
interface IClock {
    /**
     * Get current time (immutable).
     */
    public function now(): DateTimeImmutable;
}
```

### Interface 4: IUuidGenerator

**Location:** `src/Domain/Contract/IUuidGenerator.php`

```php
interface IUuidGenerator {
    /**
     * Generate UUID v4 (general identities).
     */
    public function generate(): UuidInterface;

    /**
     * Generate UUID v7 (time-ordered events).
     */
    public function generateEventId(): UuidInterface;
}
```

### Interface 5: IEventSerializer

**Location:** `src/Domain/Contract/IEventSerializer.php`

```php
interface IEventSerializer {
    /**
     * Serialize event to array for storage.
     */
    public function serialize(DomainEvent $event): array;

    /**
     * Deserialize event from array.
     *
     * @param string $eventClassName fully qualified class name
     */
    public function deserialize(
        array $data,
        string $eventClassName,
    ): DomainEvent;
}
```

---

## Part 4: Integration & Testing

### Proof 1: Example Aggregate (Product)

- Already shown above in AggregateRoot example
- Demonstrates: create(), business methods, event raising, replay via apply()

### Proof 2: Reconstitution Test

```php
class ProductReconstitutionTest extends KernelTestCase {
    public function test_product_reconstitutes_from_event_history(): void {
        $productId = ProductId::generate();
        $tenantId = TenantId::generate();
        $name = ProductName::fromString('Widget');
        $price = Money::fromCents(9999);

        $events = [
            ProductCreated::create(...),
            ProductPublished::create(...),
        ];

        $product = new Product();
        $product->reconstituteFromEvents($events);

        $this->assertEquals($productId, $product->id());
        $this->assertEquals($name, $product->name());
        $this->assertEquals($price, $product->price());
        $this->assertTrue($product->isPublished());
        $this->assertEquals(2, $product->version()->toInt());
    }
}
```

### Proof 3: Concurrency Test

```php
class ConcurrencyTest extends KernelTestCase {
    public function test_version_mismatch_throws_exception(): void {
        $repository = $this->createRepository();

        $product = $repository->load($productId);  // version = 2

        // Simulate another process incrementing version to 3
        // (in real scenario, another thread/request)

        $product->publish(...);  // expects version 2

        $this->expectException(ConcurrencyConflictException::class);
        $repository->save($product);  // version is now 3 in DB
    }
}
```

### Proof 4: Metadata Propagation Test

```php
class MetadataTest extends KernelTestCase {
    public function test_event_carries_correlation_and_causation(): void {
        $correlationId = CorrelationId::generate();
        $causationId = CausationId::generate();

        $product = Product::create(
            ...,
            $correlationId,
            $causationId,
            ...,
        );

        $events = $product->getRecordedEvents();

        $this->assertEquals($correlationId, $events[0]->correlationId());
        $this->assertEquals($causationId, $events[0]->causationId());
    }
}
```

---

## File Summary

```
Phase 3 New Files:
├── src/Domain/EventSourcing/
│   ├── DomainEvent.php          (abstract class, ~150 lines)
│   ├── EventEnvelope.php        (immutable VO, ~100 lines)
│   ├── AggregateRoot.php        (abstract generic, ~200 lines)
│   └── EventRecorder.php        (trait, ~50 lines)
│
├── src/Domain/Contract/
│   ├── IEventStore.php          (interface, ~30 lines)
│   ├── IAggregateRepository.php  (interface, ~20 lines)
│   ├── IClock.php               (interface, ~10 lines)
│   ├── IUuidGenerator.php        (interface, ~15 lines)
│   └── IEventSerializer.php      (interface, ~15 lines)
│
└── tests/
    ├── Unit/Domain/EventSourcing/
    │   ├── DomainEventTest.php
    │   ├── EventEnvelopeTest.php
    │   ├── AggregateRootTest.php
    │   └── EventRecorderTest.php
    │
    └── Integration/
        ├── ProductReconstitutionTest.php
        ├── ConcurrencyTest.php
        └── MetadataTest.php
```

**Total Phase 3 Estimate:** 9 files, ~600 lines implementation + ~400 lines tests = ~1,000 SLOC.

---

## Next Steps (After Phase 3)

### Phase 4: Temporal & Financial VOs (Orthogonal)

Can be done in parallel:
- BusinessDate
- BusinessPeriod
- Money, CurrencyCode, Quantity, UnitOfMeasure

These integrate into event payloads but don't block Phase 3.

### Phase 5: PostgreSQL Implementation

Implements the 5 contracts:
- IEventStore → PostgreSQL
- IAggregateRepository → Uses IEventStore
- IClock → SystemClock
- IUuidGenerator → RamseyUuid wrapper
- IEventSerializer → JSON + Reflection

---

## Approval Gate

**Ready to implement Phase 3?**

If yes, I will:
1. Create all 9 files with full implementation
2. Wire up reconstitution logic
3. Add comprehensive semantic tests
4. Verify PHPStan level 9 compliance
5. Update architecture diagrams with actual code

---

**Status:** Phase 3 plan complete and ready for implementation.
