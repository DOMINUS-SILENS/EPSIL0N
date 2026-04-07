# Surface Declaration Record: Projection Checkpoints

## 1. Surface Name
Projection Checkpoints

## 2. Classification
- [ ] Foundational
- [x] Operational
- [ ] Boundary

## 3. Owned Invariant
The independent cursor position of a specific projection consumer within the event stream.

## 4. Non-Ownership
Does not own event identity, content, or the actual projected state.

## 5. Source Class
- [ ] Primary Truth
- [ ] Derived / Rebuildable
- [x] Delivery / Cursor
- [ ] External Contract
- [ ] Operational Metadata

## 6. Reconstruction Rule
Rebuildable by replaying the event stream from version 0.

## 7. Consistency Contract
Read-your-writes for the specific projector; eventual consistency for the projected view.

## 8. Consumers
Projectors, Projection health monitors.

## 9. Writers
Projectors.

## 10. Drift Risk Analysis
Could drift if multiple projectors share a checkpoint; strictly 1:1 mapping between projector and checkpoint.

## 11. Failure Impact
Projector restarts from last known good checkpoint or version 0; potentially causing temporary duplicate updates (handled by idempotency).

## 12. Lifecycle
Updated continuously; updated during replay.

## 13. Approval
- Architecture: Verified
- Domain: N/A
- Operations: Verified
