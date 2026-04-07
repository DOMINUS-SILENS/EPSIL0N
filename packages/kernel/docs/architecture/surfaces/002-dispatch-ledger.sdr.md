# Surface Declaration Record: Dispatch Ledger

## 1. Surface Name
Dispatch Ledger

## 2. Classification
- [x] Foundational
- [ ] Operational
- [ ] Boundary

## 3. Owned Invariant
The commit-safe visibility of whether a specific event has been successfully delivered to its targets.

## 4. Non-Ownership
Does not own the canonical business truth of the domain.

## 5. Source Class
- [ ] Primary Truth
- [ ] Derived / Rebuildable
- [ ] Delivery / Cursor
- [x] Operational Metadata

## 6. Reconstruction Rule
Can be rebuilt by scanning the Event Store and target delivery logs, though with loss of exact timing metadata.

## 7. Consistency Contract
At-least-once delivery guarantee; eventual consistency for visibility.

## 8. Consumers
Event dispatchers, Retry mechanisms, Monitoring tools.

## 9. Writers
Event dispatcher.

## 10. Drift Risk Analysis
Competes with Event Store for "what happened"; distinguished by focusing on "was it sent" vs "what is the truth".

## 11. Failure Impact
Loss of delivery tracking leading to potential duplicate processing or missed events.

## 12. Lifecycle
Short-to-medium term retention; archived after confirmed delivery to all critical consumers.

## 13. Approval
- Architecture: Verified
- Domain: N/A
- Operations: Verified
