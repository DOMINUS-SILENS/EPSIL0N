# Architecture Offline-First SFA ERP – Spécification Technique

Ce document définit l’architecture cible pour un système de vente terrain (SFA) **local-first** avec React, Dexie.js et Laravel.  
Il ne s’agit pas d’une simple « app React avec synchronisation », mais d’un **système distribué transactionnel** où le client mobile possède un **journal d’événements local**, des **projections métier locales**, un **outbox causal**, et où le serveur agit comme **arbitre global** et **source de vérité consolidée**.

---

## 1. Principes fondamentaux

- **Vérité locale opérationnelle** : l’utilisateur peut exécuter ses actions métier (créer commande, réserver stock, enregistrer visite, encaisser) sans réseau.
- **Vérité globale consolidée** : le serveur valide définitivement les événements (stock, quota, crédit, conflits inter‑utilisateurs) et renvoie un acquittement ou un conflit.
- **Séparation stricte** :
  - **Commandes** → intentions métier
  - **Événements** → faits immuables
  - **Projections** → vues optimisées pour l’UI
- **Transactions atomiques locales** : toute modification d’état métier se fait dans une **transaction Dexie** impliquant **event store, aggregate version, outbox, projections**.
- **Causalité préservée** : les événements d’un même agrégat sont séquencés et synchronisés dans l’ordre.
- **Idempotence** : chaque commande / événement porte une clé unique (UUID v7) pour éviter les doublons.
- **Réservation vs consommation** : le stock et les quotas ne sont jamais modifiés directement localement ; ils sont projetés comme **disponibilité = dernier confirmé – réservations locales**.

---

## 2. Architecture globale

```text
┌─────────────────────────────────────────────────────────────────────────────┐
│                                   CLIENT (React)                            │
├─────────────────────────────────────────────────────────────────────────────┤
│  UI → Command → Handler → Dexie Transaction                                 │
│                ├─ append events                                             │
│                ├─ update aggregate_version                                  │
│                ├─ run local projectors                                      │
│                └─ enqueue outbox                                            │
│                                                                             │
│  Sync Engine (background) → POST /api/sync/events                           │
│                                                                             │
│  Projections (Dexie tables) ← UI via useLiveQuery                           │
└─────────────────────────────────────────────────────────────────────────────┘
                                         │
                                         │ HTTPS (batch events)
                                         ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                                   SERVEUR (Laravel)                         │
├─────────────────────────────────────────────────────────────────────────────┤
│  Ingestion : /api/sync/events                                               │
│    ├─ validation payload / signature                                        │
│    ├─ idempotency (idempotency_keys)                                       │
│    ├─ causal sequence check (aggregate_sequences)                          │
│    └─ atomic append to domain_events                                       │
│                                                                             │
│  Event Handlers & Sagas (after persistence)                                 │
│    ├─ business invariants (stock, quota, credit)                           │
│    ├─ start sagas (OrderFulfillment, QuotaAllocation, etc.)                │
│    └─ emit server events                                                   │
│                                                                             │
│  Projectors (read models) : orders, stock_balances, quotas, visits…        │
│                                                                             │
│  Outbox (server) → dispatch to integrations (email, external APIs)         │
│                                                                             │
│  Reconciliation : endpoints to push server corrections / conflicts         │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 3. Modèle de données Dexie (client)

### 3.1 Tables (IndexedDB)

```typescript
// db.ts
import Dexie, { Table } from 'dexie';

export interface Command {
  id: string;            // UUID v7
  aggregateId: string;   // ex: order_123
  type: string;          // 'CreateOrder'
  status: 'pending' | 'processed' | 'failed';
  payload: any;
  createdAt: Date;
}

export interface Event {
  id: string;            // UUID v7
  aggregateId: string;
  aggregateType: string;
  sequence: number;      // local sequence, incrémenté par aggregate
  type: string;
  payload: any;
  occurredAt: Date;
  syncStatus: 'pending' | 'synced' | 'failed';
}

export interface Outbox {
  id: string;
  eventId: string;
  aggregateId: string;
  aggregateType: string;
  sequence: number;
  payload: any;          // copie de l'event pour éviter de recharger
  status: 'pending' | 'sending' | 'acked' | 'failed' | 'dead';
  retryCount: number;
  nextRetryAt: Date | null;
  lastError: string | null;
  createdAt: Date;
  updatedAt: Date;
}

export interface AggregateVersion {
  aggregateId: string;
  aggregateType: string;
  version: number;       // dernière séquence connue localement
  updatedAt: Date;
}

export interface Snapshot {
  aggregateId: string;
  aggregateType: string;
  version: number;       // correspond à la dernière séquence incluse
  state: any;            // état sérialisé de l'agrégat
  updatedAt: Date;
}

export interface Idempotency {
  key: string;           // commandId ou eventId
  createdAt: Date;
}

export interface Conflict {
  id: string;
  aggregateId: string;
  type: string;          // 'stock', 'quota', 'credit', 'order'
  serverReason: string;
  localEventId: string;
  status: 'pending' | 'resolved' | 'discarded';
  detectedAt: Date;
}

// Projections métier (lecture)
export interface Customer {
  id: string;
  code: string;
  name: string;
  assignedTo: string;    // commercial responsable
  creditLimit: number;
  currentCredit: number; // après synchro
  updatedAt: Date;
}

export interface Product {
  id: string;
  sku: string;
  name: string;
  price: number;
  updatedAt: Date;
}

export interface StockView {
  id: string;            // composé productId:warehouseId
  productId: string;
  warehouseId: string;
  serverConfirmedQty: number;   // dernière quantité reçue du serveur
  localPendingReservations: number; // somme des réservations non acked
  availableQty: number;          // calculé = serverConfirmedQty - localPendingReservations
  updatedAt: Date;
}

export interface QuotaView {
  id: string;            // repId:productId
  repId: string;
  productId: string;
  serverConfirmedQuota: number;
  localPendingReservations: number;
  availableQuota: number;
  updatedAt: Date;
}

export interface Order {
  id: string;
  customerId: string;
  repId: string;
  status: 'draft_local' | 'synced' | 'confirmed' | 'rejected';
  totalAmount: number;
  createdAt: Date;
  updatedAt: Date;
}

export interface OrderLine {
  id: string;
  orderId: string;
  productId: string;
  quantity: number;
  unitPrice: number;
}

export interface Visit {
  id: string;
  customerId: string;
  repId: string;
  status: 'started' | 'completed' | 'synced';
  startedAt: Date;
  endedAt?: Date;
  gps?: { lat: number; lng: number };
  notes?: string;
}

export interface Payment {
  id: string;
  customerId: string;
  amount: number;
  method: 'cash' | 'card' | 'transfer';
  status: 'local' | 'synced' | 'confirmed';
  createdAt: Date;
}
```

### 3.2 Définition de la base Dexie

```typescript
class AppDatabase extends Dexie {
  commands!: Table<Command, string>;
  events!: Table<Event, string>;
  outbox!: Table<Outbox, string>;
  aggregate_versions!: Table<AggregateVersion, string>;
  snapshots!: Table<Snapshot, string>;
  idempotency!: Table<Idempotency, string>;
  conflicts!: Table<Conflict, string>;

  // Projections
  customers!: Table<Customer, string>;
  products!: Table<Product, string>;
  stock_view!: Table<StockView, string>;
  quota_view!: Table<QuotaView, string>;
  orders!: Table<Order, string>;
  order_lines!: Table<OrderLine, string>;
  visits!: Table<Visit, string>;
  payments!: Table<Payment, string>;

  constructor() {
    super('SFA_DB');
    this.version(1).stores({
      commands: 'id, aggregateId, type, status, createdAt',
      events: 'id, aggregateId, aggregateType, sequence, type, syncStatus, occurredAt',
      outbox: 'id, eventId, aggregateId, status, nextRetryAt, createdAt',
      aggregate_versions: 'aggregateId, aggregateType, version',
      snapshots: 'aggregateId, aggregateType, version',
      idempotency: 'key, createdAt',
      conflicts: 'id, aggregateId, status, detectedAt',

      customers: 'id, code, name, assignedTo, updatedAt',
      products: 'id, sku, name, updatedAt',
      stock_view: 'id, productId, warehouseId, updatedAt',
      quota_view: 'id, repId, productId, updatedAt',
      orders: 'id, customerId, repId, status, updatedAt',
      order_lines: 'id, orderId, productId',
      visits: 'id, customerId, repId, status, startedAt',
      payments: 'id, customerId, status, createdAt',
    });
  }
}

export const db = new AppDatabase();
```

---

## 4. Commandes locales et événements

### 4.1 Structure de commande

```typescript
interface CreateOrderCommand {
  commandId: string;           // UUID v7
  orderId: string;             // UUID v7
  customerId: string;
  repId: string;
  lines: Array<{
    productId: string;
    quantity: number;
    unitPrice: number;
  }>;
  occurredAt: Date;
}
```

### 4.2 Handler de commande (transactionnel)

```typescript
// domain/order/handlers.ts
import { db } from '../../infra/dexie/db';
import { projectOrderCreated } from './projectors';

export async function handleCreateOrder(cmd: CreateOrderCommand): Promise<void> {
  await db.transaction('rw', [
    db.events,
    db.aggregate_versions,
    db.outbox,
    db.orders,
    db.order_lines,
    db.stock_view,
    db.quota_view,
  ], async () => {
    // 1. Vérifier idempotence
    const existing = await db.idempotency.get(cmd.commandId);
    if (existing) return; // déjà traité

    // 2. Vérifier que l'ordre n'existe pas déjà
    const currentVersion = await db.aggregate_versions.get(cmd.orderId);
    if (currentVersion) throw new Error('Order already exists');

    // 3. (Optionnel) Vérifier invariants locaux : stock / quota disponible
    for (const line of cmd.lines) {
      const stock = await db.stock_view.get(`${line.productId}:main`);
      if (!stock || stock.availableQty < line.quantity) {
        throw new Error(`Stock insuffisant pour ${line.productId}`);
      }
      const quota = await db.quota_view.get(`${cmd.repId}:${line.productId}`);
      if (!quota || quota.availableQuota < line.quantity) {
        throw new Error(`Quota insuffisant pour ${line.productId}`);
      }
    }

    // 4. Créer l'événement
    const event: Event = {
      id: crypto.randomUUID(),
      aggregateId: cmd.orderId,
      aggregateType: 'Order',
      sequence: 1,
      type: 'OrderCreated',
      payload: cmd,
      occurredAt: cmd.occurredAt,
      syncStatus: 'pending',
    };
    await db.events.add(event);

    // 5. Mettre à jour la version de l'agrégat
    await db.aggregate_versions.put({
      aggregateId: cmd.orderId,
      aggregateType: 'Order',
      version: 1,
      updatedAt: new Date(),
    });

    // 6. Exécuter les projecteurs locaux
    await projectOrderCreated(event);

    // 7. Enregistrer dans l'outbox
    await db.outbox.add({
      id: crypto.randomUUID(),
      eventId: event.id,
      aggregateId: cmd.orderId,
      aggregateType: 'Order',
      sequence: 1,
      payload: event.payload,
      status: 'pending',
      retryCount: 0,
      nextRetryAt: null,
      lastError: null,
      createdAt: new Date(),
      updatedAt: new Date(),
    });

    // 8. Enregistrer la clé d'idempotence
    await db.idempotency.add({
      key: cmd.commandId,
      createdAt: new Date(),
    });
  });
}
```

### 4.3 Projecteurs locaux

```typescript
// domain/order/projectors.ts
import { db } from '../../infra/dexie/db';
import { Event } from '../../infra/dexie/db';

export async function projectOrderCreated(event: Event): Promise<void> {
  const payload = event.payload as CreateOrderCommand;

  // Insérer ou mettre à jour la commande
  await db.orders.put({
    id: payload.orderId,
    customerId: payload.customerId,
    repId: payload.repId,
    status: 'draft_local',
    totalAmount: payload.lines.reduce((sum, l) => sum + l.quantity * l.unitPrice, 0),
    createdAt: payload.occurredAt,
    updatedAt: payload.occurredAt,
  });

  // Insérer les lignes
  for (const line of payload.lines) {
    await db.order_lines.put({
      id: crypto.randomUUID(),
      orderId: payload.orderId,
      productId: line.productId,
      quantity: line.quantity,
      unitPrice: line.unitPrice,
    });
  }

  // Mettre à jour les vues de stock / quota (réservations locales)
  for (const line of payload.lines) {
    // Stock
    const stockKey = `${line.productId}:main`;
    const stock = await db.stock_view.get(stockKey);
    if (stock) {
      await db.stock_view.update(stockKey, {
        localPendingReservations: stock.localPendingReservations + line.quantity,
        availableQty: stock.serverConfirmedQty - (stock.localPendingReservations + line.quantity),
        updatedAt: new Date(),
      });
    }

    // Quota
    const quotaKey = `${payload.repId}:${line.productId}`;
    const quota = await db.quota_view.get(quotaKey);
    if (quota) {
      await db.quota_view.update(quotaKey, {
        localPendingReservations: quota.localPendingReservations + line.quantity,
        availableQuota: quota.serverConfirmedQuota - (quota.localPendingReservations + line.quantity),
        updatedAt: new Date(),
      });
    }
  }
}
```

---

## 5. Moteur de synchronisation (Outbox Processor)

Le sync engine est un worker qui tourne en arrière‑plan (Web Worker ou simple setInterval géré avec NetInfo).

### 5.1 Algorithme

```typescript
// infra/sync/outbox.processor.ts
import { db } from '../dexie/db';
import { sendBatchToServer } from './sync.api';
import { markAcked, markFailed } from './outbox.mutations';

const BATCH_SIZE = 50;
const RETRY_DELAYS = [1000, 5000, 30000, 120000, 300000]; // secondes

export async function processOutbox() {
  const now = new Date();

  // 1. Récupérer les events prêts
  const pending = await db.outbox
    .where('status')
    .anyOf(['pending', 'failed'])
    .filter(row => !row.nextRetryAt || row.nextRetryAt <= now)
    .limit(BATCH_SIZE)
    .toArray();

  if (pending.length === 0) return;

  // 2. Grouper par agrégat pour respecter la causalité
  const groups = new Map<string, Outbox[]>();
  for (const row of pending) {
    const key = `${row.aggregateType}:${row.aggregateId}`;
    if (!groups.has(key)) groups.set(key, []);
    groups.get(key)!.push(row);
  }

  // 3. Pour chaque groupe, envoyer séquentiellement
  for (const [_, group] of groups) {
    // Trier par séquence
    const sorted = group.sort((a, b) => a.sequence - b.sequence);

    for (const item of sorted) {
      try {
        // Marquer 'sending' pour éviter double envoi
        await db.outbox.update(item.id, { status: 'sending', updatedAt: new Date() });

        const response = await sendBatchToServer([item]);

        if (response.acked) {
          await markAcked(item.id);
          // Mettre à jour le statut de l'event associé
          await db.events.update(item.eventId, { syncStatus: 'synced' });
        } else if (response.conflict) {
          // Gérer le conflit (voir section conflits)
          await handleConflict(item, response);
        } else {
          throw new Error(response.error || 'Unknown error');
        }
      } catch (err: any) {
        const nextRetryDelay = RETRY_DELAYS[item.retryCount] || 600000;
        await markFailed(item.id, err.message, nextRetryDelay);
      }
    }
  }
}
```

### 5.2 API de synchronisation

```typescript
// infra/sync/sync.api.ts
export async function sendBatchToServer(events: Outbox[]) {
  const payload = {
    deviceId: getDeviceId(),
    userId: getUserId(),
    batchId: crypto.randomUUID(),
    events: events.map(e => ({
      eventId: e.eventId,
      aggregateId: e.aggregateId,
      aggregateType: e.aggregateType,
      sequence: e.sequence,
      type: e.eventType,
      occurredAt: e.payload.occurredAt,
      payload: e.payload,
    })),
  };

  const res = await fetch('/api/sync/events', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  });

  if (!res.ok) {
    const error = await res.json();
    throw new Error(error.message);
  }

  return await res.json(); // { acked: true } ou { conflict: true, reason: ... }
}
```

---

## 6. API Laravel – Ingestion événementielle

### 6.1 Endpoint

```php
Route::post('/api/sync/events', [SyncController::class, 'ingest']);
```

### 6.2 Controller

```php
namespace App\Http\Controllers\Api;

use App\Services\EventStore;
use App\Services\IdempotencyService;
use App\Services\SequenceValidator;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    public function ingest(Request $request, EventStore $eventStore, IdempotencyService $idempotency, SequenceValidator $seqValidator)
    {
        $payload = $request->validate([
            'deviceId' => 'required|string',
            'userId' => 'required|string',
            'batchId' => 'required|string',
            'events' => 'required|array',
            'events.*.eventId' => 'required|string',
            'events.*.aggregateId' => 'required|string',
            'events.*.aggregateType' => 'required|string',
            'events.*.sequence' => 'required|integer',
            'events.*.type' => 'required|string',
            'events.*.occurredAt' => 'required|date',
            'events.*.payload' => 'required|array',
        ]);

        // Vérification d'idempotence globale du batch
        if ($idempotency->exists($payload['batchId'])) {
            return response()->json(['acked' => true]); // déjà traité
        }

        $results = [];

        foreach ($payload['events'] as $event) {
            // Vérifier l'idempotence par eventId
            if ($idempotency->exists($event['eventId'])) {
                $results[] = ['eventId' => $event['eventId'], 'status' => 'already_processed'];
                continue;
            }

            // Vérifier la séquence causale
            if (!$seqValidator->isValid($event['aggregateType'], $event['aggregateId'], $event['sequence'])) {
                $results[] = ['eventId' => $event['eventId'], 'status' => 'out_of_order'];
                continue;
            }

            // Insérer dans l'event store
            $stored = $eventStore->append(
                aggregateType: $event['aggregateType'],
                aggregateId: $event['aggregateId'],
                eventType: $event['type'],
                payload: $event['payload'],
                companyId: auth()->user()->company_id,
                sourceDevice: $payload['deviceId'],
                occurredAt: $event['occurredAt']
            );

            // Marquer idempotence
            $idempotency->record($event['eventId']);

            // Déclencher les handlers métier (sagas, projectors) via event dispatcher
            event(new DomainEventRecorded($stored));

            $results[] = ['eventId' => $event['eventId'], 'status' => 'accepted'];
        }

        // Enregistrer l'idempotence du batch
        $idempotency->record($payload['batchId']);

        return response()->json(['acked' => true, 'results' => $results]);
    }
}
```

### 6.3 Tables serveur nécessaires

```sql
-- Event store
CREATE TABLE domain_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    aggregate_id VARCHAR(255) NOT NULL,
    aggregate_type VARCHAR(255) NOT NULL,
    sequence INT UNSIGNED NOT NULL,
    event_type VARCHAR(255) NOT NULL,
    payload JSON NOT NULL,
    metadata JSON,
    occurred_at DATETIME(6) NOT NULL,
    recorded_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    source_device_id VARCHAR(255),
    source_user_id VARCHAR(255),
    UNIQUE KEY unique_aggregate_sequence (company_id, aggregate_type, aggregate_id, sequence),
    INDEX idx_aggregate (company_id, aggregate_type, aggregate_id)
);

-- Idempotence
CREATE TABLE idempotency_keys (
    key VARCHAR(255) PRIMARY KEY,
    created_at DATETIME NOT NULL
);

-- Séquence validator
CREATE TABLE aggregate_sequences (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    aggregate_type VARCHAR(255) NOT NULL,
    aggregate_id VARCHAR(255) NOT NULL,
    current_sequence INT UNSIGNED NOT NULL DEFAULT 0,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY unique_aggregate (company_id, aggregate_type, aggregate_id)
);

-- Outbox serveur (pour intégrations externes)
CREATE TABLE domain_outbox (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id BIGINT UNSIGNED NOT NULL,
    status ENUM('pending','processing','processed','failed') DEFAULT 'pending',
    retry_count INT DEFAULT 0,
    next_retry_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
);
```

---

## 7. Gestion des conflits

### 7.1 Types de conflits

- **Causalité** : trou de séquence → le serveur rejette l'événement.
- **Intégrité métier** : stock insuffisant, quota dépassé, crédit dépassé → rejet avec raison.
- **Projection stale** : le client a une vue obsolète → le serveur renvoie un snapshot plus récent.

### 7.2 Flux de conflit côté client

1. **Réception du rejet** dans la réponse de `/api/sync/events` (ex : `{ conflict: true, reason: 'Stock insuffisant', correctiveData: {...} }`).
2. **Création d'un enregistrement dans `conflicts`**.
3. **Mise à jour locale** :
   - Si le conflit est sur un ordre, on peut passer l'ordre en `rejected` localement.
   - On peut également récupérer un snapshot mis à jour via un appel dédié (ex : `GET /api/sync/snapshots?aggregateId=...`).
4. **Notification UI** pour que l'utilisateur puisse ajuster sa commande.

### 7.3 Exemple de gestion de conflit

```typescript
// infra/sync/conflict.handler.ts
export async function handleConflict(outboxItem: Outbox, response: any) {
  await db.transaction('rw', db.conflicts, db.outbox, db.events, async () => {
    // 1. Marquer l'outbox comme dead
    await db.outbox.update(outboxItem.id, { status: 'dead', lastError: response.reason });

    // 2. Marquer l'event comme failed
    await db.events.update(outboxItem.eventId, { syncStatus: 'failed' });

    // 3. Créer un conflit
    await db.conflicts.add({
      id: crypto.randomUUID(),
      aggregateId: outboxItem.aggregateId,
      type: response.type,
      serverReason: response.reason,
      localEventId: outboxItem.eventId,
      status: 'pending',
      detectedAt: new Date(),
    });

    // 4. Si nécessaire, rafraîchir la projection depuis le serveur
    await refreshAggregateProjection(outboxItem.aggregateType, outboxItem.aggregateId);
  });
}
```

---

## 8. Vérification d’intégrité locale

Un vérificateur doit être exécuté périodiquement (au démarrage, après une sync) pour s’assurer que la base locale est cohérente.

```typescript
// infra/dexie/integrity.ts
export async function verifyLocalIntegrity(): Promise<string[]> {
  const errors: string[] = [];

  // 1. Tout événement pending doit avoir une ligne outbox correspondante
  const eventsPending = await db.events.where('syncStatus').equals('pending').toArray();
  for (const ev of eventsPending) {
    const outbox = await db.outbox.where('eventId').equals(ev.id).first();
    if (!outbox) errors.push(`Event ${ev.id} has no outbox entry`);
  }

  // 2. Toute outbox non acked doit correspondre à un event
  const outboxRows = await db.outbox.where('status').notEqual('acked').toArray();
  for (const ob of outboxRows) {
    const event = await db.events.get(ob.eventId);
    if (!event) errors.push(`Outbox ${ob.id} points to missing event ${ob.eventId}`);
  }

  // 3. Vérifier la continuité des séquences par aggregate
  const aggregates = await db.aggregate_versions.toArray();
  for (const agg of aggregates) {
    const events = await db.events.where({ aggregateId: agg.aggregateId }).sortBy('sequence');
    for (let i = 0; i < events.length; i++) {
      if (events[i].sequence !== i + 1) {
        errors.push(`Sequence gap in ${agg.aggregateType} ${agg.aggregateId}`);
        break;
      }
    }
  }

  // 4. Vérifier que les projections sont en accord avec les events
  // (ex : somme des réservations locales = localPendingReservations dans stock_view)
  // Ceci peut être fait via des requêtes de vérification.

  return errors;
}
```

---

## 9. Structure de modules recommandée

```
src/
├── app/                     # Routage, providers, etc.
├── infra/
│   ├── dexie/
│   │   ├── db.ts
│   │   ├── migrations.ts
│   │   ├── transactions.ts
│   │   └── integrity.ts
│   ├── sync/
│   │   ├── outbox.processor.ts
│   │   ├── sync.engine.ts
│   │   ├── sync.api.ts
│   │   └── conflict.handler.ts
│   └── eventing/
│       ├── command-bus.ts
│       ├── event-store.ts
│       ├── projector-registry.ts
│       └── idempotency.ts
├── domain/
│   ├── order/
│   │   ├── commands.ts
│   │   ├── events.ts
│   │   ├── handlers.ts
│   │   ├── invariants.ts
│   │   ├── projectors.ts
│   │   └── sagas.ts      # le cas échéant
│   ├── stock/
│   │   ├── commands.ts
│   │   ├── events.ts
│   │   ├── handlers.ts
│   │   └── projectors.ts
│   ├── quota/
│   ├── visit/
│   └── payment/
├── modules/
│   ├── sfa/                # Pages, composants, hooks spécifiques au SFA
│   │   ├── pages/
│   │   ├── hooks/
│   │   └── components/
│   └── shared/             # Composants réutilisables
├── hooks/                  # Hooks génériques (useLiveQuery, useOnlineStatus, etc.)
└── lib/                    # Utilitaires (crypto, date, etc.)
```

---

## 10. Stratégie de replay / rebuild

Pour reconstruire l’état local à partir des événements (par exemple après corruption ou mise à jour de projecteurs) :

```typescript
export async function rebuildProjections() {
  // 1. Vider toutes les tables de projections
  await db.orders.clear();
  await db.order_lines.clear();
  await db.stock_view.clear();
  await db.quota_view.clear();
  // ...

  // 2. Rejouer tous les événements locaux dans l'ordre
  const allEvents = await db.events.orderBy('occurredAt').toArray();
  for (const event of allEvents) {
    const projector = projectorRegistry.get(event.type);
    if (projector) await projector(event);
  }

  // 3. Recalculer les vues stock/quota à partir des réservations locales
  await recalcStockViews();
}
```

---

## 11. Règles absolues à respecter

1. **Aucune écriture directe dans une projection métier** – passez toujours par un événement et son projector.
2. **Toute modification métier locale** doit se faire dans une **transaction Dexie** impliquant tous les artefacts (events, versions, outbox, projections).
3. **Les commandes ne doivent jamais être exécutées sans vérification locale** (invariants structurels et métier basés sur les projections).
4. **L’outbox doit préserver l’ordre causal** – ne jamais envoyer d’événement d’un agrégat si un événement précédent du même agrégat est encore pending.
5. **Idempotence** – chaque commande / événement doit avoir une clé unique enregistrée.
6. **Ne jamais manipuler le stock ou le quota comme simple champ modifiable** – utilisez le pattern **serverConfirmed – localPendingReservations**.
7. **Le serveur est l’arbitre final** – le client peut seulement suggérer, réserver localement, mais ne peut imposer une modification globale.
8. **Les conflits doivent être explicitement gérés** – pas de « silence » ni de « last write wins ».
9. **Les projecteurs locaux et serveurs doivent être idempotents et rejouables** – la reconstruction doit donner le même état que le flux d’événements original.

---

## 12. Conclusion

L’architecture ci‑dessus fournit une base **production‑ready** pour un SFA offline‑first avec une séparation stricte entre l’état local opérationnel et l’état global consolidé. Elle garantit :

- **Traçabilité complète** via l’event sourcing.
- **Résilience réseau** : l’application fonctionne en coupure et synchronise automatiquement.
- **Cohérence métier** grâce aux règles métier serveur et aux sagas.
- **Gestion des conflits** explicite, sans perte de données.
- **Scalabilité** par partitionnement (entreprise) et utilisation de Redis pour les séquences haute performance.

La mise en œuvre se fera par itérations : d’abord les fondations Dexie / event store local, puis l’intégration avec Laravel, les projecteurs et sagas, et enfin la synchronisation complète. Une fois en place, ce système pourra supporter des centaines d’utilisateurs terrain sans interruption de service, même en zones blanches.
