# External Integrations

**Analysis Date:** 2026-04-06

## APIs & External Services

**None detected:**
- The kernel is designed as a foundation substrate; external API integrations are expected to reside in bounded context modules that utilize the kernel, not within the kernel itself.

## Data Storage

**Databases:**
- PostgreSQL
  - Connection: Defined in `packages/kernel/.env.example` and `packages/kernel/phpunit.xml`.
  - Purpose: Used for the Event Store, Projections, and Idempotency tracking.
  - Driver: `pgsql` (as specified in `.env.example`).

**File Storage:**
- Not detected (Local filesystem only for configuration).

**Caching:**
- Not detected (RoadRunner handles some state, but no external cache like Redis is explicitly configured in the kernel core).

## Authentication & Identity

**Auth Provider:**
- Custom/Internal.
- Implementation: The kernel defines the *contracts* and *primitives* for authorization (e.g., `ActorId`, `UserId`, `TenantId` in `packages/kernel/src/Domain/Identity/`), but the actual identity provider integration occurs in the application layer.

## Monitoring & Observability

**Error Tracking:**
- Not detected.

**Logs:**
- Standard PSR-3 logging via Spiral Framework's integration.

## CI/CD & Deployment

**Hosting:**
- RoadRunner application server.

**CI Pipeline:**
- Not explicitly defined in the kernel directory, though PHPUnit and PHPStan are the primary quality gates.

## Environment Configuration

**Required env vars:**
- `DB_DRIVER`: Database driver (e.g., `pgsql`).
- `DB_HOST`: Database host address.
- `DB_PORT`: Database port.
- `DB_DATABASE`: Database name.
- `DB_USER`: Database username.
- `DB_PASSWORD`: Database password.

**Secrets location:**
- `.env` files (not committed).

## Webhooks & Callbacks

**Incoming:**
- None detected in kernel core.

**Outgoing:**
- None detected in kernel core.

---

*Integration audit: 2026-04-06*
