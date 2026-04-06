# Phase 1: Integration Test Harness - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-04-04
**Phase:** 01-integration-test-harness
**Areas discussed:** Database, Isolation, Fixtures

---

## Database Connection

| Option | Description | Selected |
|--------|-------------|----------|
| Docker test container | Ephemeral PostgreSQL via Docker — clean slate, CI-friendly | ✓ |
| Existing local PostgreSQL | Local PostgreSQL with test database — simpler but requires setup | |

**User's choice:** Docker test container (Recommended)
**Notes:** CI-friendly, clean slate approach

---

## Test Isolation

| Option | Description | Selected |
|--------|-------------|----------|
| Truncate between tests | Fastest, but may leave dirty state if tests fail | |
| Transaction rollback per test | Slower but guaranteed clean state | |
| Recreate schema per test | Safest, slightly slower | ✓ |

**User's choice:** Recreate schema per test
**Notes:** Safest approach for kernel verification

---

## Test Fixtures

| Option | Description | Selected |
|--------|-------------|----------|
| Inline fixture creation | Create fixtures on demand in tests | |
| YAML/JSON fixture files | Pre-built fixtures loaded from files | ✓ |

**User's choice:** YAML/JSON fixture files
**Notes:** Declarative, versionable, readable

---

## Claude's Discretion

- Docker compose file naming and structure
- Exact fixture file format (YAML vs JSON)
- Migration script location

## Deferred Ideas

None — discussion stayed within phase scope
