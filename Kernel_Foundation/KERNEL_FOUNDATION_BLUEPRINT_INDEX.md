# EPSILONE ERP KERNEL FOUNDATION BLUEPRINT — COMPLETE INDEX

**Status:** Canonical ERP Kernel Foundation, Implementation-Ready
**Target Stack:** PHP 8.3+ | Spiral 3.x | RoadRunner | PostgreSQL
**Architecture:** DDD + OOD + Event-Sourcing Native
**Last Updated:** 2026-04-03

---

## Document Structure

This blueprint is split into 5 files for manageability:

### Part 1: Foundation & Strategy
**File:** `KERNEL_FOUNDATION_BLUEPRINT.md`
- Section 1: Kernel Doctrine
- Section 2: Canonical Package Directory Structure

**Topics:**
- What the kernel owns and why
- Why "kernel first" matters
- Package organization and dependency rules
- Namespace mapping

**Read First:** Yes. This establishes the philosophy and structure.

---

### Part 2: Domain Model Definition
**File:** `KERNEL_FOUNDATION_BLUEPRINT_PART2.md`
- Section 3: Build Order (Implementation Sequence)
- Section 4: Canonical Kernel Domain Model
- Section 5: Aggregate Rules

**Topics:**
- Correct order of construction (why this matters)
- All mandatory value objects (with invariants)
- AggregateRoot / Entity / ValueObject base classes
- Concurrency model (optimistic versioning)
- Aggregate lifecycle and discipline

**Read After:** Part 1. This explains WHAT to build and WHY.

---

### Part 3: Event Store & Application Layer
**File:** `KERNEL_FOUNDATION_BLUEPRINT_PART3.md`
- Section 6: Event Model & Event Store Foundation
- Section 7: Repository + Unit of Work Foundation
- Section 8: Application Boundary Foundation

**Topics:**
- Domain event contracts and versioning
- Event store contracts and PostgreSQL schema
- Repository patterns and tenant isolation
- Command/Query/Handler contracts
- The command execution pipeline (order matters)
- Result pattern and error taxonomy

**Read After:** Part 2. This defines how state changes are captured, persisted, and orchestrated.

---

### Part 4: Governance & Consistency
**File:** `KERNEL_FOUNDATION_BLUEPRINT_PART4.md`
- Section 9: Tenancy / Security / Authority Foundation
- Section 10: Temporal / Approval / Workflow Foundation
- Section 11: Outbox / Inbox / Idempotency Foundation

**Topics:**
- Multi-tenancy contracts and enforcement
- Authorization service and capabilities
- Business calendar and posting legality
- Workflow states and transitions
- Approval aggregate and policies
- Transactional outbox pattern
- Inbox deduplication and command idempotency

**Read After:** Part 3. This defines the governance and consistency guarantees.

---

### Part 5: Operations, Spiral, and Checklist
**File:** `KERNEL_FOUNDATION_BLUEPRINT_PART5_FINAL.md`
- Section 12: Audit / Observability / Diagnostics Foundation
- Section 13: Spiral-Native Integration Strategy
- Section 14: Foundation Implementation Checklist

**Topics:**
- Audit trail contracts
- Observability: tracing, metrics, logging
- Replay verification for determinism
- Spiral bootloaders and middleware
- RoadRunner integration
- Console command integration
- Testing bootstrap
- **EXECUTABLE CHECKLIST** organized by phases

**Read Last:** After understanding all foundations. This is the IMPLEMENTATION ROADMAP.

---

## Reading Path by Role

### For Architects
1. Part 1 (Doctrine + Structure)
2. Part 2 (Build Order + Domain)
3. Part 5 Section 13 (Spiral Integration)

Focus: Understanding design decisions and boundaries.

### For Implementation Leads
1. Part 1 (understand what we're building)
2. Part 5 Section 14 (the checklist)
3. All other sections as reference during implementation

Focus: Executing the checklist in order.

### For Individual Contributors (Building a Feature Module)
1. Part 1 Section 2 (package structure, so you know where to put code)
2. Part 2 Sections 4-5 (aggregate rules, value objects)
3. Part 3 Sections 7-8 (repository contracts, command handlers)
4. Part 4 Sections 9-11 (how to use auth, calendar, outbox)

Focus: Using the kernel correctly in your module.

### For QA / Verification
1. Part 4 Section 11 (idempotency, outbox)
2. Part 5 Section 12 (audit, replay verification)
3. Part 5 Section 14 (Phases 9-10: Tests and Diagnostics)

Focus: Understanding what must be verified.

---

## Key Concepts at a Glance

### Foundational Principles

| Principle | Why | Where Defined |
|-----------|-----|---|
| **Kernel-First** | Foundation determines everything possible later | Part 1, Section 1 |
| **Dependency Inversion** | Domain depends on nothing external | Part 1, Section 2 |
| **Tenant Isolation** | No cross-tenant data leakage | Part 4, Section 9 |
| **Optimistic Concurrency** | Scale better than pessimistic locks | Part 2, Section 5 |
| **Event Sourcing** | Source of truth is events, not current state | Part 3, Section 6 |
| **Transactional Outbox** | Reliable event distribution | Part 4, Section 11 |
| **Audit Automation** | Every mutation is traced | Part 5, Section 12 |
| **Deterministic Replay** | State is verifiable | Part 5, Section 12 |

### Mandatory Abstractions

| Abstraction | Purpose | Delivered By |
|-------------|---------|---|
| `AggregateRoot<TId>` | State container with event sourcing | Kernel Domain |
| `ValueObject` | Immutable, self-validating primitives | Kernel Domain |
| `DomainEvent` | Append-only facts | Kernel Domain |
| `IRepository<T, TId>` | Aggregate persistence | Infrastructure Abstractions |
| `IEventStore` | Event log | Infrastructure Abstractions |
| `IOutboxStore` | Event distribution | Infrastructure Abstractions |
| `ICommand<TResult>` | Intent to mutate | Application Contracts |
| `IAuthorizationService` | Authority verification | Infrastructure Abstractions |
| `IAuditTrail` | Immutable traceability | Infrastructure Abstractions |
| `IBusinessCalendar` | Temporal governance | Infrastructure Abstractions |

### Mandatory Value Objects

**Identity:**
- TenantId, UserId, ActorId, EventId, CorrelationId, CausationId, DocumentId

**Temporal:**
- BusinessDate, BusinessPeriod, Timestamp, TimezoneId

**Financial:**
- Money, CurrencyCode, Quantity, UnitOfMeasure

**Governance:**
- EmailAddress, DocumentNumber, ResourceReference, TenantSlug

### Mandatory Aggregates (Kernel-Provided)

- `Tenant` (minimal, tenant isolation boundary)
- `Actor` (identity and roles)
- `ApprovalRequest` (approval workflows)

### PostgreSQL Key Tables

| Table | Purpose | Defined In |
|-------|---------|---|
| `domain_streams` | Event stream metadata | Part 3, Section 6 |
| `domain_events` | Append-only event log | Part 3, Section 6 |
| `domain_snapshots` | Aggregate state cache | Part 3, Section 6 |
| `outbox_messages` | Integration message queue | Part 4, Section 11 |
| `inbox_processed_messages` | Idempotency tracking | Part 4, Section 11 |
| `idempotency_records` | Command replay cache | Part 4, Section 11 |
| `audit_log` | Immutable audit trail | Part 5, Section 12 |

---

## Implementation Phases (From Part 5, Section 14)

1. **Core Domain Primitives** — Value objects (immutable, self-validating)
2. **Result / Error Model** — Business outcome representation
3. **Domain Model** — Aggregates, entities, events
4. **Application Contracts** — Commands, queries, handlers
5. **Infrastructure Abstractions** — All interfaces, no implementations
6. **Spiral Bootloaders** — Service registration
7. **PostgreSQL Implementations** — Concrete persistence
8. **Database Migrations** — Schema creation
9. **Testing Infrastructure** — Test base classes and fixtures
10. **Diagnostics Tools** — Replay verification, audits
11. **Documentation** — Bounded Context guide
12. **Validation** — Complete test pass and quality gates

---

## Critical Rules (No Exceptions)

### Domain Layer
- ✗ No ORM types in domain
- ✗ No external I/O calls from aggregates
- ✗ No public setters
- ✗ No ambient global tenant state
- ✓ All state changes via explicit methods
- ✓ All mutation leads to domain events
- ✓ All aggregates are event-sourced capable

### Application Layer
- ✗ No domain business logic
- ✗ No direct database access (use repositories)
- ✗ No authorization checks inside aggregates
- ✓ Authorization before aggregate invocation
- ✓ Validation before domain execution
- ✓ All mutations are auditable

### Infrastructure Layer
- ✗ No domain concepts in infrastructure code
- ✗ No HTTP/gRPC concerns in repositories
- ✓ Implements domain contracts only
- ✓ Pluggable (can swap PostgreSQL for another)

### Multi-Tenancy
- ✗ Tenant parameter never optional
- ✗ No cross-tenant reads except explicit authorization
- ✗ No ambient global tenant without explicit scope
- ✓ Every query includes tenant_id filter
- ✓ Every aggregate knows its TenantId

### Concurrency
- ✗ No pessimistic locks
- ✗ No silent overwrites
- ✗ No version conflicts handled invisibly
- ✓ Optimistic versioning with explicit checks
- ✓ ConcurrencyConflictException on version mismatch

---

## FAQ Quick Reference

**Q: Where do I put a new command handler?**
A: `src/Application/Commands/{DomainName}/{CommandName}Handler.php`

**Q: Where do I create a new aggregate?**
A: `src/Domain/{DomainName}/{AggregateName}.php` (e.g., `src/Domain/Tenancy/Tenant.php`)

**Q: How do I ensure cross-tenant isolation?**
A: Every repository method includes `TenantId $tenantId` parameter. Use it in WHERE clause.

**Q: How do I handle saga-style workflows that span aggregates?**
A: Use domain events + sagas. Sagas live in Application layer, listen to events, issue commands.

**Q: How do I verify my implementation is correct?**
A: Run `ReplayVerificationCommand`. If all aggregates replay deterministically, you're correct.

**Q: Can I soft-delete an aggregate?**
A: Only if the domain requires it. Add an `isDeleted` flag, set it via event. All queries must filter it out.

**Q: Where do I put business logic for "I can only approve if credit limit < X"?**
A: In an `ApprovalPolicy` in the Approval aggregate. Authorization service uses this policy.

---

## Integration Checklist for New Bounded Context

When building a new ERP module (e.g., Sales):

- [ ] Create module dir in `/packages/sales` with own composer.json
- [ ] Extend from kernel contracts: `AggregateRoot`, `ValueObject`, `DomainEvent`
- [ ] Create your aggregates in `sales/Domain/`
- [ ] Create your commands in `sales/Application/Commands/`
- [ ] Register handlers in your own bootloader
- [ ] All aggregates must have TenantId
- [ ] All repository calls must include TenantId
- [ ] All events must be versioned (schemaVersion)
- [ ] All mutations must be auditable (IAuditTrail.record)
- [ ] Persistence should use shared kernel repositories (IRepository contract)
- [ ] Cross-context references use `ResourceReference` value object
- [ ] Test everything with `KernelTestCase` base class
- [ ] Run replay verification before merge

---

## Performance Considerations

The kernel is designed for **correctness over speed**, but performance isn't sacrificed:

- **Event Store:** PostgreSQL with proper indexing (sequence_number, tenant_id/stream_id)
- **Snapshots:** Used to avoid full replay for large aggregates
- **Outbox:** Batched processing (100 messages per dequeue)
- **Audit:** Separate table, indexed by aggregate and correlation
- **Queries:** Via specifications, not full table scans
- **Caching:** Projections are cached, rebuilt asynchronously

If performance issues arise, they're visible via traces.

---

## Support & Questions

**Problem:** "My bounded context needs to violate Kernel rule X"
**Solution:** It doesn't. Redesign. Kernel rules are non-negotiable.

**Problem:** "Event versioning is complex"
**Solution:** Yes, but it's a one-time cost. After that, replaying works forever.

**Problem:** "Outbox makes publishing complex"
**Solution:** Yes, but it's the only way to be reliable. Worth it.

**Problem:** "I need to change aggregate after saving"
**Solution:** You can't. If you forgot something, emit a compensating event.

---

## Success Criteria

You've successfully implemented the Kernel when:

1. ✓ All 14 sections are concrete and tested
2. ✓ A new bounded context can be built in < 1 week using only kernel services
3. ✓ All tests pass, including replay verification
4. ✓ No data leaks across tenants
5. ✓ Every state change is auditable and reconstructable
6. ✓ Deterministic replay produces identical state
7. ✓ No business logic is hidden in infrastructure

---

## Document Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2026-04-03 | Initial complete blueprint |

---

## Quick Links to Key Sections

- **Why Kernel Matters:** Part 1, Section 1.4
- **Build Order (MOST IMPORTANT):** Part 2, Section 3
- **Aggregate Discipline:** Part 2, Section 5
- **Event Versioning:** Part 3, Section 6.2
- **Command Pipeline:**Part 3, Section 8.2
- **Tenant Isolation Rules:** Part 4, Section 9.5
- **Transactional Outbox:** Part 4, Section 11.1
- **Replay Verification:** Part 5, Section 12.3
- **Implementation Checklist:** Part 5, Section 14

---

**END OF INDEX**

Start with Part 1. Follow the checklist in Part 5 Section 14.

The blueprint is complete. No more design. Start building.
