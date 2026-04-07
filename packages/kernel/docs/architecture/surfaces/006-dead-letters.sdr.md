# Surface Declaration Record: Dead Letters

## 1. Surface Name
Dead Letters

## 2. Classification
- [ ] Foundational
- [x] Operational
- [ ] Boundary

## 3. Owned Invariant
The isolated storage of poison events that cannot be processed by a consumer.

## 4. Non-Ownership
Does not own the truth of domain rejection (which is handled by the aggregate).

## 5. Source Class
- [ ] Primary Truth
- [ ] Derived / Rebuildable
- [ ] Delivery / Cursor
- [x] Operational Metadata

## 6. Reconstruction Rule
Primary for the "failure state"; cannot be rebuilt.

## 7. Consistency Contract
Async, out-of-band writes.

## 8. Consumers
Operators, Debugging tools, Manual correction scripts.

## 9. Writers
Event dispatchers, Projectors.

## 10. Drift Risk Analysis
Risk of treating dead letters as a "queue" for reprocessing without correcting the root cause.

## 11. Failure Impact
Loss of visibility into system failures and poison events.

## 12. Lifecycle
Retained until manual resolution or operational archival.

## 13. Approval
- Architecture: Verified
- Domain: N/A
- Operations: Verified
