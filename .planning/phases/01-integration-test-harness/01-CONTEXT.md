# Phase 1: Integration Test Harness - Context

**Gathered:** 2026-04-04
**Status:** Ready for planning

<domain>
## Phase Boundary

Create test infrastructure that can actually run against PostgreSQL. This is the foundation for the 7 kernel truth tests — without working test database connectivity, no integration tests can run.

Deliverables:
- Docker-based PostgreSQL test container setup
- Schema migration/bootstrap for tests
- Test isolation (recreate schema per test)
- Fixture loading from YAML/JSON files
- Integration test base class

</domain>

<decisions>
## Implementation Decisions

### Database Connection
- **D-01:** Use Docker test container for PostgreSQL — ephemeral, clean slate, CI-friendly
- **D-02:** Test container should be started/stopped by test suite or make target

### Test Isolation
- **D-03:** Recreate schema per test — safest approach, guaranteed clean state
- **D-04:** Migration scripts run in setup, truncated/dropped in teardown

### Test Fixtures
- **D-05:** Load fixtures from YAML/JSON files — declarative, versionable, readable
- **D-06:** Fixtures located in `tests/Fixture/data/` directory

### Claude's Discretion
- Docker compose file naming and structure
- Exact fixture file format (YAML vs JSON)
- Migration script location

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Project Configuration
- `.planning/config.json` — GSD workflow settings
- `packages/kernel/phpunit.xml` — Test suite configuration
- `packages/kernel/phpstan.neon` — Static analysis rules

### Existing Test Infrastructure
- `packages/kernel/tests/Integration/IntegrationTestCase.php` — Current integration base (placeholder)
- `packages/kernel/tests/KernelTestCase.php` — Unit test base

### Kernel Blueprint
- `Kernel_Foundation/KERNEL_FOUNDATION_BLUEPRINT_PART5_FINAL.md` — Section on testing infrastructure

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `tests/Integration/IntegrationTestCase.php` — Base class exists, needs activation
- `phpunit.xml` — Already has Unit/Integration suite split

### Established Patterns
- PHPUnit 11 for testing
- PHPStan level 9 for static analysis
- Environment variables for database config

### Integration Points
- Tests extend `IntegrationTestCase`
- Tests call `skipIfDatabaseNotAvailable()` when DB not running

</code_context>

<specifics>
## Specific Ideas

- Test container should match production PostgreSQL version (14+)
- Fixtures should support: aggregates, domain events, event streams
- Schema recreation should use migrations from `resources/sql/`

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope

</deferred>

---

*Phase: 01-integration-test-harness*
*Context gathered: 2026-04-04*
