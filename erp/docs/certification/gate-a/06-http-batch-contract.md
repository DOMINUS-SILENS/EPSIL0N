# Idempotency & Batch HTTP Contracts

**Status**: ✅ Formalized
**Date**: 29 March 2026

## 1. Idempotency HTTP Contract

Ce contrat fixe fermement les règles d'engagement entre le client Mobile (SFA) et le Backend pour la gestion des mutations idempotentes.

### Headers / Champs Requis obligatoires
Pour toute route mutante (POST, PUT, DELETE, PATCH) :
*   `client_mutation_id` (String UUID) : Identifie de façon non équivoque une intention d'écriture du client.
*   `device_id` (String) : Identifie l'origine de la mutation.

### Matrice de Réponses Possibles

*   `201 Created` / `200 OK` : Succès initial de la mutation.
*   `201 Created` / `200 OK` (Retry Exact) : Rejeu parfait d'une mutation *déjà committée*. Le Backend by-pass la validation métier et certifie le retour initial copié depuis `api_idempotency_keys.response_body`.
*   `409 Conflict` (Payload différent) : Le même `client_mutation_id` a été utilisé, mais le calcul du Hachage cryptographique du Payload (`sha256`) diffère. La réjection protège de la corruption.
*   `429 Too Many Requests` (Processing) : Le Backend est **actuellement** en train de processer ce `client_mutation_id`.

### Retry Policy (Directive Mobile)
Le client mobile **DOIT** respecter cette politique stricte sur statut `429` (Processing) :
*   **Backoff** : Exponentiel.
*   **Délai initial** : 500ms (ensuite 1s, 2s, 4s).
*   **Nombre de tentatives max** : 3 retries.
*   **Comportement si toujours processing** : Abandon de la tentative de synchronisation forcée sur cette entité, marquage en "En attente Backend" côté mobile, et réessai dans un cycle réseau ultérieur long. Le Timeout de Lock côté Backend sera purgé ou levé naturellement.


---

## 2. Sync Batch Contract

La synchronisation des lots d'événements offline `/api/sync/ingest` suit une **Option B : Partiellment Accepté**, car il est inacceptable qu'un seul mauvais événement pollue un batch entier de 100 enregistrements valides d'un commercial SFA.

### Atomicity Unit
L'unité atomique n'est **pas le Batch entier**. L'unité atomique est le **Chunk Transactionnel** (paquets de 100) défini dans `SyncBatchService` avec des protections "Per-Event" pre-flight.

1.  **Même mutation dupliquée 2 fois dans le même batch** : Interceptée lors du pré-check `filterExisting()` + Validation in-memory en batch. Une passe entière "ACCEPT" et la 2ème passe "ALREADY_ACKNOWLEDGED".
2.  **Même mutation dupliquée entre deux batches** : Filtre sur la base de données. "ALREADY_ACKNOWLEDGED".
3.  **Collision de payload dans un batch** : Le backend vérifie le schéma et l'intégrité, statut `SCHEMA_INVALID` ou `LATE_SEMANTIC_INVALID`.
4.  **Dépendances inter-mutations** : Assurées par la **Séquence Causale** (`SequenceValidator`). Si un event `N+1` arrive et s'insère, mais l'aggregate a une violation de séquence pour `N+2`, ce dernier reçoit un `CAUSALITY_VIOLATION`.

### Format de Réponse
La réponse contient une matrice de chaque événement pour acquittement par le mobile :
```json
{
  "acked": true,
  "processed": 98,
  "correlation_id": "uuid",
  "results": [
    { "eventId": "ev-1", "status": "ACCEPTED" },
    { "eventId": "ev-2", "status": "ALREADY_ACKNOWLEDGED" },
    { "eventId": "ev-3", "status": "CAUSALITY_VIOLATION" }
  ]
}
```
