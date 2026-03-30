# EPSILON Architecture Control Model

## System Classification

**State:** Transitional Distributed Monolith  
**Mode:** Canonical Migration Under Consistency Risk  
**Verification Status:** Declared

This is the only officially sanctioned description of EPSILON's architecture.

---

## Mandatory Doctrines

### Doctrine 1: Authority is Everything

> **If authority is unclear, the implementation is wrong even if it works.**

Transitional systems often "appear to work" while silently accumulating unrecoverable ambiguity.

### Doctrine 2: Patterns Do Not Make Safety

> **Patterns do not make the system safe. Only provable authority, replay behavior, and invariant preservation do.**

### Doctrine 3: Stock Truth Must Be Explainable

> **No stock number is trusted unless its derivation path is explainable and replayable.**

This governs: UI stock displays, reports, transfer approvals, replenishment logic, field sales availability.

---

## The Only Architecture Question That Matters

For every subsystem, design discussion must terminate at:

> **What is authoritative, and how is it proven?**

If a team cannot answer this in one sentence, the subsystem is not production-safe.

---

## The No Silent Mutation Rule

Any mutation to a canonical business concept must be explainable by one of:

1. Domain command (explicit intent)
2. Domain event (traceable occurrence)
3. Approved reconciliation repair process (auditable, logged)
4. Approved migration/backfill job (scheduled, monitored)

### Forbidden

- Manual SQL fixes in production
- Admin panel "corrections" bypassing event paths
- Emergency controller patches
- "Temporary" direct model updates
- Background jobs mutating state without traceability

If you allow these, event truth becomes fiction.

---

## Stop-Ship Conditions

The following are **release blockers**. If any is true, the change is **not eligible for deployment**.

| Condition | Why It Blocks |
|-----------|---------------|
| Production write path without declared authority | Authority ambiguity corrupts state |
| Stock-affecting path without idempotence proof | Duplicate execution causes drift |
| Projection used operationally without trust level | Operational dependency on ungoverned state |
| New dual-write path without kill criteria | Permanent technical debt creation |
| Conflict resolution using implicit timestamp winner | Data corruption by default |
| Replayable subsystem without rebuild procedure | Unrecoverable failure mode |
| Direct mutation of canonical state outside approved paths | Silent corruption vector |
| Migration step without rollback boundary | Irreversible cutover risk |
| Inventory path failing convergence tests | Stock drift = business failure |
| Finance path failing reconciliation validation | Financial inaccuracy risk |
| Invariant violation in CI/CD | Provable correctness regression |

**Rule:** Stop-ship conditions are not "tracked for later." They block merge, deploy, and cutover.

---

## Architecture-Level Forbidden Language

Ban these phrases in technical review unless accompanied by concrete proof:

| Phrase | Required Proof |
|--------|---------------|
| "eventually consistent" | Conflict semantics document + convergence test |
| "idempotent" | Deduplication key + duplicate test result + verification status |
| "replay-safe" | Replay class (R1/R2/R3) + rebuild procedure + verification status |
| "source of truth" | Authority classification + write path diagram + single writer proof |
| "canonical" | Schema authority register entry + verification status |
| "DDD" | Bounded context map + anti-corruption boundaries |
| "CQRS" | Command/query separation proof + no direct writes |
| "production-ready" | Authority clarity + replay safety + idempotence tests + verification status |
| "just a projection" | Projection trust level + drift detection + verification status |
| "we can rebuild it" | Replay class + rebuild procedure + validation suite + tested recovery |
| "verified" | Verification status (not just documented) |

**Rule:** Any use of these terms must be backed by a concrete mechanism or test.

**Example:**

- Bad: "Stock is idempotent."
- Acceptable: "Stock projection: Verification Status = Partially Verified. Duplicate event test passed (see test_id: stock_dup_001). Full out-of-order replay test scheduled."

---

## Verification Status Model

Every critical claim must have explicit verification status:

| Status | Definition | Evidence Required |
|--------|------------|-------------------|
| **Declared** | Written but unverified | Document exists |
| **Partially Verified** | Some tests / some evidence | Unit tests pass, partial integration tests |
| **Operationally Verified** | Proven under realistic conditions | Load test, chaos test, production shadow |
| **Recovery Verified** | Failure + rebuild path tested | Disaster recovery test completed |

**Example Claims Requiring Status:**
- Stock projection replay safety
- Sync conflict resolution correctness
- Idempotence under retry storm
- Projection drift detection sensitivity
- Cutover rollback procedure

**Governance Rule:** No component may be promoted to higher criticality without corresponding verification status.

---

## Priority Order for Architecture Review

From now on, score changes in this order:

1. **Authority clarity** - Who owns this write?
2. **Replay safety** - Can we rebuild from events?
3. **Idempotence** - What happens on duplicate?
4. **Stock correctness** - Inventory convergence proof
5. **Sync convergence** - Offline consistency model
6. **Migration reversibility** - Can we roll back?
7. **Operational observability** - How do we detect drift?
8. **Only then:** Elegance / abstraction / pattern purity

---

## Final Operational Doctrine

> A subsystem is not considered "done" when its patterns are implemented.
> It is done only when its authority, replay behavior, and failure recovery are provable.

EPSILON does not need more architecture vocabulary.

It needs:
- Fewer ambiguous writes
- Fewer transitional lies
- Stricter authority
- Stronger replay guarantees
- Mathematically boring correctness

---

## Governance Documents

This control model is enforced through the following registers:

1. **SCHEMA_AUTHORITY_REGISTER.md** - Table classifications (A/B/C/D) with ownership
2. **WRITE_AUTHORITY_MATRIX.md** - Domain write paths with verification status
3. **INVENTORY_CONSISTENCY_CONTRACT.md** - Stock governance with failure modes
4. **INVARIANT_REGISTRY.md** - Non-negotiable business and consistency invariants
5. **PROJECTION_TRUST_LEVELS.md** - Projection classifications (P0/P1/P2) with drift detection
6. **REPLAY_CLASSES.md** - Replay categories (R1/R2/R3) with rebuild procedures
7. **SYNC_CONSISTENCY_MODEL.md** - Offline sync governance with conflict semantics
8. **TRANSITION_KILL_CRITERIA.md** - Migration boundaries with kill conditions
9. **MIGRATION_EXIT_CRITERIA.md** - System-level criteria to exit "transition" state

**Rule:** No table may be used in production without a classification in the Schema Authority Register.

---

## Required Merge Gate Checklist

Code review must verify:

### Authority
- [ ] Any new table has authority classification (A/B/C/D)
- [ ] Any modified table retains explicit authority status
- [ ] Any write path has one declared canonical authority
- [ ] No alternate write path was introduced implicitly
- [ ] Owner, Approver, Last Verified Date assigned

### Replay / Rebuild
- [ ] Any event-affecting change defines replay impact
- [ ] Any new/modified projection has rebuild procedure
- [ ] Replay class (R1/R2/R3) is assigned or reaffirmed
- [ ] Verification status declared for critical claims

### Idempotence
- [ ] Any retriable path defines idempotency identity
- [ ] Duplicate execution behavior is tested
- [ ] Idempotency key collision handling documented

### Sync / Conflict
- [ ] Any sync-visible change defines ordering semantics
- [ ] Any merge/conflict path defines explicit domain resolution policy
- [ ] No implicit "timestamp wins" logic in business-critical domains

### Transition Safety
- [ ] Any transitional component has kill criteria
- [ ] Any migration step has rollback boundary
- [ ] Deletion target date specified or exception granted

### Operational Safety
- [ ] Drift detection or invariant checks are updated if affected
- [ ] Observability impact is documented for critical flows
- [ ] Failure modes section updated if applicable

### Critical Domains
- [ ] Any inventory-affecting path includes convergence validation
- [ ] Any finance-affecting path includes reconciliation validation
- [ ] Invariant registry updated for new/modified invariants

### Stop-Ship Verification
- [ ] No stop-ship conditions triggered
- [ ] If exception required: Architecture board approval documented

---

## Enforcement

### Pre-Merge
- CI/CD gates enforce checklist completion
- Static analysis blocks direct table mutations
- Automated tests verify invariant preservation

### Post-Merge
- Architecture board audits weekly
- Drift detection monitors P2 projections
- Reconciliation jobs validate inventory/finance

### Violation Response
- Critical: Immediate rollback
- High: 24-hour fix window
- Medium: Next sprint priority

---

## Standard Ownership Fields (Required in All Registers)

Every entry in governance documents must include:

- **Owner** - Team/individual responsible
- **Approver** - Architecture board member who validated
- **Last Verified Date** - When verification last performed
- **Verification Method** - Test, audit, or review that verified
- **Production Criticality** - Tier 1/2/3
- **Rollback Impact** - What breaks if rolled back
- **Verification Status** - Declared / Partially Verified / Operationally Verified / Recovery Verified

---

**Document Version:** 2.0  
**Effective Date:** Immediate  
**Review Cycle:** Bi-weekly during migration  
**Next Review:** 2026-04-13
