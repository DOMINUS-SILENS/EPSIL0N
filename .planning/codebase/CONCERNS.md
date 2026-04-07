# Codebase Concerns

**Analysis Date:** 2026-04-06

## Tech Debt

**Windsurf API Integration:**
- Issue: The `WindsurfCascadeClient` is currently a stub with a TODO for real API calls.
- Files: `packages/kernel/src/Service/WindsurfCascadeClient.php`
- Impact: The kernel depends on an external service for some operations that are not yet functional.
- Fix approach: Implement the actual HTTP client logic or remove the service if it's not a core kernel requirement.

**Integration Test Gaps:**
- Issue: `RepositoryPersistenceTest` contains multiple TODOs for critical persistence paths (saving, loading, concurrency).
- Files: `packages/kernel/tests/Integration/Repository/RepositoryPersistenceTest.php`
- Impact: High risk of regression in the persistence layer. The "Runtime Spine" is not fully verified.
- Fix approach: Implement the missing test cases to verify aggregate lifecycle.

## Architectural Deviations

**Runtime Spine Gap:**
- Issue: While `AggregateRoot` and `PostgreSqlEventStore` exist, the glue between them (Repositories) is largely missing or stubbed in tests.
- Files: `packages/kernel/src/Infrastructure/Persistence/Repository/`
- Impact: The kernel cannot yet actually "run" a full cycle of Load -> Mutate -> Save.
- Fix approach: Implement a generic `EventSourcedRepository` that utilizes `IEventStore` to reconstitute `AggregateRoot` instances.

## Known Gaps

**Lack of Concrete Aggregates:**
- Issue: Only the base `AggregateRoot` exists. There are no concrete domain aggregates to prove the implementation.
- Files: `packages/kernel/src/Domain/Shared/Aggregate/AggregateRoot.php`
- Impact: The framework is "theoretical" until a real-world ERP aggregate (e.g., `Account`, `Order`) is implemented and tested.
- Fix approach: Create a sample domain aggregate as part of the Runtime Spine verification.

**Serialization Robustness:**
- Issue: The `EventSerializer` is used but its robustness against schema evolution (versioning) is not yet proven by tests.
- Files: `packages/kernel/src/Infrastructure/Persistence/EventStore/EventSerializer.php`
- Impact: Potential for data loss or crashes during event replay if schema versions change.
- Fix approach: Add integration tests specifically for event upcasting/versioning.

## High-Risk Areas

**Optimistic Concurrency implementation:**
- Issue: The `PostgreSqlEventStore` validates versions in PHP code (`validateExpectedVersion`) rather than relying on a database-level unique constraint on `(stream_id, stream_version)`.
- Files: `packages/kernel/src/Infrastructure/Persistence/EventStore/PostgreSqlEventStore.php`
- Why fragile: Under high concurrency, a race condition could occur between the version check and the insert, leading to duplicate versions in the stream.
- Safe modification: Add a UNIQUE constraint to the `event_store` table on `(stream_id, stream_version)` and catch the PDO exception to throw `ConcurrencyConflictException`.

**Tenant Isolation Enforcement:**
- Issue: Tenant isolation is passed as a parameter (`TenantId`) but there is no global "Security Context" enforcement at the infrastructure level to prevent developers from passing the wrong `TenantId`.
- Files: `packages/kernel/src/Infrastructure/Persistence/EventStore/PostgreSqlEventStore.php`
- Why fragile: Relies on the Application layer to always provide the correct `TenantId`.
- Safe modification: Integrate `ISecurityContext` into the persistence layer to verify that the provided `TenantId` matches the authenticated actor's context.

## Scaling Limits

**Linear Event Streams:**
- Problem: `reconstituteFromEvents` iterates through all historical events.
- Files: `packages/kernel/src/Domain/Shared/Aggregate/AggregateRoot.php`
- Limit: Performance degrades as the number of events per aggregate increases.
- Scaling path: Implement snapshotting in the Infrastructure layer (e.g., `SnapshotStore`).

---

*Concerns audit: 2026-04-06*
