# External Integrations

**Analysis Date:** 2026-04-04

## Database

**PostgreSQL (Primary Data Store):**
- Type: PostgreSQL relational database
- Connection: PDO-based connection via environment variables
- Purpose: Event store, outbox, inbox, idempotency keys, audit log

**Configuration:**
```php
// Environment variables (from phpunit.xml and .env)
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=epsilone_kernel_test
DB_USER=postgres
DB_PASSWORD=password
```

**Schema Location:**
- `packages/kernel/resources/sql/event_store/001_create_event_store.sql`

**Database Tables (Planned/Implemented):**
- `domain_streams` - Aggregate stream metadata
- `domain_events` - Event store (append-only log)
- `domain_snapshots` - Aggregate state snapshots
- `event_store.events` - Event storage
- `event_store.snapshots` - Snapshot storage
- `event_store.outbox` - Outbox pattern for event distribution
- `event_store.inbox` - Inbox pattern for deduplication
- `event_store.idempotency_keys` - Idempotency key tracking
- `event_store.audit_log` - Audit trail

**Integration Test Base:**
- `packages/kernel/tests/Integration/IntegrationTestCase.php`
- PDO connection with transaction cleanup between tests

## External Services

**None Currently Integrated:**

The kernel is designed as a governance substrate without external service dependencies. Future bounded contexts (business modules) will integrate:
- Authentication providers (via contracts in Domain/Authorization/)
- Authorization services (via IAuthorizationService contract)
- External messaging/queues (via outbox/inbox pattern)

## Event Store

**PostgreSQL Event Store:**
- Implementation: Custom event sourcing on PostgreSQL
- Pattern: Append-only event log with optimistic concurrency
- Features:
  - Schema versioning for event evolution
  - Correlation/Causation ID tracking
  - Global sequence ordering
  - Tenant isolation via tenant_id column

**Key Contracts (Blueprint):**
- `IEventStore` - Event persistence contract
- `ISnapshotStore` - Aggregate state snapshots
- `IEventSerializer` - Deterministic JSON serialization

## Outbox/Inbox Pattern

**Transactional Event Publishing:**
- Outbox: Events stored in database before publishing
- Inbox: Deduplication for incoming events/messages
- Guarantees: At-least-once delivery with exactly-once processing

**Purpose:**
- Reliable event distribution
- Idempotent message handling
- Audit trail for all state changes

## Authentication & Identity

**Not Implemented Yet:**

Planned contracts (from blueprint):
- `ISecurityContext` - Current actor/tenant context
- `IAuthorizationService` - Permission verification
- `IActionRequirement` - Authorization requirement specification

**Identity Primitives (Implemented):**
- `TenantId` - Multi-tenant isolation boundary (UUID v4)
- `UserId` - User identifiers (UUID v4)
- `ActorId` - Execution context (user, system, job)
- `EventId` - Domain event identifiers (UUID v7 for time-ordering)
- `CorrelationId` - Request correlation across operations
- `CausationId` - Event causation chain tracking
- `DocumentId` - Document identifiers

## Monitoring & Observability

**Planned Contracts (Not Yet Implemented):**
- `ITracer` - Distributed tracing
- `IMetrics` - Metrics collection
- `ILogger` - Structured logging

**Implementation Location:**
- `packages/kernel/src/Infrastructure/Observability/` (planned)

**Current Status:**
- No external observability integration
- Test infrastructure logs to console via PHPUnit

## CI/CD & Deployment

**Hosting:**
- Target: RoadRunner application server
- Deployment model: Long-running PHP processes

**CI Pipeline:**
- Not configured in this repository

**Build Requirements:**
- PHP 8.3+ runtime
- Composer dependency installation
- PostgreSQL for integration tests

## Environment Configuration

**Required Environment Variables:**
```env
# Database
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=epsilone_kernel
DB_USER=postgres
DB_PASSWORD=<secret>

# Application (planned)
APP_ENV=production
APP_DEBUG=false
```

**Secrets Management:**
- Environment variables (`.env` file, not committed)
- No secrets in code repository

## Webhooks & Callbacks

**None Currently Implemented:**

The kernel provides the outbox pattern for reliable event distribution. Future integrations will:
- Publish events via outbox to external systems
- Handle incoming webhooks via inbox pattern

## Integration Architecture

**Dependency Direction:**
```
Domain (contracts) ← Application (orchestration) ← Infrastructure (implementations)
```

**Key Integration Points:**

| Layer | Purpose | Dependencies |
|-------|---------|--------------|
| `Domain/` | Business law primitives | None (pure PHP) |
| `Application/Contract/` | Interface definitions | Domain only |
| `Infrastructure/Contract/` | Infrastructure interfaces | Domain, Application |
| `Infrastructure/Persistence/` | PostgreSQL implementations | Infrastructure contracts |
| `Infrastructure/Spiral/` | Framework bindings | Spiral Framework |

**Framework Isolation:**
- Spiral Framework is ONLY referenced in `Infrastructure/Spiral/`
- Domain and Application layers are framework-agnostic
- RoadRunner integration via Spiral bridge

---

*Integration audit: 2026-04-04*