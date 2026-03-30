# Audit de Sécurité Technique : Event Sourcing Hardened

Ce document détaille l'audit de sécurité réalisé sur l'infrastructure de persistence et de messaging d'EPSILON.

## 1. Validation des Commandes & Intégrité des Données

- **Principe** : Toutes les entrées utilisateur sont validées via des `FormRequests` Laravel (ex: `StoreLeadRequest`) avant d'atteindre l'agrégat.
- **Audit** : L'agrégat ne reçoit que des données validées. Les méthodes `recordThat()` garantissent que seuls les événements définis peuvent être produits.
- **Statut** : ✅ **CONFORME**

## 2. Isolation des Locataires (Tenancy)

- **Principe** : Utilisation systématique du `tenantId` dans `AggregateRoot` et le sharding de l'`EventStoreService`.
- **Audit** : Le sharding est basé sur un hachage cohérent de l'ID agrégat, assurant que les données d'un locataire ne fuient pas sur d'autres partitions de manière aléatoire.
- **Statut** : ✅ **CONFORME**

## 3. Atomicité & Non-Répudiation

- **Principe** : Pattern Transactional Outbox.
- **Audit** : L'écriture simultanée dans l'Event Store partitionné et le log d'outbox assure qu'aucun événement n'est perdu. L'utilisation d'arbres de Merkle dans l'Event Store garantit l'immuabilité et la détection de toute altération historique.
- **Statut** : ✅ **CONFORME**

## 4. Sécurité des Snapshots

- **Principe** : Sérialisation sécurisée de l'état des agrégats.
- **Audit** : Les snapshots sont stockés sous forme de JSON binaire. 
- **Risque Identifié** : Si un attaquant accède à la table `aggregate_snapshots`, il pourrait injecter un état malveillant.
- **Recommandation** : Ajouter une signature cryptographique (HMAC) sur le payload du snapshot pour vérifier son intégrité lors du chargement.
- **Statut** : ⚠️ **SÉCURISÉ (Moyen)** - Amélioration possible via signature.

## 5. Résilience & Déni de Service (DoS)

- **Principe** : Dead Letter Queue (DLQ).
- **Audit** : L'implémentation de la DLQ empêche un événement malformé ou provoquant un crash de bloquer indéfiniment le pipeline de traitement (Head-of-Line blocking).
- **Statut** : ✅ **CONFORME**

## Conclusion

L'architecture actuelle est **robuste** et suit les meilleures pratiques de sécurité pour les systèmes distribués. La recommandation majeure est la signature des snapshots pour une protection contre les attaques de type "state-injection" en cas de compromission de la base de données.
