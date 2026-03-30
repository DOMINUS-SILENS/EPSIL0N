# Guide Développeur : Ajouter un Domaine Métier

Ce guide explique comment implémenter un nouveau domaine métier en utilisant l'architecture Hardened Event Sourcing / CQRS d'EPSILON.

## 1. Définir les Événements de Domaine

Créez vos classes d'événements dans `app/Events`. Chaque événement doit représenter un fait immuable.

```php
namespace App\Events;

class OrderPlaced
{
    public function __construct(
        public string $aggregateId,
        public int $companyId,
        public array $items,
        public float $total
    ) {}
}
```

## 2. Implémenter l'Agrégat

Héritez de `App\Aggregates\AggregateRoot`. Implémentez la logique de validation et les mutations d'état (`apply*`).

### Snapshotting (Performance)
Si votre agrégat génère beaucoup d'événements, implémentez le snapshotting pour accélérer la reconstruction.

```php
class OrderAggregate extends AggregateRoot
{
    protected string $status = 'pending';

    protected function applyOrderPlaced(OrderPlaced $event): void
    {
        $this->status = 'placed';
    }

    protected function toSnapshot(): array
    {
        return ['status' => $this->status];
    }

    protected function fromSnapshot(array $data): void
    {
        $this->status = $data['status'] ?? 'pending';
    }
}
```

## 3. Créer le Projecteur

Héritez de `App\Services\Projector`. Utilisez les méthodes de base pour garantir l'idempotence via `last_event_id`.

```php
class OrderProjector extends Projector
{
    protected string $table = 'orders';

    public function handleOrderPlaced(array $payload, DomainOutbox $event): void
    {
        DB::table($this->table)
            ->where('id', $payload['orderId'])
            ->where('last_event_id', '<', $event->id)
            ->update([
                'status' => 'placed',
                'last_event_id' => $event->id
            ]);
    }
}
```

## 4. Enregistrement

1. **Dispatcher** : Ajoutez votre projecteur dans `App\Services\ProjectionDispatcher`.
2. **SSE** : Mettez à jour `sseClient.ts` sur le frontend pour gérer l'invalidation automatique du cache.

## 🛡️ Règles d'Or
- **Immuabilité** : Un événement ne change jamais. Pour corriger un fait, publiez un événement compensatoire.
- **Idempotence** : Chaque projecteur doit vérifier le `last_event_id` avant d'appliquer une mutation.
- **Atomicité** : La persistence est gérée par l'`OutboxService` au sein d'une transaction unique.

---

## 8. Prochaines Sections

- [01-architecture-generale.md](./01-architecture-generale.md) - Vue d'ensemble
- [02-infrastructure-core.md](./02-infrastructure-core.md) - Services et composants techniques
- [03-domaines-metiers.md](./03-domaines-metiers.md) - Implémentation des 9 macro-domaines
- [04-projections-analytics.md](./04-projections-analytics.md) - Phase C : Dashboards et analytics
- [05-temps-reel-crdt.md](./05-temps-reel-crdt.md) - Phase D : Temps réel et sync mobile
- [06-api-graphql.md](./06-api-graphql.md) - Phase D : API de requête GraphQL
- [07-alerting-observabilite.md](./07-alerting-observabilite.md) - Phase D : Monitoring et alertes
- [08-deployment-operations.md](./08-deployment-operations.md) - Guide de déploiement et maintenance
