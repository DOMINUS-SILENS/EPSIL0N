# Kernel Architecture Charter

## Persistence Governance Chain

**Persistence is a constitutional act in the EPSILON Kernel. No persisted structure may exist unless it passes Declaration, Classification, Sync Compliance (when applicable), Proof, and Authorization.**

**Persistence is governed by invariant ownership, not implementation convenience.**

**Any persisted structure without an explicit ownership contract is unauthorized architecture.**

---

## EPSILON Synchronization Law

**Any structure or contract that participates in mobile synchronization must prove idempotency, cursor-based resumability, explicit conflict policy, and separation between command intake and authoritative projection delivery.**

### The 5 Sync Laws

#### Sync Law 1 — No Bidirectional Mutable Truth
Mobile clients must never synchronize canonical mutable business state directly.
- **Upstream:** commands / intents
- **Downstream:** authoritative projections / deltas

#### Sync Law 2 — Every Mobile Write Must Be Idempotent
Any mobile-originated mutation must carry a stable client-generated identity and be processable at most once.
- **Required:** `command_id`, `device_id`, `user_id`, `command_type`, `aggregate_id`, `expected_stream_version`.
- No mobile write endpoint may exist unless it proves duplicate safety.

#### Sync Law 3 — Every Mobile Read Must Be Cursor-Based
No mobile synchronization flow may depend on timestamps, heuristics, or “latest known state” guesses.
- **Required:** monotonic sync cursor, durable per-device offset, resumable feed.
- **Forbidden:** `updated_at > last_sync_time`, "download all and diff locally".

#### Sync Law 4 — Sync Feeds Must Be Explicit Surfaces
Mobile synchronization data must be served from a declared synchronization surface, not inferred from arbitrary application APIs.
- No reusing admin APIs or leaking internal storage semantics to the device.

#### Sync Law 5 — Conflict Policy Must Be Declared Per Mutation
Every mobile-capable mutation must explicitly declare its conflict behavior before it may be exposed to offline or retryable clients.
- **Allowed Policies:** append-only, server-authoritative, optimistic concurrency, manual reconciliation.

---

## Enforcement Mechanism: The SDR Gate

No new persistence structure (table, queue, snapshot, cache, outbox, materialized view) may enter the kernel without a signed **Surface Declaration Record (SDR)**.

### The Governance Rule
Any migration that creates a new persisted structure without a corresponding SDR is invalid and must not be merged.

### CI/Merge Policy
If a PR contains:
- SQL migrations creating a new table, materialized view, sequence, or index on a new object.
- Code creating a new queue, outbox, or persistent cache.

The CI/Review process must verify the existence of:
`/docs/architecture/surfaces/<matching-surface>.sdr.md`

---

## Surface Mutation Review (SMR)
Existing surfaces must not be quietly repurposed. Any change to ownership, consumers, or rebuildability triggers a mandatory **Surface Mutation Review (SMR)** to prevent architectural decay.
