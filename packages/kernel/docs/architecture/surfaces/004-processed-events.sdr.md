# Surface Declaration Record: Processed Events

## 1. Surface Name
Processed Events

## 2. Classification
- [ ] Foundational
- [x] Operational
- [ ] Boundary

## 3. Owned Invariant
The record of specific event IDs already handled by a projector to ensure strict idempotency.

## 4. Non-Ownership
Does not own event ordering or replay truth.

## 5. Source Class
- [ ] Primary Truth
- [ ] Derived / Rebuildable
- [ ] Delivery / Cursor
- [x] Operational Metadata

## 6. Reconstruction Rule
Can be rebuilt from the projected state if the state explicitly tracks handled event IDs.

## 7. Consistency Contract
Atomic update alongside the projection state change.

## 8. Consumers
Projectors.

## 9. Writers
Projectors.

## 10. Drift Risk Analysis
Similar to checkpoints, but tracks individual IDs rather than a sequential cursor.

## 11. Failure Impact
Loss of idempotency guards leading to duplicate state mutations upon replay.

## 12. Lifecycle
Retained for the duration of the current event version/schema; cleared during full projection rebuilds.

## 13. Approval
- Architecture: Verified
- Domain: N/A
- Operations: Verified
