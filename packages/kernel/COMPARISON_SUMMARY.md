# Comparaison codebase vs documentation

## Objectif

Ce document rassemble la comparaison entre l’état réel de la codebase `packages/kernel` et la documentation de contexte disponible (`CLAUDE.md`, `IMPLEMENTATION_STATUS.md`, `CODEBASE_REVIEW.md`, `CODEBASE_INVENTORY.md`).

## Sources analysées

- `packages/kernel/CODEBASE_INVENTORY.md`
- `/home/dominus/Project/EPSILON/CLAUDE.md`
- `/home/dominus/Project/EPSILON/IMPLEMENTATION_STATUS.md`
- `/home/dominus/Project/EPSILON/CODEBASE_REVIEW.md`

## Résumé général

- La codebase implémente les primitives de la phase 1-2 : exceptions, value objects, identités, tenancy.
- La structure attendue pour les couches `Application`, `Infrastructure` et `Diagnostics` existe, mais les fichiers métiers ne sont pas encore présents.
- Les sous-domaines `Domain` autres que `Identity`, `Shared`, `Tenancy` sont actuellement des dossiers vides.

## État réel des dossiers principaux

| Dossier | Attendu par la documentation | Statut réel |
|---|---|---|
| `src/Support/Exception` | Exceptions de base de Phase 1-2 | ✅ 7 fichiers présents |
| `src/Domain/Shared` | Primitives partagées (VO, erreur, result) | ✅ 4 fichiers présents |
| `src/Domain/Identity` | Identités (TenantId, UserId, ActorId, EventId, CorrelationId, CausationId, DocumentId) | ✅ 7 fichiers présents |
| `src/Domain/Tenancy` | Gouvernance tenant/slug/resource | ✅ 3 fichiers présents |
| `src/Application` | Couche applicative attendue | ⚠️ Partiel : 1 fichier présent |
| `src/Infrastructure` | Couche infrastructure attendue | ⚠️ Vide |
| `src/Diagnostics` | Couche diagnostics attendue | ⚠️ Vide |
| `src/Domain/Approval` | Sous-domaine prévu | ⚠️ Vide |
| `src/Domain/Audit` | Sous-domaine prévu | ⚠️ Vide |
| `src/Domain/Authorization` | Sous-domaine prévu | ⚠️ Vide |
| `src/Domain/DocumentIdentity` | Sous-domaine prévu | ⚠️ Vide |
| `src/Domain/Observability` | Sous-domaine prévu | ⚠️ Vide |
| `src/Domain/Serialization` | Sous-domaine prévu | ⚠️ Vide |
| `src/Domain/Temporal` | Sous-domaine prévu | ⚠️ Vide |
| `src/Domain/Workflow` | Sous-domaine prévu | ⚠️ Vide |

## Détails par couche

### Phase 1-2 correctement implémentée

- `src/Support/Exception/` contient les 7 classes d’exception attendues.
- `src/Domain/Shared/` contient :
  - `ValueObject`
  - `ErrorCode`
  - `ErrorDetail`
  - `Result`
- `src/Domain/Identity/` contient :
  - `TenantId`
  - `UserId`
  - `ActorId`
  - `EventId`
  - `CorrelationId`
  - `CausationId`
  - `DocumentId`
- `src/Domain/Tenancy/` contient :
  - `TenantSlug`
  - `EmailAddress`
  - `ResourceReference`

### Couche applicative et infrastructurelle

- `src/Application/` est présent, mais n’a qu’un seul fichier utile : `Service/ErrorDetailFactory.php`.
- `src/Infrastructure/` contient les dossiers attendus, mais aucun fichier PHP concret.
- `src/Diagnostics/` contient les dossiers attendus, mais aucun fichier PHP concret.

## Conclusion

La documentation est cohérente avec l’état réel : la phase 1-2 est livrée et alignée avec les fichiers présents, tandis que les couches suivantes sont encore à implémenter.

### Recommandation

La prochaine itération devrait cibler :

1. `src/Application/` : commandes, handlers, validation, idempotence
2. `src/Infrastructure/` : repository, event store, persistence, audit
3. `src/Diagnostics/` : replay, compliance, projection
