# Domaines Métiers - Implémentation des 9 Macro-Domaines

## Table des Matières

1. [Référentiel Articles](#1-référentiel-articles)
2. [Gestion des Stocks](#2-gestion-des-stocks)
3. [Préventes et Mouvements](#3-préventes-et-mouvements)
4. [Missions et Tournées](#4-missions-et-tournées)
5. [Encaissements et Crédits](#5-encaissements-et-crédits)
6. [Moteur de Promotions](#6-moteur-de-promotions)
7. [Optimisation des Routes](#7-optimisation-des-routes)
8. [CRM et Visites](#8-crm-et-visites)
9. [Comptabilité Analytique](#9-comptabilité-analytique)

---

## 1. Référentiel Articles

### 1.1 Modèle de Domaine

```
┌─────────────────────────────────────────────────────────┐
│                    Articleggregate                     │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  + create(data, units, prices)                         │
│  + updateInfo(data)                                    │
│  + addUnit(unit)                                       │
│  + setPrice(priceGroup, unit, amount)                  │
│  + activate()                                          │
│  + deactivate()                                      │
│                                                         │
└─────────────────────────────────────────────────────────┘
                          │
          ┌───────────────┼───────────────┐
          ▼               ▼               ▼
┌─────────────────┐ ┌─────────────┐ ┌─────────────────┐
│ ArticleCreated  │ │ArticleUnits │ │ ArticlePrices   │
│                 │ │  Updated    │ │    Updated      │
└─────────────────┘ └─────────────┘ └─────────────────┘
```

### 1.2 Structure d'un Article

```php
class Article
{
    public int $articleId;
    public string $libelle;
    public ?string $codeBarre;
    public ?string $reference;

    // Classification
    public ?int $familleId;      // Catégorie métier
    public ?int $marqueId;       // Fabricant
    public ?int $groupeClientId; // Tarification spéciale

    // Unités de messe
    public array $units = [];     // [ {unit_id, libelle, coefficient} ]

    // Prix par groupe
    public array $prices = [];    // [ {groupe_id, unit_id, prix_ht} ]

    // État
    public bool $actif = true;
    public ?DateTime $dateCreation;
}
```

### 1.3 Exemple de Création

```php
// Création d'un article avec toutes ses relations
$aggregate = Articleggregate::retrieve($uuid)
    ->create(
        data: [
            'article_id' => 12345,
            'libelle' => 'Eau minérale 1.5L',
            'code_barre' => '1234567890123',
            'reference' => 'EAU-001',
            'famille_id' => 10,  // Boissons
            'marque_id' => 25,   // Marque X
        ],
        units: [
            ['unit_id' => 1, 'libelle' => 'Bouteille', 'coefficient' => 1],
            ['unit_id' => 2, 'libelle' => 'Pack x6', 'coefficient' => 6],
            ['unit_id' => 3, 'libelle' => 'Carton x12', 'coefficient' => 12],
        ],
        prices: [
            // Prix standard
            ['groupe_id' => 1, 'unit_id' => 1, 'prix_ht' => 0.45],
            ['groupe_id' => 1, 'unit_id' => 2, 'prix_ht' => 2.50],
            // Prix grossistes
            ['groupe_id' => 2, 'unit_id' => 1, 'prix_ht' => 0.40],
            ['groupe_id' => 2, 'unit_id' => 3, 'prix_ht' => 4.50],
        ]
    )
    ->persist();

// Événements générés:
// 1. ArticleCreated
// 2. ArticleUnitsUpdated (3 unités)
// 3. ArticlePricesUpdated (4 prix)
```

### 1.4 Agrégats Associés

```php
// ArticleFamilleAggregate - Catégories
class ArticleFamilleAggregate
{
    public function create(array $data): static;
    public function updateLibelle(string $libelle): static;
    public function moveToParent(?int $parentId): static; // Arbre hiérarchique
}

// ArticleMarqueAggregate - Fabricants
class ArticleMarqueAggregate
{
    public function create(array $data): static;
    public function updateLogo(string $logoUrl): static;
}

// ArticleGroupePrixAggregate - Grilles tarifaires
class ArticleGroupePrixAggregate
{
    public function create(array $data): static;
    public function setDefault(): static;
    public function assignToClientGroup(int $clientGroupId): static;
}
```

---

## 2. Gestion des Stocks

### 2.1 Multi-Dépôt avec Réservation

```
┌─────────────────────────────────────────────────────────┐
│                    StockAggregate                       │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  + receive(articleId, depotId, qty, batch)             │
│  + consume(articleId, depotId, qty, reason)          │
│  + transfer(articleId, fromDepot, toDepot, qty)       │
│  + adjust(articleId, depotId, actualQty, reason)      │
│                                                         │
└─────────────────────────────────────────────────────────┘
                          │
          ┌───────────────┼───────────────┬───────────────┐
          ▼               ▼               ▼               ▼
┌─────────────────┐ ┌─────────────┐ ┌─────────────┐ ┌─────────────────┐
│ StockReceived   │ │StockConsumed│ │StockTransfer│ │  StockAdjusted    │
│                 │ │             │ │  red        │ │                  │
└─────────────────┘ └─────────────┘ └─────────────┘ └─────────────────┘
```

### 2.2 Modèle de Stock avec Réservation

```php
// Projection : stock_balances
class StockBalance
{
    public int $articleId;
    public float $quantity;            // Stock physique réel
    public float $reservedQuantity;   // Stock réservé (commandes validées)
    public float $availableQuantity; // quantity - reserved

    // Multi-dépôt
    public array $depotStocks = [];  // [depot_id => quantity]
}

// Exemple de flux
// Initial: quantity=100, reserved=0, available=100

// 1. Commande validée (réservation)
$stock->reserve(quantity: 10);
// Résultat: quantity=100, reserved=10, available=90

// 2. Livraison effectuée (consommation)
$stock->consume(quantity: 10);
// Résultat: quantity=90, reserved=0, available=90

// 3. Annulation commande (libération)
$stock->release(quantity: 10);
// Résultat: quantity=100, reserved=0, available=100
```

### 2.3 Règles de Gestion Stock

```php
class StockAggregate extends AggregateRoot
{
    /**
     * Règle: La quantité ne peut pas être négative.
     */
    public function consume(int $companyId, int $articleId, int $depotId, float $quantity): static
    {
        $current = $this->getDepotStock($articleId, $depotId);

        if ($current < $quantity) {
            throw new StockInsufficientException(
                "Stock insuffisant: {$current} disponible, {$quantity} demandé"
            );
        }

        $this->recordThat(new StockConsumed(...));
        return $this;
    }

    /**
     * Règle: La réservation ne peut pas dépasser le stock disponible.
     */
    public function reserveForMovement(int $movementId): static
    {
        foreach ($this->movementLines as $line) {
            $available = $this->getAvailableStock($line['article_id']);

            if ($available < $line['quantite']) {
                throw new StockReservationFailedException(
                    "Rupture de stock article {$line['article_id']}: " .
                    "disponible {$available}, commandé {$line['quantite']}"
                );
            }
        }

        $this->recordThat(new StockReservedForMovement(...));
        return $this;
    }
}
```

### 2.4 Vue Stock (Projection)

```sql
-- Table de projection pour lectures O(1)
CREATE TABLE stock_balances (
    company_id BIGINT UNSIGNED NOT NULL,
    article_id BIGINT UNSIGNED NOT NULL,
    depot_id BIGINT UNSIGNED NOT NULL,
    quantity DECIMAL(15,4) DEFAULT 0,           -- Stock physique
    reserved_quantity DECIMAL(15,4) DEFAULT 0,  -- Stock réservé
    available_quantity DECIMAL(15,4) GENERATED ALWAYS AS (
        quantity - reserved_quantity
    ) STORED,
    last_event_id BIGINT UNSIGNED NOT NULL,
    updated_at TIMESTAMP,
    PRIMARY KEY (company_id, article_id, depot_id)
);

-- Requête ultra-rapide pour disponibilité
SELECT
    a.article_id,
    a.libelle,
    COALESCE(sb.available_quantity, 0) as disponible
FROM articles a
LEFT JOIN stock_balances sb ON sb.article_id = a.article_id
WHERE a.article_id = ?;
-- Temps d'exécution: < 1ms (index PK)
```

---

## 3. Préventes et Mouvements

### 3.1 Machine à États (Finite State Machine)

```
                    ┌────────────────────────────────────────┐
                    │        Mouvement State Machine         │
                    └────────────────────────────────────────┘

┌─────────┐    create()    ┌───────────┐   validate()   ┌─────────────┐
│  NONE   │ ───────────────▶│   DRAFT   │───────────────▶│  VALIDATED  │
└─────────┘                 └───────────┘               └──────┬──────┘
                                                               │
                              cancel()                         │ deliver()
                              ┌────────┐                       │
                              │        ▼                       ▼
                         ┌─────────┐                    ┌────────────┐
                         │CANCELLED│                    │  DELIVERED │
                         └─────────┘                    └────────────┘

Transitions autorisées:
- NONE → DRAFT (création)
- DRAFT → VALIDATED (validation avec check crédit/stock)
- DRAFT → CANCELLED (annulation)
- VALIDATED → DELIVERED (livraison)
- VALIDATED → CANCELLED (annulation avec libération stock)

Interdit:
- DRAFT → DELIVERED (doit passer par VALIDATED)
- VALIDATED → DRAFT (irréversible)
- DELIVERED → * (terminal)
```

### 3.2 Agrégat Mouvement

```php
class MovementAggregate extends AggregateRoot
{
    protected string $state = 'none';
    protected int $movementId;
    protected array $lines = [];
    protected float $totalHt = 0;
    protected float $totalTtc = 0;

    /**
     * Création d'un mouvement en brouillon.
     */
    public function create(array $data, array $lines): static
    {
        if ($this->state !== 'none') {
            throw new \Exception("Mouvement déjà existant");
        }

        // Calculs des totaux
        $totals = $this->calculateTotals($lines);

        $this->recordThat(new MovementCreated(
            uuid: $this->uuid(),
            movementId: $data['mouvement_id'],
            companyId: $data['company_id'],
            data: $data,
            lines: $lines,
            ...$totals
        ));

        return $this;
    }

    /**
     * Validation avec pipeline de garde-fous.
     */
    public function validate(
        int $companyId,
        int $contactId,
        float $totalOrderAmount
    ): static {
        if ($this->state !== 'draft') {
            throw new \Exception("Seuls les brouillons sont validables");
        }

        // Guard 1: Vérification crédit client
        $this->verifyCreditLimit($companyId, $contactId, $totalOrderAmount);

        // Guard 2: Vérification stock disponible
        $this->verifyStockAvailability();

        // Guard 3: Règles promotionnelles
        $this->applyPromotionsIfAny();

        $this->recordThat(new MovementValidated(
            uuid: $this->uuid(),
            movementId: $this->movementId,
            companyId: $companyId,
            routeId: $this->data['route_id'],
            date: $this->data['date'],
            totalHt: $this->totalHt,
            totalTtc: $this->totalTtc,
            contactId: $contactId,
            lines: $this->enrichLinesWithPricing(),
        ));

        return $this;
    }

    /**
     * Livraison - consume le stock réservé.
     */
    public function deliver(int $companyId): static
    {
        if ($this->state !== 'validated') {
            throw new \Exception("Seuls les validés sont livrables");
        }

        $this->recordThat(new MovementDelivered(
            uuid: $this->uuid(),
            movementId: $this->movementId,
            companyId: $companyId,
            lines: $this->lines,
            deliveredAt: now()->toIso8601String(),
        ));

        return $this;
    }

    /**
     * Annulation avec compensation.
     */
    public function cancel(int $companyId): static
    {
        if (in_array($this->state, ['delivered', 'cancelled'])) {
            throw new \Exception("Impossible d'annuler un mouvement livré ou déjà annulé");
        }

        $this->recordThat(new MovementCancelled(
            uuid: $this->uuid(),
            movementId: $this->movementId,
            companyId: $companyId,
            lines: $this->lines,
            previousState: $this->state,
            reason: request('reason'),
        ));

        return $this;
    }

    // Apply methods
    protected function applyMovementCreated(MovementCreated $event): void
    {
        $this->state = 'draft';
        $this->movementId = $event->movementId;
        $this->lines = $event->lines;
        $this->totalHt = $event->totalHt;
        $this->totalTtc = $event->totalTtc;
    }

    protected function applyMovementValidated(MovementValidated $event): void
    {
        $this->state = 'validated';
    }

    protected function applyMovementDelivered(MovementDelivered $event): void
    {
        $this->state = 'delivered';
    }

    protected function applyMovementCancelled(MovementCancelled $event): void
    {
        $this->state = 'cancelled';
    }
}
```

### 3.3 Projection Movement

```php
class MovementProjector extends Projector
{
    protected string $table = 'mouvements';

    public function handleMovementCreated(array $payload, DomainOutbox $event): void
    {
        DB::table('mouvements')->insert([
            'company_id' => $payload['companyId'],
            'mouvement_id' => $payload['movementId'],
            'contact_id' => $payload['data']['contact_id'],
            'date' => $payload['data']['date'],
            'total_ht' => $payload['totalHt'],
            'total_ttc' => $payload['totalTtc'],
            'status' => 'draft',
            'last_event_id' => $event->id,
            'created_at' => now(),
        ]);

        // Lignes
        foreach ($payload['lines'] as $line) {
            DB::table('mouvement_lignes')->insert([
                'company_id' => $payload['companyId'],
                'mouvement_id' => $payload['movementId'],
                'article_id' => $line['article_id'],
                'quantite' => $line['quantite'],
                'prix_unitaire_ht' => $line['prix_ht'],
                'total_ligne_ht' => $line['quantite'] * $line['prix_ht'],
                'last_event_id' => $event->id,
            ]);
        }
    }

    public function handleMovementValidated(array $payload, DomainOutbox $event): void
    {
        DB::table('mouvements')
            ->where('company_id', $payload['companyId'])
            ->where('mouvement_id', $payload['movementId'])
            ->where('last_event_id', '<', $event->id)
            ->update([
                'status' => 'validated',
                'validated_at' => now(),
                'validator_id' => auth()->id(),
                'last_event_id' => $event->id,
            ]);
    }

    public function handleMovementCancelled(array $payload, DomainOutbox $event): void
    {
        DB::table('mouvements')
            ->where('company_id', $payload['companyId'])
            ->where('mouvement_id', $payload['movementId'])
            ->where('last_event_id', '<', $event->id)
            ->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $payload['reason'],
                'last_event_id' => $event->id,
            ]);
    }
}
```

---

## 4. Missions et Tournées

### 4.1 Cycle de Vie d'une Mission

```
┌─────────┐   create()   ┌─────────┐  load()   ┌─────────┐
│  NONE   │ ────────────▶│ PLANNED │──────────▶│ LOADED  │
└─────────┘              └─────────┘           └────┬────┘
                                                   │
                      ┌────────────────────────────┘
                      │ visitStop()
                      ▼
               ┌────────────┐
               │ IN_PROGRESS│
               └─────┬──────┘
                     │ complete()
                     ▼
               ┌────────────┐
               │  COMPLETED │
               └────────────┘
```

### 4.2 Agrégat Mission

```php
class MissionAggregate extends AggregateRoot
{
    protected string $state = 'none';
    protected int $missionId;
    protected array $points = []; // Points de livraison

    public function create(array $data, array $points): static
    {
        // Optimisation: calculer le plus court chemin
        $optimizedRoute = $this->optimizeRoute($points);

        $this->recordThat(new MissionCreated(
            uuid: $this->uuid(),
            missionId: $data['mission_id'],
            companyId: $data['company_id'],
            routeId: $data['route_id'],
            date: $data['date'],
            data: $data,
            points: $optimizedRoute,
        ));

        return $this;
    }

    /**
     * Chargement du véhicule avec vérification des stocks.
     */
    public function loadPhysicalStock(int $companyId): static
    {
        if ($this->state !== 'planned') {
            throw new \Exception("Mission non planifiée");
        }

        // Vérifier que tout le stock est disponible
        foreach ($this->getStockRequirements() as $req) {
            $available = $this->stockService->getAvailable($req['article_id']);
            if ($available < $req['quantity']) {
                throw new InsufficientStockForMissionException(
                    "Stock manquant pour la mission: article {$req['article_id']}"
                );
            }
        }

        $this->recordThat(new MissionLoaded(
            uuid: $this->uuid(),
            missionId: $this->missionId,
            companyId: $companyId,
            loadedAt: now()->toIso8601String(),
        ));

        return $this;
    }

    /**
     * Visite d'un point de livraison.
     */
    public function visitStop(
        int $companyId,
        int $pointId,
        int $routeId,
        string $visitedAt,
        array $deliveryData
    ): static {
        if (!in_array($this->state, ['loaded', 'in_progress'])) {
            throw new \Exception("Mission non chargée");
        }

        $this->recordThat(new StopVisited(
            uuid: $this->uuid(),
            missionId: $this->missionId,
            companyId: $companyId,
            missionPointId: $pointId,
            routeId: $routeId,
            visitedAt: $visitedAt,
            deliveryData: $deliveryData, // qty_dropped, returns, damages
        ));

        return $this;
    }

    public function complete(int $companyId): static
    {
        if ($this->state !== 'in_progress') {
            throw new \Exception("Mission non démarrée");
        }

        // Générer le rapport de mission
        $report = $this->generateMissionReport();

        $this->recordThat(new MissionCompleted(
            uuid: $this->uuid(),
            missionId: $this->missionId,
            companyId: $companyId,
            report: $report,
        ));

        return $this;
    }

    /**
     * Algorithme d'optimisation de tournée (simplifié).
     */
    private function optimizeRoute(array $points): array
    {
        // Implémentation: Algorithme du plus proche voisin
        // ou algorithme génétique pour des centaines de points

        $start = array_shift($points); // Départ
        $optimized = [$start];
        $remaining = $points;

        while (!empty($remaining)) {
            $last = $optimized[count($optimized) - 1];
            $nearest = $this->findNearest($last, $remaining);
            $optimized[] = $nearest;
            $remaining = array_filter($remaining, fn($p) => $p['id'] !== $nearest['id']);
        }

        return $optimized;
    }
}
```

### 4.3 Projection Mission

```php
class MissionProjector extends Projector
{
    public function handleMissionCreated(array $payload, DomainOutbox $event): void
    {
        DB::table('missions')->insert([
            'company_id' => $payload['companyId'],
            'mission_id' => $payload['missionId'],
            'route_id' => $payload['routeId'],
            'date' => $payload['date'],
            'commercial_id' => $payload['data']['commercial_id'],
            'vehicle_id' => $payload['data']['vehicle_id'],
            'status' => 'planned',
            'last_event_id' => $event->id,
        ]);

        // Points de la mission
        foreach ($payload['points'] as $index => $point) {
            DB::table('mission_points')->insert([
                'company_id' => $payload['companyId'],
                'mission_id' => $payload['missionId'],
                'point_id' => $point['id'],
                'sequence' => $index,
                'contact_id' => $point['contact_id'],
                'mouvement_id' => $point['mouvement_id'],
                'latitude' => $point['latitude'],
                'longitude' => $point['longitude'],
                'estimated_time' => $point['estimated_time'],
                'status' => 'pending',
            ]);
        }
    }

    public function handleStopVisited(array $payload, DomainOutbox $event): void
    {
        DB::table('mission_points')
            ->where('company_id', $payload['companyId'])
            ->where('mission_id', $payload['missionId'])
            ->where('point_id', $payload['missionPointId'])
            ->where('last_event_id', '<', $event->id)
            ->update([
                'status' => 'visited',
                'visited_at' => $payload['visitedAt'],
                'actual_dropped' => $payload['deliveryData']['actual_dropped'] ?? null,
                'returns' => $payload['deliveryData']['returns'] ?? null,
                'gps_coordinates' => json_encode([
                    'lat' => $payload['deliveryData']['lat'] ?? null,
                    'lng' => $payload['deliveryData']['lng'] ?? null,
                ]),
                'last_event_id' => $event->id,
            ]);

        // Mettre à jour le statut de la mission
        DB::table('missions')
            ->where('company_id', $payload['companyId'])
            ->where('mission_id', $payload['missionId'])
            ->update(['status' => 'in_progress']);
    }
}
```

---

## 5. Encaissements et Crédits

### 5.1 Gestion du Crédit Client

```php
class CreditAggregate extends AggregateRoot
{
    /**
     * Enregistre un encaissement et met à jour le solde crédit.
     */
    public function recordPayment(
        int $companyId,
        int $contactId,
        float $amount,
        string $paymentMethod,
        ?int $movementId = null
    ): static {
        $this->recordThat(new PaymentRecorded(
            uuid: $this->uuid(),
            creditId: $this->getNextId(),
            companyId: $companyId,
            contactId: $contactId,
            amount: $amount,
            paymentMethod: $paymentMethod,
            movementId: $movementId,
            recordedAt: now()->toIso8601String(),
        ));

        return $this;
    }

    /**
     * Ajuste le plafond de crédit d'un client.
     */
    public function adjustCreditLimit(
        int $companyId,
        int $contactId,
        float $newLimit,
        string $reason
    ): static {
        $this->recordThat(new CreditLimitAdjusted(
            uuid: $this->uuid(),
            companyId: $companyId,
            contactId: $contactId,
            previousLimit: $this->getCurrentLimit($contactId),
            newLimit: $newLimit,
            reason: $reason,
            adjustedBy: auth()->id(),
        ));

        return $this;
    }
}

// Vérification de crédit (utilisée dans MovementAggregate::validate)
class CreditLimitChecker
{
    public function check(
        int $companyId,
        int $contactId,
        float $orderAmount
    ): void {
        $contact = DB::table('contacts')
            ->where('company_id', $companyId)
            ->where('contact_id', $contactId)
            ->first(['montant_max_credit', 'montant_credit_en_cours']);

        if (!$contact || $contact->montant_max_credit <= 0) {
            return; // Pas de limite
        }

        $currentCredit = $contact->montant_credit_en_cours ?? 0;
        $newTotal = $currentCredit + $orderAmount;

        if ($newTotal > $contact->montant_max_credit) {
            throw new CreditLimitExceededException(
                "Dépassement plafond crédit! " .
                "Limite: {$contact->montant_max_credit}, " .
                "En cours: {$currentCredit}, " .
                "Commande: {$orderAmount}, " .
                "Nouveau total: {$newTotal}"
            );
        }
    }
}
```

### 5.2 Projection Solde Client

```php
class CustomerBalanceProjector extends Projector
{
    public function handlePaymentRecorded(array $payload, DomainOutbox $event): void
    {
        // Décrémente le crédit en cours
        DB::table('contacts')
            ->where('company_id', $payload['companyId'])
            ->where('contact_id', $payload['contactId'])
            ->where('last_event_id', '<', $event->id)
            ->update([
                'montant_credit_en_cours' => DB::raw(
                    "GREATEST(0, montant_credit_en_cours - {$payload['amount']})"
                ),
                'last_payment_date' => $payload['recordedAt'],
                'last_event_id' => $event->id,
            ]);

        // Insère l'encaissement
        DB::table('encaissements')->insert([
            'company_id' => $payload['companyId'],
            'credit_id' => $payload['creditId'],
            'contact_id' => $payload['contactId'],
            'montant' => $payload['amount'],
            'mode_paiement' => $payload['paymentMethod'],
            'mouvement_id' => $payload['movementId'],
            'date_encaissement' => $payload['recordedAt'],
            'last_event_id' => $event->id,
        ]);
    }

    public function handleMovementValidated(array $payload, DomainOutbox $event): void
    {
        // Incrémente le crédit en cours si commande créditée
        if ($payload['paymentType'] === 'credit') {
            DB::table('contacts')
                ->where('company_id', $payload['companyId'])
                ->where('contact_id', $payload['contactId'])
                ->update([
                    'montant_credit_en_cours' => DB::raw(
                        "montant_credit_en_cours + {$payload['totalTtc']}"
                    ),
                ]);
        }
    }
}
```

---

## 6. Moteur de Promotions

### 6.1 Types de Promotions Supportés

```php
// Promotion n+1 (achète X, paye Y)
[
    'type' => 'n_plus_one',
    'config' => [
        'buy_quantity' => 2,
        'pay_quantity' => 1,
        'applies_to' => 'cheapest', // cheapest | most_expensive | all
    ]
]

// Seuil de montant
[
    'type' => 'threshold',
    'config' => [
        'min_amount' => 100,
        'discount_percent' => 10,
        'discount_amount' => null,
    ]
]

// Remise sur paliers
[
    'type' => 'tiered',
    'config' => [
        'tiers' => [
            ['min_qty' => 10, 'discount_percent' => 5],
            ['min_qty' => 50, 'discount_percent' => 10],
            ['min_qty' => 100, 'discount_percent' => 15],
        ],
    ]
]

// Pack (produits groupés)
[
    'type' => 'bundle',
    'config' => [
        'required_articles' => [100, 101, 102],
        'bundle_price' => 50.00,
    ]
]
```

### 6.2 Moteur d'Évaluation

```php
class PromotionEngineService
{
    /**
     * Évalue toutes les promotions applicables à un panier.
     */
    public function evaluate(
        int $companyId,
        array $cartLines,
        ?int $contactGroupId = null
    ): array {
        $activePromotions = $this->getActivePromotions($companyId, $contactGroupId);
        $appliedPromotions = [];

        foreach ($activePromotions as $promo) {
            if ($this->isApplicable($promo, $cartLines)) {
                $discount = $this->calculateDiscount($promo, $cartLines);
                if ($discount > 0) {
                    $appliedPromotions[] = [
                        'promotion_id' => $promo->id,
                        'name' => $promo->name,
                        'type' => $promo->type,
                        'discount_amount' => $discount,
                    ];
                }
            }
        }

        // Trier par valeur décroissante
        usort($appliedPromotions, fn($a, $b) => $b['discount_amount'] <=> $a['discount_amount']);

        // Ne garder que les promotions combinables
        return $this->filterCombinable($appliedPromotions, $cartLines);
    }

    private function calculateDiscount(array $promo, array $lines): float
    {
        return match ($promo['type']) {
            'n_plus_one' => $this->calculateNPlusOne($promo, $lines),
            'threshold' => $this->calculateThreshold($promo, $lines),
            'tiered' => $this->calculateTiered($promo, $lines),
            'bundle' => $this->calculateBundle($promo, $lines),
            default => 0,
        };
    }

    private function calculateNPlusOne(array $promo, array $lines): float
    {
        $config = $promo['config'];
        $totalDiscount = 0;

        foreach ($lines as $line) {
            $sets = intdiv($line['quantity'], $config['buy_quantity']);
            if ($sets > 0) {
                $freeQty = $sets * ($config['buy_quantity'] - $config['pay_quantity']);
                $totalDiscount += $freeQty * $line['unit_price'];
            }
        }

        return $totalDiscount;
    }
}
```

### 6.3 Application des Promotions

```php
// Dans MovementAggregate::validate()
public function validate(...): static
{
    // ...

    // Évaluer les promotions
    $promotions = $this->promotionEngine->evaluate(
        companyId: $companyId,
        cartLines: $this->lines,
        contactGroupId: $contact->groupe_client_id,
    );

    $totalDiscount = array_sum(array_column($promotions, 'discount_amount'));
    $finalTotal = $this->totalHt - $totalDiscount;

    // Enregistrer les promotions appliquées
    foreach ($promotions as $promo) {
        $this->recordThat(new PromotionAppliedToOrder(
            uuid: $this->uuid(),
            movementId: $this->movementId,
            promotionId: $promo['promotion_id'],
            discountAmount: $promo['discount_amount'],
            originalTotal: $this->totalHt,
            finalTotal: $finalTotal,
        ));
    }

    // Continuer avec le total ajusté
    // ...
}
```

---

## 7. Optimisation des Routes

### 7.1 Algorithmes d'Optimisation

```php
class RouteOptimizationService
{
    /**
     * Algorithme du Voyageur de Commerce (TSP) avec contraintes.
     */
    public function optimizeRoute(array $stops, array $constraints): array
    {
        $points = $this->extractPoints($stops);

        // Construire la matrice de distances
        $distanceMatrix = $this->buildDistanceMatrix($points);

        // Résoudre avec OR-Tools ou heuristique 2-opt
        $optimizedOrder = $this->solveTSP($distanceMatrix, $constraints);

        // Appliquer les contraintes de capacité
        if (!empty($constraints['vehicle_capacity'])) {
            $optimizedOrder = $this->splitIntoSubRoutes(
                $optimizedOrder,
                $constraints['vehicle_capacity']
            );
        }

        // Contraintes de fenêtres de temps
        if (!empty($constraints['time_windows'])) {
            $optimizedOrder = $this->applyTimeWindows(
                $optimizedOrder,
                $constraints['time_windows']
            );
        }

        return [
            'optimized_route' => $optimizedOrder,
            'total_distance' => $this->calculateTotalDistance($optimizedOrder),
            'estimated_duration' => $this->calculateDuration($optimizedOrder),
            'savings_percent' => $this->calculateSavings($stops, $optimizedOrder),
        ];
    }

    /**
     * Algorithme 2-opt pour amélioration locale.
     */
    private function twoOptImprove(array $route, array $distances): array
    {
        $improved = true;
        while ($improved) {
            $improved = false;
            for ($i = 0; $i < count($route) - 2; $i++) {
                for ($j = $i + 2; $j < count($route); $j++) {
                    $delta = $this->calculate2OptDelta($route, $i, $j, $distances);
                    if ($delta < 0) {
                        $route = $this->perform2OptSwap($route, $i, $j);
                        $improved = true;
                    }
                }
            }
        }
        return $route;
    }
}
```

---

## 8. CRM et Visites Commerciales

### 8.1 Suivi des Interactions

```php
class CrmAggregate extends AggregateRoot
{
    /**
     * Enregistre une visite commerciale.
     */
    public function recordVisit(
        int $companyId,
        int $contactId,
        int $commercialId,
        array $visitData
    ): static {
        $this->recordThat(new CustomerVisited(
            uuid: $this->uuid(),
            visitId: $this->getNextId(),
            companyId: $companyId,
            contactId: $contactId,
            commercialId: $commercialId,
            visitType: $visitData['type'], // scheduled | unscheduled
            gpsCoordinates: $visitData['gps'],
            notes: $visitData['notes'],
            photos: $visitData['photos'] ?? [],
            signature: $visitData['signature'] ?? null,
            visitedAt: now()->toIso8601String(),
        ));

        return $this;
    }

    /**
     * Enregistre une interaction marketing.
     */
    public function recordMarketingInteraction(
        int $companyId,
        int $contactId,
        string $channel, // email | sms | phone | meeting
        string $interactionType,
        array $metadata
    ): static {
        $this->recordThat(new MarketingInteractionRecorded(
            uuid: $this->uuid(),
            companyId: $companyId,
            contactId: $contactId,
            channel: $channel,
            type: $interactionType,
            metadata: $metadata,
            recordedAt: now()->toIso8601String(),
        ));

        return $this;
    }
}
```

### 8.2 Projection Client avec Métriques

```php
class CrmProjector extends Projector
{
    public function handleCustomerVisited(array $payload, DomainOutbox $event): void
    {
        DB::table('contacts')
            ->where('company_id', $payload['companyId'])
            ->where('contact_id', $payload['contactId'])
            ->where('last_event_id', '<', $event->id)
            ->update([
                'last_visit_date' => $payload['visitedAt'],
                'last_visit_commercial_id' => $payload['commercialId'],
                'visit_count' => DB::raw('visit_count + 1'),
                'last_event_id' => $event->id,
            ]);

        // Historique des visites
        DB::table('crm_visits')->insert([
            'company_id' => $payload['companyId'],
            'visit_id' => $payload['visitId'],
            'contact_id' => $payload['contactId'],
            'commercial_id' => $payload['commercialId'],
            'visit_type' => $payload['visitType'],
            'gps_lat' => $payload['gpsCoordinates']['lat'] ?? null,
            'gps_lng' => $payload['gpsCoordinates']['lng'] ?? null,
            'notes' => $payload['notes'],
            'has_signature' => !empty($payload['signature']),
            'visited_at' => $payload['visitedAt'],
            'last_event_id' => $event->id,
        ]);
    }

    public function handleMarketingInteractionRecorded(array $payload, DomainOutbox $event): void
    {
        // Métriques d'engagement
        DB::table('contacts')
            ->where('company_id', $payload['companyId'])
            ->where('contact_id', $payload['contactId'])
            ->update([
                'last_interaction_date' => $payload['recordedAt'],
                'preferred_channel' => $this->updatePreferredChannel($payload),
            ]);
    }
}
```

---

## 9. Comptabilité Analytique

### 9.1 Journal des Écritures

```php
class JournalEntryAggregate extends AggregateRoot
{
    /**
     * Crée une écriture comptable.
     */
    public function createEntry(
        int $companyId,
        string $journalCode, // VT (ventes), AC (achats), etc.
        \DateTime $date,
        array $lines // Double écriture: au moins 2 lignes
    ): static {
        // Vérification équilibre
        $totalDebit = array_sum(array_column($lines, 'debit'));
        $totalCredit = array_sum(array_column($lines, 'credit'));

        if ($totalDebit !== $totalCredit) {
            throw new \InvalidArgumentException(
                "Déséquilibre comptable: D {$totalDebit} != C {$totalCredit}"
            );
        }

        $this->recordThat(new JournalEntryCreated(
            uuid: $this->uuid(),
            entryId: $this->getNextId(),
            companyId: $companyId,
            journalCode: $journalCode,
            date: $date->format('Y-m-d'),
            lines: $lines,
            totalAmount: $totalDebit,
        ));

        return $this;
    }
}
```

### 9.2 Projections Comptables

```php
class AccountingProjector extends Projector
{
    public function handleJournalEntryCreated(array $payload, DomainOutbox $event): void
    {
        foreach ($payload['lines'] as $line) {
            DB::table('journal_entries')->insert([
                'company_id' => $payload['companyId'],
                'entry_id' => $payload['entryId'],
                'journal_code' => $payload['journalCode'],
                'date' => $payload['date'],
                'account_number' => $line['account_number'],
                'account_label' => $line['account_label'],
                'debit' => $line['debit'],
                'credit' => $line['credit'],
                'reference' => $line['reference'] ?? null,
                'analytic_section' => $line['section'] ?? null,
                'last_event_id' => $event->id,
            ]);
        }

        // Mise à jour des soldes de comptes
        foreach ($payload['lines'] as $line) {
            $balanceChange = $line['debit'] - $line['credit'];

            DB::statement("
                INSERT INTO account_balances
                (company_id, account_number, balance, last_event_id, updated_at)
                VALUES (?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                balance = balance + VALUES(balance),
                last_event_id = VALUES(last_event_id),
                updated_at = NOW()
            ", [
                $payload['companyId'],
                $line['account_number'],
                $balanceChange,
                $event->id,
            ]);
        }
    }
}
```

---

## 10. Gestion des Ressources Humaines (HR)

### 10.1 Cycle de Vie Employé
Le domaine HR gère le cycle complet de l'employé via des événements immuables.

```php
class HrAggregate extends AggregateRoot
{
    public function createEmployee(int $employeeId, int $companyId, array $data): static
    {
        $this->recordThat(new EmployeeCreated($this->uuid(), $employeeId, $companyId, $data));
        return $this;
    }
}
```

---

## 11. Gestion de Flotte (Fleet)

### 11.1 Enregistrement et Maintenance
Suivi en temps réel des véhicules et de leur état opérationnel.

```php
class FleetAggregate extends AggregateRoot
{
    public function registerVehicle(int $vehicleId, int $companyId, array $data): static
    {
        $this->recordThat(new VehicleRegistered($this->uuid(), $vehicleId, $companyId, $data));
        return $this;
    }
}
```

---

## 12. Achats et Approvisionnements (Purchasing)

### 12.1 Flux de Commande Fournisseur
Intégration directe avec le stock lors de la réception des commandes.

```php
class PurchasingAggregate extends AggregateRoot
{
    public function createPurchaseOrder(int $purchaseOrderId, int $companyId, int $supplierId, array $items): static
    {
        $this->recordThat(new PurchaseOrderCreated(...));
        return $this;
    }
}
```

---

## 13. Gestion de Projets

### 13.1 Planification et Suivi
Suivi des jalons et de l'état des projets par entreprise.

```php
class ProjectAggregate extends AggregateRoot
{
    public function createProject(int $projectId, int $companyId, string $name, array $data): static
    {
        $this->recordThat(new ProjectCreated(...));
        return $this;
    }
}
```

---

## 14. Prochaines Sections

- [01-architecture-generale.md](./01-architecture-generale.md) - Documentation technique générale
- [02-infrastructure-core.md](./02-infrastructure-core.md) - Services et composants techniques
- [03-domaines-metiers.md](./03-domaines-metiers.md) - Implémentation des 13 macro-domaines
- [04-projections-analytics.md](./04-projections-analytics.md) - Phase C : Dashboards et analytics
- [05-temps-reel-crdt.md](./05-temps-reel-crdt.md) - Phase D : Temps réel et sync mobile
- [06-api-graphql.md](./06-api-graphql.md) - Phase D : API de requête GraphQL
- [07-alerting-observabilite.md](./07-alerting-observabilite.md) - Phase D : Monitoring et alertes
- [08-deployment-operations.md](./08-deployment-operations.md) - Guide de déploiement et maintenance
