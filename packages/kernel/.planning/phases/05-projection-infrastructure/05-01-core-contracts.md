---
name: Define Core Projection Contracts
id: 05-01
wave: 1
autonomous: true
---

# Plan 05-01: Define Core Projection Contracts

## Objective
Establish the base contracts for event projection and the projection engine, ensuring they support the downstream flow of authoritative projections.

## Tasks
1. Define `IEventProjector` interface:
    - `project(DomainEvent $event): void`
    - `getProjectionId(): string`
2. Define `IProjectionEngine` interface:
    - `dispatch(DomainEvent $event): void`
    - `replay(string $projectionId, int $fromVersion): void`

## Verification
- PHPStan Level 9 compliance.
- Contracts correctly define the "downstream" flow of data.
