# Technology Stack

**Analysis Date:** 2026-04-04

## Languages

**Primary:**
- PHP 8.3+ - Core implementation language for the entire kernel

**Type System:**
- Strict typing with `declare(strict_types=1)` enforced across all source files
- PHPStan level 9 (highest strictness) for static analysis

## Runtime

**Environment:**
- RoadRunner 2025.1+ - High-performance PHP application server (PSR-7 compatible)
- Spiral Framework 3.x - Application framework providing DI container, HTTP handling, and lifecycle management

**Package Manager:**
- Composer - PHP dependency manager
- Lockfile: composer.lock present

## Frameworks

**Core:**
- Spiral Framework ^3.0 - Application framework for ERP kernel substrate
  - Provides: DI container, bootloader system, lifecycle hooks, queue integration
  - Namespace: `Spiral\Kernel\`

**Testing:**
- PHPUnit ^11.0 - Test framework for unit and integration tests
  - Test suites configured: Unit, Integration
  - Coverage tracking enabled for src/ directory

**Static Analysis:**
- PHPStan ^1.10 - Static analysis tool
  - Level: 9 (maximum strictness)
  - Paths analyzed: src/, tests/
  - Exclusions: src/Support (utility layer)

## Key Dependencies

**Production:**
- `spiral/framework` ^3.0 - Core framework providing DI, lifecycle, HTTP handling
- `spiral/roadrunner` ^2025.1 - RoadRunner integration for application server
- `nyholm/psr7` ^1.8 - PSR-7 HTTP message implementation
- `ramsey/uuid` ^4.7 - UUID generation (v4 standard, v7 for time-ordered EventId)

**Development:**
- `phpunit/phpunit` ^11.0 - Testing framework
- `phpstan/phpstan` ^1.10 - Static analysis
- `spiral/roadrunner-bridge` ^3.0 - RoadRunner testing bridge

## Configuration

**Environment:**
- `.env.example` present - Template for environment configuration
- Environment variables loaded via Spiral bridge
- Test database configuration in `phpunit.xml`:
  - DB_HOST, DB_PORT, DB_DATABASE, DB_USER, DB_PASSWORD

**Build Configuration Files:**
- `packages/kernel/composer.json` - Dependencies and autoloading
- `packages/kernel/phpstan.neon` - Static analysis configuration
- `packages/kernel/phpunit.xml` - Test configuration
- `packages/kernel/rector.php` - (Planned, not yet present)

**Autoloading:**
- Production: `Spiral\Kernel\` → `src/`
- Testing: `Spiral\Kernel\Tests\` → `tests/`

## Platform Requirements

**Development:**
- PHP 8.3+ runtime
- PostgreSQL 14+ database for integration tests
- RoadRunner application server
- Composer 2.x

**Production:**
- RoadRunner application server (high-performance async PHP)
- PostgreSQL database for event store and projections
- PSR-7 compatible HTTP layer

**Architecture Constraints:**
- Domain layer must NOT depend on Spiral framework (framework-agnostic)
- Domain layer must NOT depend on database implementations
- Domain layer must NOT depend on transport (HTTP, queues)
- Infrastructure layer provides implementations for domain contracts

## Build Tools

**Commands:**
```bash
# Install dependencies
cd packages/kernel && composer install

# Run tests
cd packages/kernel && ./vendor/bin/phpunit

# Run specific test suites
cd packages/kernel && ./vendor/bin/phpunit --testsuite Unit
cd packages/kernel && ./vendor/bin/phpunit --testsuite Integration

# Run static analysis (PHPStan level 9)
cd packages/kernel && ./vendor/bin/phpstan analyse
```

## File Structure Summary

```
packages/kernel/
├── src/                    # Spiral\Kernel\ namespace
│   ├── Domain/            # Business-law-neutral truth layer
│   ├── Application/       # Orchestration layer (commands, queries, handlers)
│   ├── Infrastructure/     # Implementation layer
│   ├── Diagnostics/       # Replay/verification/compliance tools
│   └── Support/           # Exceptions, utilities (excluded from PHPStan)
├── tests/
│   ├── Unit/              # Unit tests (domain logic, value objects)
│   ├── Integration/       # Integration tests (database, event store)
│   └── Fixture/           # Test fixtures (aggregates, events, projections)
├── resources/
│   ├── config/            # Configuration files (kernel.php)
│   └── sql/               # Database migrations (event_store/)
├── composer.json
├── phpstan.neon           # Level 9 static analysis
└── phpunit.xml            # PHPUnit 11 configuration
```

---

*Stack analysis: 2026-04-04*