# Gate B Incident Report & Remediation Log

## Incident 1 — Synthetic Volume Rejected

**Incident:**
Gate B profiler attempted synthetic `event_store` volume injection.

**Finding:**
This violated certification discipline by mutating the source-of-truth event log and failed under real schema constraints. Injecting arbitrary fake events bypassed the rigorous architecture put in place during Gate A, risking contamination of the immutable ledger, its sequence dependencies, hash chains, projector state tracking, and generated metrics columns such as `tenant_id`.

**Decision:**
`ProfileGateB` was refactored to become strictly read-only. We replaced synthetic volume generation with defensive volume auditing (`checkRealisticVolume()`). The command now simply outputs a warning if the database lacks sufficient statistical samples to generate a realistic `EXPLAIN ANALYZE` output. Synthetic volume generation is now explicitly prohibited on `event_store` and all other source-of-truth tables during evaluation gates.

**Impact:**
Prevents benchmark contamination and preserves the integrity of Event Sourcing certification. Ensures the `EXPLAIN ANALYZE` reports are grounded in genuine data distributions and actual system states, allowing for true metric evaluation.

---

## Incident 2 — Schema Drift Detected

**Incident:**
Gate B profiling failed on Article barcode lookup due to missing expected column `ean13`.

**Finding:**
The profiling assumptions were ahead of the actual schema reality.
This reveals a schema/application drift between optimization claims (modern eloquent model assumptions) and the deployed database structure (which uses `article_ean13` and `article_bar_code`).

**Decision:**
A schema reality audit (Phase B0) is now mandatory before continuing Gate B.
Profiler query families have been refactored to become schema-aware (verifying columns using `Schema::hasColumn(...)`) and will explicitly skip invalid queries instead of crashing.

**Impact:**
Prevents false-positive profiling and forces structural truth before SQL optimization. Identifies the need to reconcile the legacy physical table schema with the business API layer.
