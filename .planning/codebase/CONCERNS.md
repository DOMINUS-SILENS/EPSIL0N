# Codebase Concerns

**Analysis Date:** 2026-04-04

## Implementation Gap

**Phases 0-2 Complete, Phases 3-16 Pending:**
- **Complete:** Package skeleton, exception hierarchy, Result monad, identity/governance value objects (22 PHP files)
- **Not Implemented:** Temporal primitives, financial primitives, event sourcing infrastructure, application layer, PostgreSQL persistence, Spiral integration

**Critical Missing Components (Per Blueprint Part 5 Section 14):**
- `packages/kernel/src/Domain/Temporal/` — BusinessDate, Timestamp, TimezoneId (Phase 3)
- `packages/kernel/src/Domain/Shared/Financial/` — Money, CurrencyCode, Quantity (Phase 3)
- `packages/kernel/src/Domain/Shared/Event/` — DomainEvent, EventMetadata, EventEnvelope (Phase 4)
- `packages/kernel/src/Domain/Shared/Aggregate/` — AggregateRoot base class (Phase 5)
- `packages/kernel/src/Infrastructure/Persistence/` — PostgreSQL event store, repositories (Phase 12)
- `packages/kernel/src/Infrastructure/Spiral/` — Bootloaders, middleware (Phase 13)

**Test Fixture vs Real Implementation Gap:**
- `packages/kernel/tests/Fixture/Aggregate/TestAggregate.php` implements its own event recording (lines 259-286)
- The fixture does NOT extend a real AggregateRoot — it implements event sourcing patterns manually
- Real AggregateRoot abstraction (planned in Phase 5) is not yet available

**Impact:** Bounded contexts cannot be built until Phases 3-5 are complete. The kernel is currently a semantic substrate, not a runtime kernel.

---

## Incomplete Test Infrastructure

**Empty Base Test Case:**
- File: `packages/kernel/tests/KernelTestCase.php`
- Status: 12 lines, contains only `class KernelTestCase extends TestCase {}`
- Missing: Container bootstrap, fixture management, database transactions

**Integration Tests Are Placeholders:**
- File: `packages/kernel/tests/Integration/EventStore/EventStoreTest.php`
- Status: All tests call `$this->skipIfEventStoreNotAvailable()` with TODO comments
- Missing: PostgreSQL connection, event store implementation

**Test Coverage Gap:**
- Unit tests exist for: ValueObjects, Result monad, ErrorCode/ErrorDetail, Exception hierarchy, Aggregate behavior (via fixtures)
- Integration tests: All pending implementation
- No tests for: Event persistence, concurrency, tenant isolation, replay verification

**Impact:** Cannot verify kernel behavior works with real infrastructure. Seven Closure Gates (Blueprint Part 5, Section 14) cannot be validated without integration tests.

---

## Architecture Decisions

**Dependency Law (Frozen — IMPLEMENTATION_STATUS.md lines 178-189):**
- Domain MUST NOT depend on Application or Infrastructure
- Aggregates carry immutable TenantId
- State changes only via domain events
- Authorization checks before aggregate invocation

**Tenant Isolation Rules (Blueprint Part 4, Section 9.5):**
- Every query MUST include tenant filter
- Tenant parameter never optional
- No ambient global tenant state
- Files: `packages/kernel/src/Domain/Identity/TenantId.php` implements the identity primitive

**Event Sourcing Native:**
- All mutations produce domain events
- Optimistic concurrency via Version field
- Replay determinism required (Seven Closure Gates, Gate 4)

**Critical Architecture Note (MEMORY.md):**
> "Your kernel is NOT yet a runtime kernel. It is a semantic domain substrate — primitives with execution mechanics."

---

## Technical Debt

**Placeholder Directories:**
Multiple directories exist but contain no implementations:
- `packages/kernel/src/Domain/Shared/Aggregate/` — Empty
- `packages/kernel/src/Domain/Shared/Entity/` — Empty
- `packages/kernel/src/Domain/Shared/Event/` — Empty
- `packages/kernel/src/Domain/Temporal/` — Empty
- `packages/kernel/src/Infrastructure/Persistence/EventStore/` — Empty
- `packages/kernel/src/Infrastructure/Contract/Persistence/` — Empty

**Test Fixture Duplication:**
- File: `packages/kernel/tests/Fixture/Aggregate/TestAggregate.php`
- Issue: Contains 352 lines of manually implemented aggregate behavior
- Risk: Pattern may diverge from real AggregateRoot when implemented
- Recommendation: Phase 5 implementation should reference TestAggregate patterns for consistency

**Missing Kernel.php Entry Point:**
- Blueprint specifies `packages/kernel/src/Kernel.php` as root entry
- Currently does not exist
- Impact: No centralized kernel initialization

---

## Security Considerations

**Environment Configuration:**
- Files: `packages/kernel/.env` and `packages/kernel/.env.example` exist
- Pattern: Database credentials stored in `.env` files
- Risk Level: Medium — `.env` files are in `.gitignore` (verified via git status showing `.gitignore` deletion pending)
- Recommendation: Ensure `.env` is never committed

**PostgreSQL Connection String:**
- `.env.example` contains: `DB_PASSWORD=password`
- This is a placeholder, but production deployments must rotate credentials

**Blueprint Security Patterns (Not Yet Implemented):**
- ISecurityContext interface — resolves tenant/actor from request context
- IAuthorizationService — verifies permissions before aggregate operations
- ITenantResolver — resolves tenant from JWT/headers/subdomain
- AuthorizationException — proper scoping for auth failures

**Tenant Isolation Enforcement:**
- Currently: Structural via TenantId value object
- Missing: Repository-level enforcement (every query includes tenant filter)
- Missing: Cross-tenant read protection
- Impact: Data isolation depends on developer discipline until Phase 12

---

## Performance Bottlenecks

**Event Sourcing Not Yet Implemented:**
- No event store → no persistence → no performance characteristics
- Snapshot stores not implemented (would cache aggregate state)
- Projection stores not implemented (would cache read models)

**Outbox Pattern Not Implemented:**
- Blueprint defines: Transactional outbox for reliable event publishing
- Impact: Without outbox, dual-write problem can cause event loss
- Files: `packages/kernel/src/Infrastructure/Eventing/Outbox/` — Empty

**Query Optimization (Planned):**
- Blueprint specifies indexes for: aggregate_id, tenant_id, correlation_id, sequence_number
- SQL migration: `packages/kernel/resources/sql/event_store/001_create_event_store.sql` defines schema
- Status: Schema defined, implementation pending

---

## Fragile Areas

**Test Aggregate Coupling:**
- File: `packages/kernel/tests/Fixture/Aggregate/TestAggregate.php`
- Pattern: Standalone implementation with manual event handling
- Risk: Tests may pass with fixture but fail with real AggregateRoot
- Mitigation: Phase 5 should validate fixture patterns match real implementation

**Version Inconsistency Potential:**
- TestAggregate tracks version as integer (line 77)
- Blueprint specifies Version as a value object
- Risk: Version semantics may differ between test and production

**Exception Hierarchy Missing TenantIsolationViolationException:**
- Blueprint Part 5, Phase 1 checklist specifies: `TenantIsolationViolationException.php`
- Current implementation: Does not exist
- File: `packages/kernel/src/Support/Exception/` — Missing this exception type

---

## Scaling Limits

**Current State: Semantic Substrate Only**
- No persistence layer → cannot scale
- No event replay → cannot rebuild state
- No projections → cannot optimize reads

**Post-Implementation Scaling (Per Blueprint):**
- Event Store: PostgreSQL with proper indexing
- Snapshots: Avoid full replay for large aggregates
- Outbox: Batched processing (100 messages per dequeue)
- Audit: Separate table, indexed by aggregate and correlation

**Horizontal Scaling Considerations:**
- RoadRunner workers: Blueprint supports async runtime
- Database connection pooling: PostgreSQL max connections configuration
- Event store append pattern: Optimistic concurrency prevents lost updates

---

## Test Coverage Gaps

**Untested Areas:**
- Event persistence and replay (Integration tests are placeholders)
- Concurrency conflict handling (ConcurrencyConflictException defined but not tested in context)
- Cross-tenant access prevention (Not enforced at repository level)
- Idempotency (IIdempotencyStore defined in blueprint, not implemented)

**Coverage Requirements (Seven Closure Gates):**
1. Structural Completion — 14 sections must exist (currently ~30%)
2. Behavioral Correctness — Tests prove all behaviors (unit tests only)
3. Consumability — New bounded context can use kernel (not possible yet)
4. Determinism — Replay produces identical state (not testable)
5. Tenant & Security Closure — Unauthorized access fails by architecture (not enforced)
6. Operational Safety — Retries, crashes, races don't corrupt (not implemented)
7. Governance Closure — Audit, authority, temporal legality automatic (not implemented)

**Priority Test Gaps:**
| Area | Files | Risk |
|------|-------|------|
| Event Store | `tests/Integration/EventStore/EventStoreTest.php` | High — Core persistence |
| Concurrency | `tests/Integration/Concurrency/ConcurrencyTest.php` | High — Data integrity |
| Tenant Isolation | `tests/Integration/Tenancy/TenancyTest.php` | Critical — Security |
| Replay Verification | `tests/Integration/Replay/ReplayTest.php` | High — Data recovery |

---

## Dependencies at Risk

**External Dependencies (composer.json):**
- `spiral/framework: ^3.0` — Application framework
- `spiral/roadrunner: ^2025.1` — Async runtime
- `ramsey/uuid: ^4.7` — UUID generation
- `nyholm/psr7: ^1.8` — PSR-7 HTTP

**Risk Assessment:**
- All dependencies are stable, actively maintained
- Spiral 3.x is current; RoadRunner 2025.1 is the latest
- No deprecated packages identified

**Development Dependencies:**
- `phpunit/phpunit: ^11.0` — Current major version
- `phpstan/phpstan: ^1.10` — Level 9 static analysis

---

## Missing Critical Features

**AggregateRoot Base Class:**
- Status: Not implemented
- Blocking: All bounded context development
- File: `packages/kernel/src/Domain/Shared/Aggregate/AggregateRoot.php`

**Event Store Interface:**
- Status: Defined in blueprint, not implemented
- Blocking: All persistence operations
- File: `packages/kernel/src/Infrastructure/Contract/EventStore/IEventStore.php`

**Repository Contracts:**
- Status: Defined in blueprint, not implemented
- Blocking: All aggregate persistence
- File: `packages/kernel/src/Infrastructure/Contract/Persistence/IRepository.php`

**Business Calendar:**
- Status: Defined in blueprint, not implemented
- Blocking: Temporal governance (posting periods, backdating rules)
- File: `packages/kernel/src/Infrastructure/Contract/Temporal/IBusinessCalendar.php`

**Authorization Service:**
- Status: Defined in blueprint, not implemented
- Blocking: Permission verification before operations
- File: `packages/kernel/src/Infrastructure/Contract/Security/IAuthorizationService.php`

---

## Known Bugs

**PHASE_1_2_REVIEW.md Contains False Claims:**
- Issue: MEMORY.md explicitly notes false claims in this review document
- False claim: TenantSlug validation logic described incorrectly
- Reality: `packages/kernel/src/Domain/Tenancy/TenantSlug.php` has correct, separate validation checks
- Action: Ignore PHASE_1_2_REVIEW.md, trust actual source code

---

## Documentation Gaps

**README.md Missing:**
- Blueprint specifies `packages/kernel/README.md` should exist
- Currently does not exist
- Impact: New developers need to read CLAUDE.md + Blueprint docs

**No Migration Guide:**
- No documentation for upgrading from Phase 1-2 to Phase 3+
- Blueprint is comprehensive but assumes linear implementation

---

*Concerns audit: 2026-04-04*