# Surface Mutation Review (SMR)

An SMR is required whenever a persisted surface is modified in a way that alters its ownership, semantics, or reliability.

## Trigger Conditions
An SMR must be initiated if any of the following occur:
- Adding columns/fields that change the semantic meaning of the surface.
- Changing the ownership of writes (who is allowed to write).
- Changing the authorized consumers (who is allowed to read).
- Changing the rebuildability (e.g., changing from "Derived" to "Primary Truth").
- Modifying the consistency guarantees (e.g., moving from strong to eventual consistency).

## Review Process
1. **Identification:** The engineer identifies the trigger condition in the PR.
2. **Declaration:** The engineer updates the corresponding SDR and creates an SMR document.
3. **Audit:** Architecture review verifies that the mutation does not introduce drift or violate the Kernel Constitution.
4. **Sign-off:** Approval from Architecture and Domain leads.

## Goal
Prevent the "Quiet Repurposing" of surfaces where a cache evolves into a system of record without explicit architectural intent.
