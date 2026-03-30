# Legacy Drop Manifest

This document identifies all legacy database objects slated for removal after the successful cutover to the Canonical Schema v1. **No object listed here may be dropped until the Phase 4.5 Certification Cutover Gate is passed.**

## Tables Targeted for Removal

| Legacy Table | Replacement Table | Reason | Safe to Drop | Verified By |
| :--- | :--- | :--- | :--- | :--- |
| `article` | `articles` | Replaced by Canonical Schema | No | Cutover Gate |
| `depot` | `depots` | Replaced by Canonical Schema | No | Cutover Gate |
| `article_unite` | `article_units` | Replaced by Canonical Schema | No | Cutover Gate |
| `article_famille` | `article_families` | Replaced by Canonical Schema | No | Cutover Gate |
| `article_marque` | `article_brands` | Replaced by Canonical Schema | No | Cutover Gate |
| `article_mouvement` | `stock_movements` | Replaced by Canonical Schema | No | Cutover Gate |
| `mouvement` | `movements` | Replaced by Canonical Schema | No | Cutover Gate |
| `mouvement_ligne` | `movement_lines` | Replaced by Canonical Schema | No | Cutover Gate |
| `balance_stock` | `stock_balances` | Replaced by Canonical Schema | No | Cutover Gate |
| `device_sync_state` | `device_sync_states`| Replaced by Canonical Schema | No | Cutover Gate |

---

## Columns Targeted for Removal (Partial Table Refactors)

| Table | Legacy Column | Canonical Replacement | Reason |
| :--- | :--- | :--- | :--- |
| `customers` | `company_id` | `entreprise_id` | Multi-tenant unification |
| `event_store` | Legacy SQL structure | Standardized Structure | Optimization (Payloads kept) |

---

## Cleanup Procedures

1. **Verify Phase 4.5**: Ensure `GateACertificationTest` and `gate:profile-b` are Green.
2. **Execute Cleanup Migration**: Run the destructive migration that DROPs these objects.
3. **Verify Codebase**: Ensure no remaining Grep results for legacy table names (except in migration history).
