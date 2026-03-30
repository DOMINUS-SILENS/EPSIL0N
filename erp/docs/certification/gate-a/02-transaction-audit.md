# A1 - Transaction Boundary Audit (Couche 1 et 2)

**Statut**: ❌ Non-Conforme (Requiert Refactoring)
**Date**: 29 Mars 2026

## 1. Objectif de l'audit
Vérifier l'invariant critique défini pour la Gate A : 
> "La transaction ne doit pas être dans le contrôleur. Elle doit être dans le Command Handler / Application Service. Et tout changement d'état (EventStore + Outbox) doit être encapsulé dans une même transaction."

## 2. Analyse Statique & Flux Métier (Grep & Trace)

### ✅ Les bons élèves (Conformes)
Ces services respectent parfaitement la frontière transactionnelle forte demandée :

*   **`App\Services\OutboxService::publishDomain()`**  
    **Conforme**. Encapsule proprement l'insertion dans l'Event Store, la table `domain_events` et `domain_outbox` dans une unique transaction atomique. Aucun *side-effect* asynchrone (comme Redis) ne se produit avant la clôture.
*   **`App\Services\SyncBatchService::processBatch()`**  
    **Conforme**. Effectue le *batch processing* du flux de synchronisation mobile (SFA) de manière unifiée avec un verrouillage transactionnel, assurant qu'aucun identifiant séquentiel ou événement n'est orphelin.

### ❌ Les violations graves (À corriger pour fermer la Gate A)
Ces composants exposent des *leaks* transactionnels graves nécessitant un refactoring immédiat :

*   **`App\Http\Controllers\Api\Erp\OrderController`**  
    **Violation d'Architecture**. Les méthodes de mutation (`store`, `update`, `confirm`, `cancel`) manipulent les agrégats métier (`OrderAggregate`) **et** exécutent des `DB::transaction` manuelles directement depuis le contrôleur pour mettre à jour les *Read Models* bypassés (Launch Bridge). 
    *   **Problème** : La transaction est portée au niveau applicatif pur (HTTP) plutôt que par un Service d'Application/Command Handler. L'idempotence et les cas d'erreur de commit laissent le système dans un état ambigu.
*   **`App\Http\Controllers\Api\SyncController::ingestLegacy()`**  
    **Violation d'Architecture**. Le contrôleur embarque une transaction monolithique ultra-complexe de plus de 45 lignes. La validation causale, métier, HTTP et la persistance sont fusionnées sans contrat d'interface défini.

## 3. Plan d'action de Remédiation (Refactoring)

Pour respecter le "Pattern Correct" imposé, nous allons exécuter les actions suivantes :
1.  **Créer des CommandHandlers**: Introduire `CreateOrderHandler`, `ConfirmOrderHandler`, etc.
2.  **Migrer la logique `DB::transaction`** depuis le contrôleur (ex: `OrderController`) vers les nouveaux Handlers.
3.  **Intégrer le Middleware d'idempotence** : Le contrôleur ne sert plus qu'à router et parser le HTTP vers le Handler, qui va commencer par vérifier le statut de la requête existante (`api_idempotency_keys`) avant d'entamer la transaction avec l'Agrégat.
4.  Élimination immédiate du "Launch Bridge" Eloquent direct si possible, ou du moins son enfermement strict à l'intérieur de la même transaction dans le *Command Handler* au lieu du Contrôleur.

---
📝 *Ce document sera mis à jour avec la Couche 3 (Preuves Runtime) dès que le code sera refactorisé et testé.*
