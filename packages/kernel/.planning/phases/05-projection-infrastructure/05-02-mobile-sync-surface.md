---
name: Implement Mobile Sync Surface Contracts
id: 05-02
wave: 1
autonomous: true
---

# Plan 05-02: Implement Mobile Sync Surface Contracts

## Objective
Define the boundaries for mobile synchronization to prevent internal API leakage and enforce idempotency and cursor-based resumability (Sync Laws 2, 3, 4).

## Tasks
1. Define `IMobileSyncSurface` interface:
    - Methods for handling mobile-safe intake and output.
    - Ensure parameters include `command_id` and `device_id` for idempotency.
2. Define `IMobileSyncFeed` interface:
    - `getDeltas(string $deviceId, int $sinceSyncId): array`
    - `acknowledge(string $deviceId, int $syncId): void`
3. Define the Mobile Command Intake contract:
    - Integration with `SDR-007` (Mobile Command Inbox).
    - Must carry `expected_stream_version` for conflict detection (SDR-010).

## Verification
- Compliance with Sync Law 2 (Idempotency) and Sync Law 3 (Cursors).
- Compliance with SDR-007 and SDR-009.
- PHPStan Level 9 compliance.
