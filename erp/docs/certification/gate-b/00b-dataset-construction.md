# Phase B0: Certification Dataset Construction Plan

Cette étape vise à générer le volume indispensable pour valider SQL Profiler (Gate B), tout en préservant l'intégrité absolue de l'Event Sourcing (Hash Chains, Versions, Multi-tenant) mise en place durant la Gate A.

## 1. Remplissage du Référentiel (Fondations SQL)
L'objectif est d'alimenter la base de données relationnelle (read models / tables classiques) pour que les associations et l'intégrité référentielle puissent exister. Cette insertion s'effectuera par batch optimisés (Insert) qui respecteront le schéma réel (`article_ean13`, `entreprise_id`, etc.).
- **50** Users (Commerciaux).
- **20** Depôts.
- **1 000** Customers.
- **5 000** Articles (Catalog).
- **200** `device_sync_state`.

## 2. Remplissage Transactionnel (Event-Sourced Flux)
C'est ici qu'interviennent les règles cardinales. Aucun insert brut dans `event_store` ou `domain_outbox`. Tout événement généré proviendra du coeur de l'application via les Aggregates et les ApplicationServices afin que le hashchain, la validation des versions, l'outbox, le `projector_processed_events` et la distribution JSON métier soient 100% naturels (y compris les colonnes virtuelles JSON `tenant_id`).
- **Commandes Métier** (Orders / Lignes): Utilisation de `OrderApplicationService` ou `SyncBatchService` pour rejouer l'ingestion des payloads SFA/Mobile et générer **10 000+** commandes, ce qui déclenchera la persistance des événements (`OrderCreated`, `OrderLineAdded`).
- **Mouvements & Balances Stock**: Instanciation des Aggregates (ex: `StockAggregate`) pour ajuster le stock sur des flux d'entrées / réservations. Cela va mécaniquement déclencher des projections pour mettre à jour `balance_stock` et `article_mouvements`.

## 3. Plan d'Exécution & Limitations
- L'outil sera une commande interactive Laravel : `php artisan cert:seed-sfa`.
- Je vais insérer un marqueur de progression `ProgressBar` par segment pour ne pas blinder la RAM.
- **Limitation Anticipée** : Instancier et persister 100 000 événements via les Domain Event Handlers (incluant des `hash` SHA-256 et requêtes de contrôle des versions existantes) peut prendre **plusieurs minutes**. Nous assumerons ce "coût" comme étant le prix d'un Audit Zéro-Corruptions.
