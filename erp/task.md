# 🚀 Sprint Gate B : SQL Profiling & Plan Validation

**Focus Principal** : Vérifier empiriquement les 12 requêtes chaudes du SFA. Aucune requête ne doit générer de "Write Amplification" désastreuse ou de "Filesort" destructeur.

## 🛠️ Phase B1 - Inventaire & Profiling Brut
- `[x]` Réaliser l'inventaire des requêtes (`docs/certification/gate-b/01-query-inventory.md`).
- `[x]` Rédiger le script automatisé de capture des bindings `DB::listen` (`App\Console\Commands\ProfileGateB.php`).
- `[x]` Exécuter `php artisan gate:profile-b` pour récolter la santé initiale de MySQL.
- `[x]` Consolider `docs/certification/gate-b/02-explain-analyze.md`.

## 🛠️ Phase B2 - Exégèse des Plans (Index Audit)
- `[x]` Justifier chaque index impliqué vs full-table scan (`03-index-justification.md`).
- `[x]` Évaluer l'amplification d'écriture sur les tables hyper actives comme `event_store` et `domain_outbox` (`04-write-amplification-risk.md`).
- [x] Phase 2: Model & Scope Refactoring
    - [x] Update User.php
    - [x] Update TenantScope.php
    - [x] Update AuditLog, CreditReservation, Contact, AlertRule
- [x] Phase 3: Business Logic Refactoring
    - [x] Update AggregateRoot.php
    - [x] Update OutboxService.php
    - [x] Update DeltaSyncService.php
- [/] Phase 4: API & Web Layer Refactoring
    - [x] Update Form Requests
    - [x] Update Controllers
    - [/] Update Resources (if any)
- [/] Phase 5: Tooling & Tests
    - [ ] Update AuditSchema command
    - [/] Update ProfileGateB command
    - [ ] Update SeedCertificationDataset command
- [/] Phase 6: Massive Refactoring
    - [/] Replace companyId with entrepriseId in app/
    - [/] Replace company_id with entreprise_id in app/
    - [ ] Replace companyId/company_id in database/
- [ ] Phase 7: Final Verification
    - [ ] Run gate:schema-audit
    - [ ] Run cert:seed-sfa --clean
    - [ ] Run full system check (VerifySystem)

## 🛠️ Phase B3 - Remédiation (SQL Fixing)
- `[ ]` Générer la migration corrective ajoutant les index composites vitaux manquants.
- `[ ]` Supprimer les index toxiques ralentissant l'insertion O(1) de l'EventStore.
- `[ ]` Ré-exécuter le script `ProfileGateB` post-correction.
- `[ ]` Compiler `05-remediation-log.md` documentant chaque *delta*.

---
🔙 **Gate A** : Terminée et Fermée.
🔜 **Gate C** : Verrouillée (en attente clôture Gate B).
