# Requirements — Phase 2.5: Runtime Spine Completion

## v1 Requirements (Phase 2.5)

### Core Integration Tests

- [ ] **TEST-01**: `it_persists_a_new_aggregate_event_stream()` — Proves append works, first event versioning works, stream creation works
- [ ] **TEST-02**: `it_rehydrates_an_aggregate_from_persisted_events()` — Proves replay works, state reconstruction works, event ordering works
- [ ] **TEST-03**: `it_appends_new_events_to_existing_stream_with_correct_version()` — Proves version increments correctly, uncommitted event handling works, append semantics are stable
- [ ] **TEST-04**: `it_rejects_stale_expected_version_writes()` — Proves optimistic concurrency is real, no silent overwrite possible
- [ ] **TEST-05**: `it_rejects_cross_tenant_aggregate_access()` — Proves tenant isolation is enforced structurally, not socially
- [ ] **TEST-06**: `it_roundtrips_event_payload_and_metadata_without_loss()` — Proves serialization/deserialization boundary is valid, metadata schema is stable
- [ ] **TEST-07**: `it_returns_empty_when_stream_does_not_exist()` — Proves repository load semantics are explicit, missing aggregate behavior is not ambiguous

### AggregateRoot Implementation

- [ ] **AGG-01**: `record()` — Record domain event to pending buffer
- [ ] **AGG-02**: `apply()` — Apply event to state, dispatch to handler
- [ ] **AGG-03**: `replay()` — Replay historical events without re-recording
- [ ] **AGG-04**: `version` — Track aggregate version for optimistic concurrency
- [ ] **AGG-05**: `pullUncommittedEvents()` — Extract pending events for persistence

### DomainEvent Contracts

- [ ] **EVT-01**: Event envelope with aggregateId, tenantId, version, occurredAt
- [ ] **EVT-02**: Event name/type for serialization
- [ ] **EVT-03**: Metadata structure (correlationId, causationId, actorId)
- [ ] **EVT-04**: Serialization boundary contract

### EventStore Interface

- [ ] **ES-01**: `append(streamId, events, expectedVersion)` — Append events to stream
- [ ] **ES-02**: `load(streamId, tenantId)` — Load event stream with tenant scoping

### PostgreSQL EventStore Implementation

- [ ] **PG-01**: Stream persistence with unique (streamId, version) constraint
- [ ] **PG-02**: Optimistic concurrency enforcement
- [ ] **PG-03**: Metadata storage
- [ ] **PG-04**: Ordering guarantees
- [ ] **PG-05**: Tenant scoping

### Tenant Isolation

- [ ] **TEN-01**: `TenantIsolationViolationException` — Exception for cross-tenant access
- [ ] **TEN-02**: Tenant-bound repository loading
- [ ] **TEN-03**: Tenant metadata enforcement on append
- [ ] **TEN-04**: Stream ownership verification on load

## v2 Requirements (Deferred)

### Temporal & Financial VOs

- [ ] `Timestamp`
- [ ] `BusinessDate`
- [ ] `BusinessPeriod`
- [ ] `Money`
- [ ] `Currency`
- [ ] `Percentage`

### Event Law (Phase 4)

- [ ] `DomainEvent` full implementation
- [ ] `EventMetadata` full implementation
- [ ] `EventEnvelope` full implementation

### Domain Contracts (Phase 6)

- [ ] `IRepository<T, TId>` interface
- [ ] `IUnitOfWork` interface
- [ ] `ISpecificationRepository`

## Out of Scope

- **UI/UX** — Not until kernel is runnable
- **Projections/read models** — Downstream of working event store
- **HTTP adapters** — Downstream of runtime spine
- **Saga orchestration** — Requires working aggregates
- **Outbox workers** — Requires working event store
- **Snapshots** — Optimization, not required for spine
- **Module generation** — Premature

## Success Criterion

```bash
cd packages/kernel && vendor/bin/phpunit tests/Integration/EventStore
```

All 7 tests pass **without skips**.

---
*Requirements derived from Phase 2.5 Runtime Spine scope*
