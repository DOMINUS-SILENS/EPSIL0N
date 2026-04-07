# Technology Stack

**Analysis Date:** 2026-04-06

## Languages

**Primary:**
- PHP 8.3+ - Core implementation language for the entire kernel. Enforces strict typing via `declare(strict_types=1)` and high-level static analysis.

## Runtime

**Environment:**
- RoadRunner 2025.1+ - High-performance PHP application server providing PSR-7 compatibility and async capabilities.

**Package Manager:**
- Composer - Dependency management.
- Lockfile: `packages/kernel/composer.lock` present.

## Frameworks

**Core:**
- Spiral Framework 3.x - Provides the DI container, lifecycle management, and core application structure.

**Testing:**
- PHPUnit 11.0 - Standard test runner for Unit and Integration suites.

**Build/Dev:**
- PHPStan 1.10 - Static analysis configured at Level 9 (the strictest level) in `packages/kernel/phpstan.neon`.

## Key Dependencies

**Critical:**
- `ramsey/uuid` 4.7 - Used for generating globally unique identifiers, specifically UUID v7 for time-ordered `EventId`s.
- `nyholm/psr7` 1.8 - PSR-7 HTTP message implementation.

**Infrastructure:**
- `spiral/roadrunner` 2025.1 - Bridge between the PHP application and the RoadRunner server.
- `spiral/roadrunner-bridge` 3.0 - Tooling for testing RoadRunner integration.

## Configuration

**Environment:**
- Configured via `.env` files (template: `packages/kernel/.env.example`).
- Key configs include PostgreSQL connection details (`DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USER`, `DB_PASSWORD`).

**Build:**
- `packages/kernel/composer.json`: Dependency and autoloading definitions.
- `packages/kernel/phpstan.neon`: Static analysis rules and exclusions.
- `packages/kernel/phpunit.xml`: Test suite definitions and environment overrides for the test database.

## Platform Requirements

**Development:**
- PHP 8.3+
- Composer 2.x
- PostgreSQL 14+ (for integration tests)

**Production:**
- RoadRunner server
- PostgreSQL database
- Linux environment (implied by project structure and RoadRunner usage)

---

*Stack analysis: 2026-04-06*
