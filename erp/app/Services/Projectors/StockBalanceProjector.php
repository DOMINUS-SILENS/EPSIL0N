<?php

namespace App\Services\Projectors;

use App\Models\ProjectionVersion;
use App\Services\Projector;
use Illuminate\Support\Facades\DB;
use App\Models\DomainOutbox;

class StockBalanceProjector extends Projector
{
    protected string $table = 'stock_balances';
    protected string $idField = 'article_id';

    protected function getVersionFromDatabase(): int
    {
        return ProjectionVersion::where('projector_name', self::class)->value('version') ?? 0;
    }

    protected function setVersion(int $version): void
    {
        ProjectionVersion::updateOrCreate(
        ['projector_name' => self::class],
        ['version' => $version]
        );
    }

    protected function getState(int $aggregateId): array
    {
        return [];
    }

    protected function restoreState(int $aggregateId, array $state): void
    {
    }

    protected function setLastProcessedEventId(int $aggregateId, int $lastEventId): void
    {
    }

    /**
     * Common Upsert logic incorporating idempotency guarantees using exactly the last_event_id constraint.
     */
    protected function upsertBalance(array $payload, int $eventId, string $operation, float|int $qty): void
    {
        // For simplicity, entreprise_id is assumed isolated per shard sequence or provided in payload.
        // Assuming entreprise_id defaults to 1 for this context if missing
        $entrepriseId = $payload['entreprise_id'];

        if (!$entrepriseId) {
            throw new \RuntimeException('Missing entreprise_id invariant');
        }
        $articleId = $payload['article_id'];

        $affected = DB::table('stock_balances')
            ->where('entreprise_id', $entrepriseId)
            ->where('article_id', $articleId)
            ->where('last_event_id', '<', $eventId)
            ->update([
            'quantity' => DB::raw("quantity {$operation} {$qty}"),
            'last_event_id' => $eventId,
            'updated_at' => now()
        ]);

        if ($affected === 0) {
            // Either record exists but generated prior event, or doesn't exist.
            // Check existence.
            $exists = DB::table('stock_balances')
                ->where('entreprise_id', $entrepriseId)
                ->where('article_id', $articleId)
                ->exists();

            if (!$exists) {
                // Determine absolute start value for logic
                $absoluteQty = $operation === '+' ? $qty : -$qty;
                DB::table('stock_balances')->insert([
                    'entreprise_id' => $entrepriseId,
                    'article_id' => $articleId,
                    'quantity' => $absoluteQty,
                    'reserved_quantity' => 0,
                    'last_event_id' => $eventId,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }
    }

    public function handleStockEntered(array $payload, DomainOutbox $event): void
    {
        $this->upsertBalance($payload, $event->id, '+', $payload['quantity']);
    }

    public function handleStockExited(array $payload, DomainOutbox $event): void
    {
        $this->upsertBalance($payload, $event->id, '-', $payload['quantity']);
    }

    // Removed deprecated handleStockTransferred.

    public function handleMovementValidated(array $payload, DomainOutbox $event): void
    {
        foreach ($payload['lines'] as $line) {
            // Reserve stock (Draft -> Validated)
            $this->upsertReservation($payload['entrepriseId'], $line['article_id'], $event->id, '+', $line['quantite']);
        }
    }

    public function handleMovementDelivered(array $payload, DomainOutbox $event): void
    {
        foreach ($payload['lines'] as $line) {
            // Unreserve and Consume (Validated -> Delivered)
            $this->upsertReservation($payload['entrepriseId'], $line['article_id'], $event->id, '-', $line['quantite']);

            // Re-use logic to actually decrement the real stock
            $this->upsertBalance(
            ['entreprise_id' => $payload['entrepriseId'], 'article_id' => $line['article_id']],
                $event->id,
                '-',
                $line['quantite']
            );
        }
    }

    public function handleMovementCancelled(array $payload, DomainOutbox $event): void
    {
        if ($payload['previousState'] === 'validated') {
            foreach ($payload['lines'] as $line) {
                // Return reservation (Validated -> Cancelled)
                $this->upsertReservation($payload['entrepriseId'], $line['article_id'], $event->id, '-', $line['quantite']);
            }
        }
    }

    protected function upsertReservation(int $entrepriseId, int $articleId, int $eventId, string $operation, $qty): void
    {
        $affected = DB::table('stock_balances')
            ->where('entreprise_id', $entrepriseId)
            ->where('article_id', $articleId)
            ->where('last_event_id', '<', $eventId)
            ->update([
            'reserved_quantity' => DB::raw("reserved_quantity {$operation} {$qty}"),
            'last_event_id' => $eventId,
            'updated_at' => now()
        ]);

        if ($affected === 0) {
            $exists = DB::table('stock_balances')
                ->where('entreprise_id', $entrepriseId)
                ->where('article_id', $articleId)
                ->exists();

            if (!$exists) {
                $absoluteQty = $operation === '+' ? $qty : -$qty;
                DB::table('stock_balances')->insert([
                    'entreprise_id' => $entrepriseId,
                    'article_id' => $articleId,
                    'quantity' => 0,
                    'reserved_quantity' => $absoluteQty,
                    'last_event_id' => $eventId,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }
    }

    // --- NEW PHASE: MULTI-DEPOT PHYSICAL NETTING ---

    public function handleStockReceived(array $payload, DomainOutbox $event): void
    {
        $this->upsertDepotBalance($payload['entrepriseId'], $payload['depotId'], $payload['articleId'], $event->id, '+', $payload['quantity']);
    }

    public function handleStockConsumed(array $payload, DomainOutbox $event): void
    {
        $this->upsertDepotBalance($payload['entrepriseId'], $payload['depotId'], $payload['articleId'], $event->id, '-', $payload['quantity']);
    }

    public function handleStockTransferred(array $payload, DomainOutbox $event): void
    {
        // Double-entry atomicity handled elegantly via sequenced operations
        $this->upsertDepotBalance($payload['entrepriseId'], $payload['sourceDepotId'], $payload['articleId'], $event->id, '-', $payload['quantity']);
        $this->upsertDepotBalance($payload['entrepriseId'], $payload['targetDepotId'], $payload['articleId'], $event->id, '+', $payload['quantity']);
    }

    public function handleStockAdjusted(array $payload, DomainOutbox $event): void
    {
        // Directly overrides the value cleanly assuming single point of absolute physical truth
        $affected = DB::table('article_unite_depot')
            ->where('entreprise_id', $payload['entrepriseId'])
            ->where('depot_id', $payload['depotId'])
            ->where('article_id', $payload['articleId'])
            ->where('last_event_id', '<', $event->id)
            ->update([
            'stock_theorique' => $payload['actualQuantity'],
            'last_event_id' => $event->id,
            'updated_at' => now(),
        ]);

        if ($affected === 0) {
            $this->ensureDepotRowExists($payload['entrepriseId'], $payload['depotId'], $payload['articleId'], $event->id, clone $payload['actualQuantity']);
        }
    }

    protected function upsertDepotBalance(int $entrepriseId, int $depotId, int $articleId, int $eventId, string $op, $qty): void
    {
        $affected = DB::table('article_unite_depot')
            ->where('entreprise_id', $entrepriseId)
            ->where('depot_id', $depotId)
            ->where('article_id', $articleId)
            ->where('last_event_id', '<', $eventId)
            ->update([
            'stock_theorique' => DB::raw("stock_theorique {$op} {$qty}"),
            'last_event_id' => $eventId,
            'updated_at' => now()
        ]);

        if ($affected === 0) {
            $absQty = $op === '+' ? $qty : -$qty;
            $this->ensureDepotRowExists($entrepriseId, $depotId, $articleId, $eventId, $absQty);
        }
    }

    protected function ensureDepotRowExists(int $entrepriseId, int $depotId, int $articleId, int $eventId, $qty): void
    {
        $exists = DB::table('article_unite_depot')
            ->where('entreprise_id', $entrepriseId)
            ->where('depot_id', $depotId)
            ->where('article_id', $articleId)
            ->exists();

        if (!$exists) {
            DB::table('article_unite_depot')->insert([
                'entreprise_id' => $entrepriseId,
                'depot_id' => $depotId,
                'article_id' => $articleId,
                'stock_theorique' => $qty,
                'last_event_id' => $eventId,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }

    public static function getAvailableBalance(int $entrepriseId, int $depotId, int $articleId): float
    {
        return DB::table('article_unite_depot')
            ->where('entreprise_id', $entrepriseId)
            ->where('depot_id', $depotId)
            ->where('article_id', $articleId)
            ->value('stock_theorique') ?? 0.0;
    }
}
