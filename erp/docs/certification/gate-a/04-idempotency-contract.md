# A3 - Le Contrat d'Idempotence Absolue (Gate A)

**Statut**: ✅ Implémenté (`ApiIdempotencyService` & Migration)
**Date**: 29 Mars 2026

## 1. Description du Contrat
Ce document acte la mise en place de la stratégie d'idempotence stricte sur l'ensemble des requêtes mutantes via la table centralisée `api_idempotency_keys`.

L'idempotence API est traitée comme un "concern transversal d’infrastructure" (infrastructure concern), garantissant que le réseau (offline, retries, duplications) n'altère en rien la vérité métier.

## 2. Table d'Idempotence (Schema)
La migration `2026_03_29_100000_create_api_idempotency_keys_table.php` a imposé le schéma suivant :
*   `endpoint` + `client_mutation_id` : Clé composite Unique `UNIQUE KEY uniq_endpoint_mutation`.
*   `payload_hash` : Vérification cryptographique des collisions.
*   `status` : Machine à état stricte `['processing', 'completed', 'failed']`.
*   `response_body` : Cache du DTO JSON de réponse pour les replays fluides.

## 3. Matrice Comportementale (`ApiIdempotencyService::acquire`)

Le comportement de verrouillage est le suivant (Testé par contrat) :

| Scénario Client | Stratégie Handler (Backend) | Réponse HTTP Attendu |
| :--- | :--- | :--- |
| **Cas 1 : Première Emission** | Insertion `processing`, exécution, update `completed` | `201/200 OK` (Logique de l'agrégat) |
| **Cas 2 : Retry pur (Même Payload)** | Lookup `completed`. Hash correspond. Handler bypassé. | Renvoi DTO initial `201/200 OK` |
| **Cas 3 : Retry sale (Payload différent)** | Hash mismatch vs `payload_hash` stoké. Rejet. | `409 Conflict` (Corruption protocole) |
| **Cas 4 : Concurrence simultanée** | `lockForUpdate()`. Le 1er prend le lock. Le 2nd attend. | Résultat du gagnant ou `429 Conflict` |

## 4. Implémentation Applicative (Application Service)

Tout point d'entrée métier (ex: `OrderApplicationService`) respecte dorénavant le patron suivant :
1. Calcul du hash du payload entrant.
2. Acquérir la clé d'idempotence (Bloque ou Bypass).
3. Ouvrir l'unique transaction applicative (`DB::transaction`).
4. Append `event_store` + `domain_outbox` + `read_models_bridge`.
5. Clôturer et updater la clé d'idempotence en statuant `completed` en dehors de la transaction (évite les annulations de statut sur rollback backend).

Aucun Contrôleur HTTP ne gère de mutation d'état directement.
