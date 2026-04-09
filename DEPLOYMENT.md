# Deployment

## Purpose
This document describes the target deployment model for EPSILON after kernel authority closure is complete. It is not a statement that the current repository is production ready.

## Runtime
- Spiral Framework on RoadRunner
- PostgreSQL as the primary persistence system
- Bootloader-first startup
- explicit request-scope reset between responses

## Startup Order
1. load environment and configuration
2. resolve schema routing and connection settings
3. register DI bindings and Bootloaders
4. compose interceptors and middleware
5. register health probes and workers
6. begin request handling

## Data and Schema Rules
- each bounded context owns a dedicated PostgreSQL schema
- `search_path` must be explicit per context
- cross-schema joins and foreign keys are prohibited
- high-volume tables should be partitioned by tenant or transaction date
- RLS must enforce tenant and role isolation in the database

## Event and Integration Rules
- local consistency is enforced within one transaction and one aggregate boundary
- outbox records must be written in the same transaction as state mutation
- cross-context delivery is asynchronous and idempotent
- compensating actions replace distributed transactions

## Readiness Gate
Do not treat this deployment model as active until the canonical commit pipeline, runtime composition, tenant enforcement, and reset-safe worker lifecycle are all proven by integration tests.

## Frontend Semantic Runtime Gate
- generate `apps/frontend/semantic-manifest.json` during CI after topology, compiler, and AST gates pass
- reject promotion if `.blueprint/ast-violations.json` contains any `E_*` entry
- bind deployed container state to the semantic manifest hash through `EPSILON_FRONTEND_SEMANTIC_HASH`
- expose `POST /health/contract-verify` and reject readiness with `503` on semantic drift
- publish drift counters `epsilon_contract_mismatch_count`, `epsilon_ghost_actions_total`, and `epsilon_state_flattening_detected`
