---
name: Implement Projection Engine (Pull-based)
id: 05-03
wave: 2
autonomous: true
---

# Plan 05-03: Implement Projection Engine (Pull-based)

## Objective
Implement the machinery that tracks device offsets and delivers ordered, incremental projection feeds.

## Tasks
1. Implement `OffsetStore` (Implementation of SDR-008):
    - `getOffset(string $deviceId): int`
    - `updateOffset(string $deviceId, int $offset): void`
2. Implement `ProjectionEngine`:
    - Logic to orchestrate `IEventProjector` and `IMobileSyncFeed`.
    - Ensure delivery is incremental and ordered.
3. Integrate with the `SDR-009` (Mobile Projection Feed) surface.

## Verification
- Device cursors are updated atomically.
- Delivery is resumable from the last confirmed offset.
- PHPStan Level 9 compliance.
