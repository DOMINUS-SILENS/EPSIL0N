# Phase 6: CQRS Routing — Research

**Phase:** 6
**Goal:** Establish a clean separation between mutation (Commands) and retrieval (Queries)
**Roadmap:** Milestone 2: The Read Side

---

## Current State

### What's Already Implemented

1. **Command Bus** (`src/Application/Bus/CommandBus.php`)
   - Dispatches commands to handlers
   - Registry of command handlers

2. **ICommandBus Interface** (`src/Application/Contract/Bus/ICommandBus.php`)
   - Interface for command dispatch

3. **ICommandHandler** (`src/Application/Contract/Handler/ICommandHandler.php`)
   - Generic handler interface

### What's Missing

1. **Query Bus** — No IQueryBus interface or implementation
2. **Query Handlers** — No pattern for handling query objects
3. **Idempotency Guard** — No CorrelationId-based deduplication

---

## Requirement Analysis

### From ROADMAP.md

> **Phase 6: CQRS Routing (Command/Query Bus)**
> - **Goal:** Establish a clean separation between mutation and retrieval.
> - **Key Tasks:**
>     - Implement `ICommandBus` and `IQueryBus`.
>     - Create routing layer to dispatch queries to read models.
>     - Implement `IdempotencyGuard` using `CorrelationId`.
> - **Success Criteria:** Requests are routed as either Commands or Queries without overlapping logic.

### From Architecture Analysis

The kernel currently has:
- Write side operational (CommandBus exists)
- Read side missing (no QueryBus)

CQRS Routing is critical for:
1. Separating read vs write concerns
2. Enabling different read models for different queries
3. Idempotent command processing

---

## Domain Analysis

### Command vs Query Semantics

| Aspect | Command | Query |
|--------|---------|-------|
| Intent | Mutate state | Read state |
| Return | Result<T> | QueryResult<T> |
| Side effects | Yes | No |
| Idempotency | Required | N/A |
| Authorization | Per-command | Per-query |

### Required Abstractions

1. **IQuery** — Marker interface for query objects
2. **IQueryHandler** — Interface for query handlers
3. **IQueryBus** — Query dispatcher
4. **QueryResult** — Wrapper for query results (could reuse Result<T>)
5. **IdempotencyGuard** — CorrelationId-based command deduplication

---

## Implementation Scope

### Must Have (MVP)

1. `IQuery` interface in `Application/Contract/Query/`
2. `IQueryHandler` interface in `Application/Contract/Handler/`
3. `IQueryBus` interface in `Application/Contract/Bus/`
4. `QueryBus` implementation in `Application/Bus/`
5. `IdempotencyGuard` service in `Application/Behavior/Idempotency/`

### Should Have

1. Base query classes (abstract base for queries)
2. Query handler registration in CommandBus or separate registry

### Won't Have (Phase 6)

- Complex query filtering/sorting
- Pagination at query level (handled by read models)
- Multiple query buses

---

## Technical Considerations

### Idempotency Pattern

Using `CorrelationId` to detect duplicate commands:

```
1. Command arrives with CorrelationId
2. Check idempotency store for existing execution
3. If exists: return cached result
4. If not: execute, store result, return
```

### Query Routing

Queries route to read models (projections), not aggregates:

```
Query → QueryBus → QueryHandler → ReadModel (projection table)
```

### Error Handling

- Queries return Result<T> or dedicated QueryResult
- Commands use existing Result<T> pattern

---

## Dependencies

- **Existing:** CommandBus, ICommandHandler, CorrelationId
- **New:** Query interfaces, IdempotencyGuard
- **External:** None required

---

## Test Strategy

1. **Command routing** — Verify commands route correctly
2. **Query routing** — Verify queries route to read models
3. **Idempotency** — Duplicate commands return cached result
4. **Separation** — No command logic in query handlers

---

## Risks

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Query performance | Medium | Medium | Start simple, optimize later |
| Idempotency store | Low | High | Use PostgreSQL table |
| Over-engineering | Medium | Low | YAGNI — keep interfaces minimal |

---

## Research Status: Complete

**Recommendation:** Proceed to planning with this research as foundation.