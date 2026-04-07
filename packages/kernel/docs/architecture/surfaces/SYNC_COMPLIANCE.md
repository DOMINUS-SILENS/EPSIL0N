# Mobile Sync Compliance Checklist

This checklist must be completed for any PR that affects mobile synchronization. 

- [ ] **Upstream Writes**: Are they command-based intents? (No state overwrites)
- [ ] **Idempotency**: Are all mobile writes idempotent? (Verified `command_id` check)
- [ ] **Inbound Sync**: Is the sync cursor-based? (No timestamp-based authority)
- [ ] **Explicit Feed**: Is the mobile feed a declared surface? (No leaking internal APIs)
- [ ] **Conflict Policy**: Is the conflict behavior declared for every affected mutation?
- [ ] **Boundary**: Does the change avoid exposing internal truth surfaces directly to mobile?
- [ ] **Resumability**: Can the device resume from a durable offset without data loss?

**Verdict:** 
- [ ] **SFA-Safe**: All laws respected.
- [ ] **SFA-Dangerous**: Violates one or more Sync Laws. Must be redesigned.
