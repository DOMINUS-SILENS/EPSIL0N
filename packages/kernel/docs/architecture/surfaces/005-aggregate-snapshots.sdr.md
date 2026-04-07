# Surface Declaration Record: Aggregate Snapshots

## 1. Surface Name
Aggregate Snapshots

## 2. Classification
- [ ] Foundational
- [x] Operational
- [ ] Boundary

## 3. Owned Invariant
The cached state of an aggregate at a specific version to accelerate rehydration.

## 4. Non-Ownership
Does not own the canonical state (which resides in the Event Store).

## 5. Source Class
- [ ] Primary Truth
- [x] Derived / Rebuildable
- [ ] Delivery / Cursor
- [ ] External Contract
- [ ] Operational Metadata

## 6. Reconstruction Rule
Rebuildable by replaying all events from version 0 up to the snapshot version.

## 7. Consistency Contract
Eventually consistent with the Event Store; must be invalidated if the event stream is modified.

## 8. Consumers
Aggregate repositories.

## 9. Writers
Aggregate repositories (during save operations).

## 10. Drift Risk Analysis
Drift occurs if the snapshot is not purged during event stream correction; handled by version mismatch.

## 11. Failure Impact
Slower rehydration (full replay required); no loss of data integrity.

## 12. Lifecycle
Short-term cache; expired based on age or version gap.

## 13. Approval
- Architecture: Verified
- Domain: N/A
- Operations: Verified
