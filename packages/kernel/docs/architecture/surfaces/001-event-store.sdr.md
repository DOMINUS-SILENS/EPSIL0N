# Surface Declaration Record: Event Store

## 1. Surface Name
Event Store

## 2. Classification
- [x] Foundational
- [ ] Operational
- [ ] Boundary

## 3. Owned Invariant
The immutable, time-ordered sequence of domain events representing the absolute truth of the system.

## 4. Non-Ownership
Does not own projection state or current aggregate snapshots.

## 5. Source Class
- [x] Primary Truth
- [ ] Derived / Rebuildable
- [ ] Delivery / Cursor
- [ ] External Contract
- [ ] Operational Metadata

## 6. Reconstruction Rule
Primary truth; cannot be rebuilt from other surfaces. Loss is catastrophic.

## 7. Consistency Contract
Strict ordering per stream; atomic appends with optimistic concurrency via versioning.

## 8. Consumers
Aggregate repositories, Projectors, Audit tools, Diagnostics.

## 9. Writers
Aggregate repositories (via Command handlers).

## 10. Drift Risk Analysis
Could be confused with a Dispatch Ledger; however, Event Store is for state truth, Ledger is for delivery visibility.

## 11. Failure Impact
System cannot reconstruct state or verify business laws; all projections become untrustworthy.

## 12. Lifecycle
Permanent retention; no deletions; schema evolution via event upgraders.

## 13. Approval
- Architecture: Verified
- Domain: Verified
- Operations: Verified
