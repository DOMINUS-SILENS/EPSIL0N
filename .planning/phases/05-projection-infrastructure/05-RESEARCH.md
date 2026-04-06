# Phase 05: Projection Infrastructure - Research

**Researched:** 2026-04-06
**Domain:** Event-Sourced Read Models / Projections
**Confidence:** HIGH

## Summary

The goal of this phase is to implement the "Read Side" of the CQRS pattern within the EPSILON Kernel. While the `IEventStore` manages the source of truth (the event log), the Projection Infrastructure is responsible for observing this log and transforming events into optimized, queryable state.

The proposed architecture implements a **Pull-based Projection Engine**. This model ensures high reliability and allows for "replaying" the event store to rebuild read models from scratch (essential for schema migrations or adding new projections). The engine tracks the progress of each projector via a `projection_offsets` table, ensuring that each event is processed exactly once (or at least once with idempotent handlers).

**Primary recommendation:** Use a polling-based `ProjectionEngine` that iterates through `StoredEvent` records in chronological order, dispatching them to registered `IEventProjector` implementations.

## Standard Stack

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| PostgreSQL | 14+ | Read Model Storage | Native JSONB support for flexible read models and ACID guarantees for offset updates. |
| Spiral Framework | 3.x | DI & Lifecycle | Manages projector registration and engine execution via bootloaders. |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| ramsey/uuid | 4.7 | Event Identification | Ensuring event uniqueness during projection. |

**Installation:**
No new external packages required; utilizes existing kernel stack.

## Architecture Patterns

### Recommended Project Structure
```
packages/kernel/src/
├── Application/
│   └── Query/               # New: Query definitions and handlers
│       ├── Contract/        # IQuery, IQueryHandler
│       └── Behavior/        # Concrete query implementations
├── Infrastructure/
│   ├── Projection/          # The "Engine"
│   │   ├── ProjectionEngine.php    # Orchestrator
│   │   ├── Contract/
│   │   │   └── IEventProjector.php  # Projector interface
│   │   └── OffsetStore.php          # Manages projection_offsets table
│   └── Persistence/
│       └── ReadModel/       # Read model implementations (SQL)
└── Domain/
    └── Shared/
        └── Query/           # Domain-level query primitives
```

### Pattern 1: The Projection Cycle
**What:** A loop that reads the global event log and updates read models.
**When to use:** Always for asynchronous read-model updates.
**Flow:**
1. `ProjectionEngine` queries `projection_offsets` to find the `last_processed_version`.
2. Engine fetches `StoredEvent` records from `IEventStore` where `global_version > last_processed_version`.
3. For each event, the engine identifies registered `IEventProjector`s.
4. Projectors update their respective read model tables.
5. Upon successful processing of a batch, the `ProjectionEngine` updates the `projection_offsets`.

### Anti-Patterns to Avoid
- **Synchronous Projections:** Updating read models inside the command handler. This couples write performance to read model complexity and risks data inconsistency if the write succeeds but the projection fails.
- **Shared Tables:** Using the same tables for the Write Model (Aggregates) and Read Model. Read models should be optimized for queries (denormalized), not invariants.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Event Dispatching | Custom Event Bus | Spiral's existing event/interceptor system | Leveraging the framework's DI and lifecycle hooks. |
| Offset Management | In-memory counters | PostgreSQL table | Must persist across restarts and be transactional. |

## Runtime State Inventory
*Not applicable for this greenfield infrastructure phase.*

## Common Pitfalls

### Pitfall 1: Eventual Consistency Lag
**What goes wrong:** User performs an action, is redirected to a list page, but the change isn't visible yet.
**Why it happens:** The `ProjectionEngine` hasn't polled the event store yet.
**How to avoid:** 
- Implement "Read-Your-Own-Writes" via version tracking in the UI (poll until version X is reached).
- For critical paths, allow "Direct-to-Aggregate" reads (at the cost of performance).

### Pitfall 2: Non-Idempotent Projectors
**What goes wrong:** Replaying the event store doubles the data in the read model.
**Why it happens:** Projectors use `INSERT` instead of `UPSERT`.
**How to avoid:** Every projector operation must be idempotent. Use `ON CONFLICT (id) DO UPDATE` in PostgreSQL.

## Code Examples

### Proposed `IEventProjector` Interface
```typescript
interface IEventProjector {
    /**
     * Unique identifier for the projector (used in projection_offsets table)
     */
    public function getProjectorId(): string;

    /**
     * Handle a specific event and update the read model.
     * Should be idempotent.
     */
    public function project(StoredEvent $event): void;
}
```

### Proposed `ProjectionEngine` Signature
```typescript
class ProjectionEngine {
    public function __construct(
        private IEventStore $eventStore,
        private OffsetStore $offsetStore,
        private array $projectors // List of IEventProjector
    ) {}

    public function run(): void {
        // 1. Get current offset
        // 2. Load events from IEventStore
        // 3. Dispatch to projectors
        // 4. Update offset
    }
}
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Direct DB Views | Projections | Industry Standard | Massive query performance boost; decouple write/read scaling. |

## Open Questions

1. **Global Sequence vs Stream Sequence**
   - What we know: `IEventStore` has stream versions.
   - What's unclear: For a global projection engine, we need a `global_sequence_number` (monotonic increasing ID) across all streams to poll efficiently.
   - Recommendation: Add a `global_position` (BIGINT) to the event store table to serve as the polling cursor.

2. **Query Primitives**
   - What we know: We need a way to return read model data.
   - What's unclear: Should we introduce a formal `IQuery` object or just use Repository methods?
   - Recommendation: Introduce `IQuery` and `IQueryHandler` to maintain the CQRS separation of concerns.

## Environment Availability

| Dependency | Required By | Available | Version | Fallback |
|------------|------------|-----------|---------|----------|
| PostgreSQL | Read Model / Offsets | ✓ | 14+ | — |

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | PHPUnit 11 |
| Config file | `packages/kernel/phpunit.xml` |
| Quick run command | `./vendor/bin/phpunit --testsuite Integration` |

### Phase Requirements $\to$ Test Map
| Req ID | Behavior | Test Type | Automated Command |
|--------|----------|-----------|-------------------|
| PROJ-01 | Event $\to$ Read Model update | Integration | `it_projects_event_to_read_model()` |
| PROJ-02 | Offset tracking / Resume | Integration | `it_resumes_projection_from_last_offset()` |
| PROJ-03 | Idempotency on replay | Integration | `it_handles_duplicate_events_idempotently()` |

## Sources

### Primary (HIGH confidence)
- `packages/kernel/src/Infrastructure/Contract/EventStore/IEventStore.php` - Verified existing store contract.
- `packages/kernel/src/Domain/Shared/Event/StoredEvent.php` - Verified event envelope structure.

### Secondary (MEDIUM confidence)
- Event Sourcing patterns (Greg Young / Martin Fowler) - Standard industry approach for Projections/Read Models.

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH - Based on project's existing PHP/PostgreSQL stack.
- Architecture: HIGH - Pull-based projections are the industry standard for reliable event sourcing.
- Pitfalls: HIGH - Eventual consistency and idempotency are the primary known risks in this domain.

**Research date:** 2026-04-06
**Valid until:** 2026-05-06
