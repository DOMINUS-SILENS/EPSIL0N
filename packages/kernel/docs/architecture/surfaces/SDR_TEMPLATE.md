# Surface Declaration Record (SDR)

## 1. Surface Name
<name>

## 2. Classification
- [ ] Foundational
- [ ] Operational
- [ ] Boundary

## 3. Owned Invariant
<single sentence>
What unique truth, guarantee, or failure boundary does this surface own?

## 4. Non-Ownership
<single sentence>
What does this surface explicitly NOT own?

## 5. Source Class
- [ ] Primary Truth
- [ ] Derived / Rebuildable
- [ ] Delivery / Cursor
- [ ] External Contract
- [ ] Operational Metadata

## 6. Reconstruction Rule
How is this surface rebuilt if lost?
If it cannot be rebuilt, justify why it is primary.

## 7. Consistency Contract
What ordering / visibility / durability rules govern reads and writes?

## 8. Consumers
Which runtime components are allowed to read this surface?

## 9. Writers
Which runtime components are allowed to write this surface?

## 10. Drift Risk Analysis
Which existing surface could this accidentally compete with?
Why will drift not occur?

## 11. Failure Impact
If this surface is unavailable or corrupted, what must remain correct?

## 12. Lifecycle
Retention, archival, replay, deletion, migration rules.

## 13. Approval
- Architecture: <required>
- Domain: <if applicable>
- Operations: <if applicable>
